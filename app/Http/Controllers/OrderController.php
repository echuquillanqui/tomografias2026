<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AgreementPrice;
use App\Models\Exam;
use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const ESTADOS = ['Pendiente', 'En proceso', 'Informado', 'Entregado', 'Anulado'];
    private const TIPOS_PAGO = ['Efectivo', 'Tarjeta', 'Transferencia', 'Yape/Plin', 'Convenio'];

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
        ]);
    }

    public function create(Request $request): View
    {
        return view('orders.form', $this->formData($request) + [
            'order' => new Order(['fecha_orden' => now(), 'estado' => 'Pendiente', 'descuento' => 0]),
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
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme', 'orderExams.exam', 'creator', 'report']);

        return view('orders.show', compact('order'));
    }

    public function edit(Request $request, Order $order): View
    {
        $order->load('orderExams');

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
            'exams' => Exam::select(['id', 'nombre_examen'])->where('activo', true)->orderBy('nombre_examen')->get(),
            'agreementPrices' => AgreementPrice::select(['agreement_id', 'exam_id', 'tipo_contraste', 'precio_pactado'])->get(),
            'medicosSolicitantes' => $medicos->whereIn('tipo_medico', ['Solicitante', 'Ambos'])->values(),
            'medicosInformantes' => $medicos->whereIn('tipo_medico', ['De Informe', 'Ambos'])->values(),
            'estados' => self::ESTADOS,
            'tiposPago' => self::TIPOS_PAGO,
        ];
    }

    private function saveOrder(Order $order, Request $request): Order
    {
        $request->merge(['exams' => collect($request->input('exams', []))->filter(fn ($row) => ! empty($row['exam_id']))->values()->all()]);
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'codigo_orden' => ['nullable', 'string', 'max:255', Rule::unique('orders', 'codigo_orden')->ignore($order)],
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
        ]);
        $subtotal = collect($data['exams'])->sum('precio');
        $descuento = $data['descuento'] ?? 0;
        $payload = $data + [
            'codigo_orden' => $data['codigo_orden'] ?? null,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => max($subtotal - $descuento, 0),
            'created_by' => $order->exists ? $order->created_by : auth()->id(),
        ];
        unset($payload['exams']);
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

        $this->generateReport($order);

        return $order;
    }

    private function generateReport(Order $order): void
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

---

**Médico radiólogo:** {$this->valueOrPlaceholder($reportingDoctor?->nombre_completo, '[Nombre del médico informante]')}
**CMP:** {$this->valueOrPlaceholder($reportingDoctor?->cmp, '[Número CMP]')}
**RNE:** {$this->valueOrPlaceholder($reportingDoctor?->rne, '[Número RNE]')}
**Firma:** {$this->valueOrPlaceholder($reportingDoctor?->firma_path, '[Firma digital o imagen de firma]')}
REPORT;

        $order->report()->updateOrCreate(
            ['order_id' => $order->id],
            ['titulo' => 'REPORTE DE TOMOGRAFÍA COMPUTARIZADA', 'contenido' => $content]
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
