<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AgreementPrice;
use App\Models\Exam;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Reagent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const ESTADOS = ['Pendiente', 'En proceso', 'Informado', 'Entregado', 'Anulado'];
    private const TIPOS_PAGO = ['Efectivo', 'Tarjeta', 'Transferencia', 'Yape/Plin', 'Convenio'];
    private const UNIDADES = ['Topico', 'Sala de control (Tecnologo)'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $orders = Order::with(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme'])
            ->withCount('orderExams')
            ->when($search !== '', fn ($q) => $q->where('codigo_orden', 'like', "%{$search}%")
                ->orWhereHas('patient', fn ($qq) => $qq->where('dni', 'like', "%{$search}%")
                    ->orWhere('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")))
            ->latest('fecha_orden')
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'search' => $search,
            'estados' => self::ESTADOS,
            'tiposPago' => self::TIPOS_PAGO,
            'unidades' => self::UNIDADES,
        ]);
    }

    public function create(Request $request): View
    {
        return view('orders.form', $this->formData($request) + [
            'order' => new Order(['fecha_orden' => now(), 'estado' => 'Pendiente', 'descuento' => 0, 'unidad' => 'Topico']),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $order = DB::transaction(fn () => $this->saveOrder(new Order(), $request));

        return redirect()->route('orders.show', $order)->with('success', 'Orden generada correctamente.');
    }

    public function show(Order $order): View
    {
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme', 'orderExams.exam', 'creator', 'report', 'consumables.reagent']);

        return view('orders.show', compact('order'));
    }

    public function edit(Request $request, Order $order): View
    {
        $order->load(['orderExams', 'consumables']);

        return view('orders.form', $this->formData($request, $order) + ['order' => $order, 'mode' => 'edit']);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(fn () => $this->saveOrder($order, $request));

        return redirect()->route('orders.show', $order)->with('success', 'Orden actualizada correctamente.');
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'estado' => ['required', Rule::in(self::ESTADOS)],
        ]);

        $order->update($data);

        return redirect()->route('orders.index')->with('success', 'Estado de la orden actualizado correctamente.');
    }

    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'tipo_pago' => ['required', Rule::in(self::TIPOS_PAGO)],
        ]);

        $order->update($data);

        return redirect()->route('orders.index')->with('success', 'Tipo de pago actualizado correctamente.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Orden eliminada correctamente.');
    }

    private function formData(Request $request, ?Order $order = null): array
    {
        $medicos = User::select(['id', 'nombre_completo', 'tipo_medico', 'comision_porcentaje'])
            ->where('rol', 'Médico')
            ->where('activo', true)
            ->orderBy('nombre_completo')
            ->get();

        return [
            'patients' => Patient::select(['id', 'dni', 'nombres', 'apellidos'])->orderBy('apellidos')->orderBy('nombres')->get(),
            'agreements' => Agreement::select(['id', 'nombre_institucion'])->where('activo', true)->orderBy('nombre_institucion')->get(),
            'exams' => Exam::with('reagents:id,nombre,unidad')->select(['id', 'nombre_examen'])->where('activo', true)->orderBy('nombre_examen')->get(),
            'reagents' => Reagent::select(['id', 'nombre', 'unidad'])->where('activo', true)->orderBy('nombre')->get(),
            'agreementPrices' => AgreementPrice::select(['agreement_id', 'exam_id', 'tipo_contraste', 'precio_pactado'])->get(),
            'medicosSolicitantes' => $medicos->whereIn('tipo_medico', ['Solicitante', 'Ambos'])->values(),
            'medicosInformantes' => $medicos->whereIn('tipo_medico', ['De Informe', 'Ambos'])->values(),
            'estados' => self::ESTADOS,
            'tiposPago' => self::TIPOS_PAGO,
            'unidades' => self::UNIDADES,
        ];
    }

    private function saveOrder(Order $order, Request $request): Order
    {
        $request->merge(['exams' => collect($request->input('exams', []))->filter(fn ($row) => ! empty($row['exam_id']))->values()->all()]);
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'codigo_orden' => ['nullable', 'string', 'max:255', Rule::unique('orders', 'codigo_orden')->ignore($order)],
            'unidad' => ['required', Rule::in(self::UNIDADES)],
            'archivo_orden' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'agreement_id' => ['required', 'exists:agreements,id'],
            'medico_solicitante_id' => ['nullable', 'exists:users,id'],
            'medico_informe_id' => ['nullable', 'exists:users,id'],
            'fecha_orden' => ['required', 'date'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'tipo_pago' => ['required', Rule::in(self::TIPOS_PAGO)],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
            'exams' => ['required', 'array', 'min:1'],
            'exams.*.exam_id' => ['required', 'exists:exams,id'],
            'exams.*.tipo_contraste' => ['required', Rule::in(['Con contraste', 'Sin contraste'])],
            'exams.*.precio' => ['required', 'numeric', 'min:0'],
            'exams.*.estado' => ['required', Rule::in(['Pendiente', 'Realizado', 'Informado', 'Anulado'])],
            'consumables' => ['nullable', 'array'],
            'consumables.*.reagent_id' => ['required', 'exists:reagents,id'],
            'consumables.*.cantidad' => ['required', 'numeric', 'min:0'],
        ]);
        $subtotal = collect($data['exams'])->sum('precio');
        $descuento = $data['descuento'] ?? 0;
        if ($request->hasFile('archivo_orden')) {
            if ($order->archivo_orden_path) {
                Storage::disk('public')->delete($order->archivo_orden_path);
            }
            $data['archivo_orden_path'] = $request->file('archivo_orden')->store('ordenes', 'public');
        }

        $payload = $data + [
            'codigo_orden' => $data['codigo_orden'] ?? null,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => max($subtotal - $descuento, 0),
            'created_by' => $order->exists ? $order->created_by : auth()->id(),
        ];
        unset($payload['exams'], $payload['consumables'], $payload['archivo_orden']);
        $order->fill($payload)->save();
        $order->orderExams()->delete();
        $med = User::find($data['medico_informe_id'] ?? null);
        $pct = $med?->comision_porcentaje;
        foreach ($data['exams'] as $row) {
            $order->orderExams()->create($row + [
                'comision_porcentaje' => $pct,
                'comision_monto' => $pct ? ($row['precio'] * $pct / 100) : null,
            ]);
        }

        $order->consumables()->delete();
        foreach (collect($data['consumables'] ?? [])->filter(fn ($row) => (float) ($row['cantidad'] ?? 0) > 0) as $row) {
            $order->consumables()->updateOrCreate(
                ['reagent_id' => $row['reagent_id']],
                ['cantidad' => $row['cantidad']]
            );
        }

        $this->createInitialReport($order);

        return $order;
    }


    public function fichaIngresoTemplate(Order $order): View
    {
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam']);
        $hasContrast = $order->orderExams->contains('tipo_contraste', 'Con contraste');

        return view('orders.templates.ficha-ingreso', compact('order', 'hasContrast'));
    }

    public function fichaIngresoPdf(Order $order)
    {
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam']);
        $hasContrast = $order->orderExams->contains('tipo_contraste', 'Con contraste');

        return Pdf::loadView('orders.pdfs.ficha-ingreso', compact('order', 'hasContrast'))->setPaper('a4')->stream('ficha-ingreso-'.$order->id.'.pdf');
    }

    public function declaracionJuradaTemplate(Order $order): View
    {
        $order->load(['patient', 'orderExams.exam']);

        return view('orders.templates.declaracion-jurada', compact('order'));
    }

    public function declaracionJuradaPdf(Order $order)
    {
        $order->load(['patient', 'orderExams.exam']);

        return Pdf::loadView('orders.pdfs.declaracion-jurada', compact('order'))->setPaper('a4')->stream('declaracion-jurada-'.$order->id.'.pdf');
    }

    private function isMinor(Patient $patient): bool
    {
        if ($patient->fecha_nacimiento) {
            return $patient->fecha_nacimiento->age < 18;
        }

        return $patient->edad !== null && (int) $patient->edad < 18;
    }

    public function createInitialReport(Order $order): void
    {
        $order->load(['patient', 'medicoSolicitante', 'medicoInforme', 'orderExams.exam']);

        $patient = $order->patient;
        $exams = $order->orderExams;
        $examNames = $exams->pluck('exam.nombre_examen')->filter()->values();
        $region = $this->regionFromExams($examNames);
        $contrast = $exams->contains('tipo_contraste', 'Con contraste')
            ? 'Con contraste endovenoso'
            : 'Sin contraste';
        $contrastTechnique = $exams->contains('tipo_contraste', 'Con contraste')
            ? "\nSe administró contraste endovenoso yodado, sin evidenciarse reacciones adversas inmediatas durante el procedimiento.\n"
            : '';
        $reportingDoctor = $order->medicoInforme;

        $content = <<<REPORT
**REPORTE DE TOMOGRAFÍA COMPUTARIZADA**

**Paciente:** {$patient->nombres} {$patient->apellidos}
**DNI:** {$patient->dni}
**Edad:** {$this->patientAge($patient)}
**Fecha del estudio:** {$order->fecha_orden->format('d/m/Y')}
**Médico solicitante:** {$this->valueOrPlaceholder($order->medicoSolicitante?->nombre_completo, '[Nombre del médico solicitante]')}
**Estudio solicitado:** Tomografía computarizada de {$region}
**Contraste:** {$contrast}

---

### **TÉCNICA**

Se realizó tomografía computarizada de {$region} mediante adquisición helicoidal/multicorte, con reconstrucciones multiplanares en planos axial, coronal y sagital.
{$contrastTechnique}
---

### **HALLAZGOS**

**Región evaluada:** {$region}

Se evalúan las estructuras anatómicas incluidas en el campo de estudio.

**Órganos y estructuras principales:**
[Describir hallazgos normales o patológicos según el estudio.]

**Lesiones / alteraciones identificadas:**
[Ubicación, tamaño, densidad, bordes, realce con contraste, compromiso de estructuras vecinas.]

**Estructuras óseas:**
[Sin lesiones óseas evidentes / Cambios degenerativos / Fracturas / Lesiones líticas o blásticas / Otros.]

**Partes blandas:**
[Sin alteraciones significativas / Describir hallazgos.]

**Otros hallazgos:**
[Hallazgos incidentales o relevantes.]

---

### **IMPRESIÓN DIAGNÓSTICA**

1. [Conclusión principal del estudio.]
2. [Hallazgo secundario relevante, si existe.]
3. [Sugerencia de correlación clínica, laboratorio o estudios complementarios, si corresponde.]

REPORT;

        $order->report()->updateOrCreate(
            ['order_id' => $order->id],
            ['titulo' => 'REPORTE DE TOMOGRAFÍA COMPUTARIZADA', 'contenido' => $content, 'medico_firmante_id' => $reportingDoctor?->id]
        );
    }

    private function regionFromExams($examNames): string
    {
        $names = $examNames->implode(', ');

        return $names !== '' ? $names : '[Región anatómica]';
    }

    private function patientAge(Patient $patient): string
    {
        if ($patient->edad) {
            return $patient->edad . ' años';
        }

        if ($patient->fecha_nacimiento) {
            return $patient->fecha_nacimiento->age . ' años';
        }

        return '[Edad]';
    }

    private function valueOrPlaceholder(?string $value, string $placeholder): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $placeholder;
    }

    private function nextCode(): string
    {
        $id = (Order::max('id') ?? 0) + 1;

        return 'ORD-' . now()->format('Ymd') . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
    }
}
