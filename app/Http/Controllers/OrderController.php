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
    private const TIPOS_COMPROBANTE = ['Boleta', 'Factura'];
    private const MOTIVOS_ELIMINACION = ['error de digitacion', 'equivocacion', 'error por sistema', 'otros'];
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
            'tiposComprobante' => self::TIPOS_COMPROBANTE,
            'motivosEliminacion' => self::MOTIVOS_ELIMINACION,
            'unidades' => self::UNIDADES,
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


    public function updateOrderFile(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'archivo_orden' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($order->archivo_orden_path) {
            Storage::disk('public')->delete($order->archivo_orden_path);
        }

        $order->update([
            'archivo_orden_path' => $request->file('archivo_orden')->store('ordenes', 'public'),
        ]);

        return redirect()->route('orders.index')->with('success', 'Archivo de orden actualizado correctamente.');
    }

    public function destroy(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'motivo_eliminacion' => ['required', Rule::in(self::MOTIVOS_ELIMINACION)],
            'motivo_eliminacion_otro' => ['required_if:motivo_eliminacion,otros', 'nullable', 'string', 'max:255'],
        ]);

        $motivo = $data['motivo_eliminacion'] === 'otros'
            ? trim((string) $data['motivo_eliminacion_otro'])
            : $data['motivo_eliminacion'];

        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Orden eliminada correctamente. Motivo: '.$motivo);
    }

    private function formData(Request $request, ?Order $order = null): array
    {
        $medicos = $this->activeDoctors();

        return [
            'patients' => Patient::select(['id', 'dni', 'nombres', 'apellidos', 'telefono', 'fecha_nacimiento', 'edad'])->orderBy('apellidos')->orderBy('nombres')->get(),
            'agreements' => Agreement::select(['id', 'nombre_institucion', 'mostrar_precio_orden'])->where('activo', true)->orderByRaw("CASE WHEN UPPER(nombre_institucion) = 'PARTICULAR' THEN 0 ELSE 1 END")->orderBy('nombre_institucion')->get(),
            'exams' => Exam::with('reagents:id,nombre,unidad')->select(['id', 'nombre_examen'])->where('activo', true)->orderBy('nombre_examen')->get(),
            'reagents' => Reagent::select(['id', 'nombre', 'unidad'])->where('activo', true)->orderBy('nombre')->get(),
            'agreementPrices' => AgreementPrice::select(['agreement_id', 'exam_id', 'tipo_contraste', 'precio_pactado'])->get(),
            'medicosSolicitantes' => $medicos->whereIn('tipo_medico', ['Solicitante', 'Ambos'])->values(),
            'medicosInformantes' => $medicos->whereIn('tipo_medico', ['De Informe', 'Ambos'])->values(),
            'estados' => self::ESTADOS,
            'tiposPago' => self::TIPOS_PAGO,
            'tiposComprobante' => self::TIPOS_COMPROBANTE,
            'motivosEliminacion' => self::MOTIVOS_ELIMINACION,
            'unidades' => self::UNIDADES,
        ];
    }

    private function activeDoctors()
    {
        return User::select(['id', 'nombre_completo', 'tipo_medico', 'comision_porcentaje'])
            ->where('rol', 'Médico')
            ->where('activo', true)
            ->orderBy('nombre_completo')
            ->get();
    }

    private function medicosInformantes()
    {
        return $this->activeDoctors()->whereIn('tipo_medico', ['De Informe', 'Ambos'])->values();
    }

    private function saveOrder(Order $order, Request $request): Order
    {
        $request->merge(['exams' => collect($request->input('exams', []))->filter(fn ($row) => ! empty($row['exam_id']))->values()->all()]);
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'codigo_orden' => ['nullable', 'string', 'max:255', Rule::unique('orders', 'codigo_orden')->ignore($order)],
            'unidad' => ['nullable', Rule::in(self::UNIDADES)],
            'archivo_orden' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'agreement_id' => ['required', 'exists:agreements,id'],
            'medico_solicitante_id' => ['nullable', 'exists:users,id'],
            'medico_informe_id' => ['nullable', 'exists:users,id'],
            'fecha_orden' => ['required', 'date'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'tipo_pago' => ['required', Rule::in(self::TIPOS_PAGO)],
            'tipo_comprobante' => ['nullable', Rule::in(self::TIPOS_COMPROBANTE)],
            'numero_comprobante' => ['nullable', 'string', 'max:255'],
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
        $agreementExamIds = AgreementPrice::where('agreement_id', $data['agreement_id'])->pluck('exam_id')->map(fn ($id) => (int) $id)->unique();
        $invalidExam = collect($data['exams'])->first(fn ($row) => ! $agreementExamIds->contains((int) $row['exam_id']));
        if ($invalidExam) {
            back()->withErrors(['exams' => 'Solo se pueden agregar exámenes asociados al convenio seleccionado.'])->throwResponse();
        }

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
            'unidad' => $data['unidad'] ?? null,
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
        $hasContrastedExam = collect($data['exams'])->contains(fn ($row) => ($row['tipo_contraste'] ?? null) === 'Con contraste');
        if ($hasContrastedExam) {
            foreach (collect($data['consumables'] ?? [])->filter(fn ($row) => (float) ($row['cantidad'] ?? 0) > 0) as $row) {
                $order->consumables()->updateOrCreate(
                    ['reagent_id' => $row['reagent_id']],
                    ['cantidad' => $row['cantidad']]
                );
            }
        }

        $order->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam']);
        $this->syncPrintableDocuments($order);
        $this->createInitialReport($order);

        return $order;
    }


    public function triaje(Order $order): View
    {
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam.reagents', 'admissionForm', 'consumables.reagent']);
        $this->syncPrintableDocuments($order);
        $order->refresh()->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam.reagents', 'admissionForm', 'consumables.reagent']);
        $admissionData = $order->admissionForm?->data ?? [];
        $reagents = Reagent::select(['id', 'nombre', 'unidad'])->where('activo', true)->orderBy('nombre')->get();
        $triageConsumables = $this->triageConsumables($order);

        return view('orders.triaje', compact('order', 'admissionData', 'reagents', 'triageConsumables') + ['unidades' => self::UNIDADES]);
    }

    private function triageConsumables(Order $order): array
    {
        if ($order->consumables->isNotEmpty()) {
            return $order->consumables->map(fn ($consumable) => [
                'reagent_id' => (string) $consumable->reagent_id,
                'name' => $consumable->reagent?->nombre ?? 'Consumible',
                'unit' => $consumable->reagent?->unidad_medida ?? '',
                'cantidad' => (float) $consumable->cantidad,
            ])->values()->all();
        }

        return $order->orderExams
            ->filter(fn ($orderExam) => $orderExam->tipo_contraste === 'Con contraste')
            ->flatMap(fn ($orderExam) => $orderExam->exam?->reagents ?? collect())
            ->groupBy('id')
            ->map(fn ($reagents) => [
                'reagent_id' => (string) $reagents->first()->id,
                'name' => $reagents->first()->nombre,
                'unit' => $reagents->first()->unidad_medida ?? '',
                'cantidad' => $reagents->sum(fn ($reagent) => (float) ($reagent->pivot->cantidad_estimada ?? 0)),
            ])
            ->filter(fn ($row) => (float) $row['cantidad'] > 0)
            ->values()
            ->all();
    }

    public function updateTriaje(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'unit' => ['nullable', 'string', 'max:255'],
            'cause' => ['nullable', 'string'],
            'symptomatology' => ['nullable', 'string'],
            'surgeries' => ['nullable', 'string', 'max:255'],
            'surgeries_detail' => ['nullable', 'string', 'max:255'],
            'medication' => ['nullable', 'string'],
            'antecedents' => ['nullable', 'string'],
            'medications' => ['nullable', 'array'],
            'medications.*' => ['nullable', 'string', 'max:255'],
            'allergy' => ['nullable', 'string', 'max:255'],
            'fasting' => ['nullable', Rule::in(['SI', 'NO'])],
            'creatinine' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string'],
            'rule_out' => ['nullable', 'string'],
            'condition' => ['nullable', Rule::in(['NORMAL', 'PATOLOGICO'])],
            'provenance' => ['nullable', 'string', 'max:255'],
            'peripheral_route' => ['nullable', Rule::in(['18', '20', '22', 'Permeable'])],
            'informed_by' => ['nullable', 'string', 'max:255'],
            'delivery' => ['nullable', 'string'],
            'plates_count' => ['nullable', 'integer', 'min:0'],
            'delivery_options' => ['nullable', 'array'],
            'delivery_quantities' => ['nullable', 'array'],
            'delivery_quantities.*' => ['nullable', 'integer', 'min:0'],
            'delivery_options.*' => ['nullable', Rule::in(['PLACAS', 'CD', 'INFORME'])],
            'delivery_media' => ['nullable', Rule::in(['CD', 'LINK', 'AMBOS'])],
            'consumables' => ['nullable', 'array'],
            'consumables.*.reagent_id' => ['required', 'exists:reagents,id'],
            'consumables.*.cantidad' => ['required', 'numeric', 'min:0'],
        ]);

        $order->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam', 'admissionForm']);
        $this->syncPrintableDocuments($order);
        $current = $order->fresh('admissionForm')->admissionForm?->data ?? [];
        $formData = collect($data)->except('consumables')->all();
        $formData['delivery_options'] = array_values($formData['delivery_options'] ?? ['PLACAS', 'CD', 'INFORME']);
        $formData['delivery_quantities'] = collect($formData['delivery_quantities'] ?? [])->map(fn ($item) => $item === null || $item === '' ? '' : (int) $item)->all();
        $formData['plates_count'] = $formData['delivery_quantities']['PLACAS'] ?? ($formData['plates_count'] ?? null);
        $formData['delivery'] = implode(', ', $formData['delivery_options']);
        if (($formData['surgeries'] ?? '') !== 'Otros') $formData['surgeries_detail'] = '';
        $medications = collect($formData['medications'] ?? [])->map(fn ($item) => trim((string) $item))->filter()->values()->all();
        $formData['medications'] = $medications;
        $formData['medication'] = implode(PHP_EOL, $medications);
        $order->admissionForm()->updateOrCreate([], ['data' => array_merge($current, $formData)]);
        $order->update(['unidad' => $data['unit'] ?? null]);
        $order->consumables()->delete();
        foreach (collect($data['consumables'] ?? [])->filter(fn ($row) => (float) ($row['cantidad'] ?? 0) > 0) as $row) {
            $order->consumables()->updateOrCreate(['reagent_id' => $row['reagent_id']], ['cantidad' => $row['cantidad']]);
        }

        return redirect()->route('orders.triaje', $order)->with('success', 'Parte de triaje guardado correctamente.');
    }

    public function fichaIngresoTemplate(Order $order): View
    {
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam', 'admissionForm']);
        $this->syncPrintableDocuments($order);
        $order->refresh()->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam', 'admissionForm']);
        $admissionData = $order->admissionForm?->data ?? [];
        $hasContrast = $order->orderExams->contains('tipo_contraste', 'Con contraste');
        $contrastConsumables = $this->triageConsumables($order);
        $medicosInformantes = $this->medicosInformantes();

        return view('orders.templates.ficha-ingreso', compact('order', 'hasContrast', 'admissionData', 'contrastConsumables', 'medicosInformantes'));
    }

    public function updateFichaIngreso(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'agreement' => ['nullable', 'string', 'max:255'],
            'request_number' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
            'patient_name' => ['nullable', 'string', 'max:255'],
            'patient_dni' => ['nullable', 'string', 'max:255'],
            'patient_phone' => ['nullable', 'string', 'max:255'],
            'patient_birthdate' => ['nullable', 'string', 'max:255'],
            'patient_age' => ['nullable', 'string', 'max:255'],
            'requested_by' => ['nullable', 'string', 'max:255'],
            'contrast_label' => ['nullable', 'string', 'max:255'],
            'study' => ['nullable', 'string'],
            'observations' => ['nullable', 'string'],
            'rule_out' => ['nullable', 'string'],
            'condition' => ['nullable', Rule::in(['NORMAL', 'PATOLOGICO'])],
            'provenance' => ['nullable', 'string', 'max:255'],
            'peripheral_route' => ['nullable', Rule::in(['18', '20', '22', 'Permeable'])],
            'informed_by' => ['nullable', 'string', 'max:255'],
            'delivery' => ['nullable', 'string'],
            'plates_count' => ['nullable', 'integer', 'min:0'],
            'delivery_quantities' => ['nullable', 'array'],
            'delivery_quantities.*' => ['nullable', 'integer', 'min:0'],
            'cause' => ['nullable', 'string'],
            'symptomatology' => ['nullable', 'string'],
            'surgeries' => ['nullable', 'string', 'max:255'],
            'surgeries_detail' => ['nullable', 'string', 'max:255'],
            'delivery_options' => ['nullable', 'array'],
            'delivery_options.*' => ['nullable', Rule::in(['PLACAS', 'CD', 'INFORME'])],
            'medication' => ['nullable', 'string'],
            'antecedents' => ['nullable', 'string'],
            'medications' => ['nullable', 'array'],
            'medications.*' => ['nullable', 'string', 'max:255'],
            'allergy' => ['nullable', 'string', 'max:255'],
            'fasting' => ['nullable', Rule::in(['SI', 'NO'])],
            'creatinine' => ['nullable', 'string', 'max:255'],
            'delivery_media' => ['nullable', Rule::in(['CD', 'LINK', 'AMBOS'])],
            'consumables' => ['nullable', 'array'],
            'consumables.*.reagent_id' => ['required', 'exists:reagents,id'],
            'consumables.*.cantidad' => ['required', 'numeric', 'min:0'],
        ]);

        $order->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam', 'admissionForm']);
        $this->syncPrintableDocuments($order);
        $current = $order->fresh('admissionForm')->admissionForm?->data ?? [];
        $data['delivery_options'] = array_values($data['delivery_options'] ?? ['PLACAS', 'CD', 'INFORME']);
        $data['delivery_quantities'] = collect($data['delivery_quantities'] ?? [])->map(fn ($item) => $item === null || $item === '' ? '' : (int) $item)->all();
        $data['plates_count'] = $data['delivery_quantities']['PLACAS'] ?? ($data['plates_count'] ?? null);
        $data['delivery'] = implode(', ', $data['delivery_options']);
        if (($data['surgeries'] ?? '') !== 'Otros') $data['surgeries_detail'] = '';
        if (array_key_exists('medications', $data)) {
            $data['medications'] = collect($data['medications'])->map(fn ($item) => trim((string) $item))->filter()->values()->all();
            $data['medication'] = implode(PHP_EOL, $data['medications']);
        }
        $order->admissionForm()->updateOrCreate([], ['data' => array_merge($current, collect($data)->except('consumables')->all())]);
        if (array_key_exists('consumables', $data)) {
            $order->consumables()->delete();
            foreach (collect($data['consumables'] ?? [])->filter(fn ($row) => (float) ($row['cantidad'] ?? 0) > 0) as $row) {
                $order->consumables()->updateOrCreate(['reagent_id' => $row['reagent_id']], ['cantidad' => $row['cantidad']]);
            }
        }

        return redirect()->route('orders.ficha-ingreso.template', $order)->with('success', 'Ficha de ingreso guardada correctamente.');
    }

    public function fichaIngresoPdf(Order $order)
    {
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam', 'admissionForm']);
        $this->syncPrintableDocuments($order);
        $order->refresh()->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam', 'admissionForm']);
        $admissionData = $order->admissionForm?->data ?? [];
        $hasContrast = $order->orderExams->contains('tipo_contraste', 'Con contraste');
        $contrastConsumables = $this->triageConsumables($order);
        $medicosInformantes = $this->medicosInformantes();

        return Pdf::loadView('orders.pdfs.ficha-ingreso', compact('order', 'hasContrast', 'admissionData', 'contrastConsumables', 'medicosInformantes'))->setPaper('a4')->stream('ficha-ingreso-'.$order->id.'.pdf');
    }

    public function declaracionJuradaTemplate(Order $order): View
    {
        $order->load(['patient', 'orderExams.exam', 'swornDeclaration']);
        $this->syncPrintableDocuments($order);
        $order->refresh()->load(['patient', 'orderExams.exam', 'swornDeclaration']);
        $declarationData = $order->swornDeclaration?->data ?? [];

        return view('orders.templates.declaracion-jurada', compact('order', 'declarationData'));
    }

    public function updateDeclaracionJurada(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'patient_name' => ['nullable', 'string', 'max:255'],
            'patient_dni' => ['nullable', 'string', 'max:255'],
            'legal_representative_dni' => ['nullable', 'string', 'max:255'],
            'study' => ['nullable', 'string'],
            'day' => ['nullable', 'string', 'max:20'],
            'month' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'string', 'max:20'],
            'hour' => ['nullable', 'string', 'max:50'],
            'revocation' => ['nullable', 'string'],
        ]);

        $order->load(['patient', 'orderExams.exam', 'swornDeclaration']);
        $this->syncPrintableDocuments($order);
        $current = $order->fresh('swornDeclaration')->swornDeclaration?->data ?? [];
        $order->swornDeclaration()->updateOrCreate([], ['data' => array_merge($current, $data)]);

        return redirect()->route('orders.declaracion-jurada.template', $order)->with('success', 'Declaración jurada guardada correctamente.');
    }

    public function declaracionJuradaPdf(Order $order)
    {
        $order->load(['patient', 'orderExams.exam', 'swornDeclaration']);
        $this->syncPrintableDocuments($order);
        $order->refresh()->load(['patient', 'orderExams.exam', 'swornDeclaration']);
        $declarationData = $order->swornDeclaration?->data ?? [];

        return Pdf::loadView('orders.pdfs.declaracion-jurada', compact('order', 'declarationData'))->setPaper('a4')->stream('declaracion-jurada-'.$order->id.'.pdf');
    }


    private function syncPrintableDocuments(Order $order): void
    {
        $order->loadMissing(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam']);

        $hasContrast = $order->orderExams->contains('tipo_contraste', 'Con contraste');
        $examNames = $order->orderExams->pluck('exam.nombre_examen')->filter()->join(', ');
        $patientName = trim($order->patient->apellidos.' '.$order->patient->nombres);
        $patientAge = $order->patient->edad ?? ($order->patient->fecha_nacimiento?->age);

        $admissionDefaults = [
            'agreement' => $order->agreement?->nombre_institucion ?? 'PARTICULAR',
            'request_number' => $order->codigo_orden ?? (string) $order->id,
            'date' => $order->fecha_orden?->format('d/m/Y H:i'),
            'unit' => $order->unidad,
            'patient_name' => $patientName,
            'patient_dni' => $order->patient->dni,
            'patient_phone' => $order->patient->telefono,
            'patient_birthdate' => $order->patient->fecha_nacimiento?->format('d/m/Y'),
            'patient_age' => $patientAge,
            'requested_by' => $order->medicoSolicitante?->nombre_completo,
            'contrast_label' => $hasContrast ? 'CON CONTRASTE' : 'SIN CONTRASTE',
            'has_contrast' => $hasContrast,
            'study' => $examNames,
            'observations' => $order->observaciones,
            'rule_out' => $order->observaciones,
            'condition' => 'NORMAL',
            'provenance' => $order->agreement?->nombre_institucion ?? 'PARTICULAR',
            'peripheral_route' => '',
            'informed_by' => '',
            'delivery' => 'PLACAS, CD, INFORME',
            'delivery_options' => ['PLACAS', 'CD', 'INFORME'],
            'delivery_quantities' => [],
            'plates_count' => '',
            'cause' => '',
            'symptomatology' => '',
            'surgeries' => 'Ninguna',
            'surgeries_detail' => '',
            'medication' => '',
            'antecedents' => '',
            'medications' => [],
            'allergy' => '',
            'fasting' => '',
            'creatinine' => '',
        ];
        $order->admissionForm()->updateOrCreate([], [
            'data' => array_merge($admissionDefaults, $order->admissionForm?->data ?? []),
        ]);

        $now = now();
        $declarationDefaults = [
            'patient_name' => trim($order->patient->nombres.' '.$order->patient->apellidos),
            'patient_dni' => $order->patient->dni,
            'legal_representative_dni' => '',
            'study' => $examNames,
            'day' => $now->format('d'),
            'month' => $now->translatedFormat('F'),
            'year' => $now->format('Y'),
            'hour' => '',
            'revocation' => '',
        ];
        $order->swornDeclaration()->updateOrCreate([], [
            'data' => array_merge($declarationDefaults, $order->swornDeclaration?->data ?? []),
        ]);
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
        $technique = trim("Se realizó tomografía computarizada de {$region} mediante adquisición helicoidal/multicorte, con reconstrucciones multiplanares en planos axial, coronal y sagital.\n{$contrastTechnique}");
        $findings = <<<FINDINGS
Se evalúan las estructuras anatómicas incluidas en el campo de estudio.

[Describir hallazgos normales o patológicos según el estudio.]
FINDINGS;

        $impression = <<<IMPRESSION
1. [Conclusión principal del estudio.]
2. [Hallazgo secundario relevante, si existe.]
3. [Sugerencia de correlación clínica, laboratorio o estudios complementarios, si corresponde.]
IMPRESSION;

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

{$technique}

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
            [
                'titulo' => 'REPORTE DE TOMOGRAFÍA COMPUTARIZADA',
                'tecnica' => $technique,
                'informe' => trim($findings),
                'impresion' => trim($impression),
                'contenido' => $content,
                'medico_firmante_id' => $reportingDoctor?->id,
            ]
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
