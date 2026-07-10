@php($admissionData = $admissionData ?? [])
<div class="text-center mb-3">
    <h2 class="fw-bold text-decoration-underline">FICHA DE INGRESO</h2>
    <div class="fw-bold">{{ $admissionData['agreement'] ?? ($order->agreement->nombre_institucion ?? 'PARTICULAR') }}</div>
</div>
<table class="table table-bordered align-middle">
    <tbody>
        <tr><th>N° de solicitud</th><td>{{ $admissionData['request_number'] ?? ($order->codigo_orden ?? $order->id) }}</td><th>Fecha</th><td>{{ $admissionData['date'] ?? $order->fecha_orden->format('d/m/Y') }}</td><th>Unidad</th><td>{{ $admissionData['unit'] ?? $order->unidad }}</td></tr>
        <tr><th>Paciente</th><td colspan="3">{{ $admissionData['patient_name'] ?? ($order->patient->apellidos.' '.$order->patient->nombres) }}</td><th>DNI</th><td>{{ $admissionData['patient_dni'] ?? $order->patient->dni }}</td></tr>
        <tr><th>Celular</th><td>{{ $admissionData['patient_phone'] ?? ($order->patient->telefono ?? '—') }}</td><th>F. nacimiento</th><td>{{ $admissionData['patient_birthdate'] ?? (optional($order->patient->fecha_nacimiento)->format('d/m/Y') ?? '—') }}</td><th>Edad</th><td>{{ $admissionData['patient_age'] ?? ($order->patient->edad ?? ($order->patient->fecha_nacimiento?->age ?? '—')) }}</td></tr>
        <tr><th>Solicitado por</th><td colspan="3">{{ $admissionData['requested_by'] ?? ($order->medicoSolicitante?->nombre_completo ?? '—') }}</td><th>Contraste</th><td>{{ $admissionData['contrast_label'] ?? ($hasContrast ? 'CON CONTRASTE' : 'SIN CONTRASTE') }}</td></tr>
        <tr><th>Estudio solicitado</th><td colspan="5">{{ $admissionData['study'] ?? $order->orderExams->pluck('exam.nombre_examen')->join(', ') }}</td></tr>
        <tr><th>Observaciones</th><td colspan="5">{{ $admissionData['observations'] ?? ($order->observaciones ?? '—') }}</td></tr>
    </tbody>
</table>
<div class="row g-3">
    @foreach(['Causa', 'Sintomatología', 'Intervenciones quirúrgicas', 'Medicación'] as $field)
        <div class="col-md-6">
            <label class="form-label fw-bold">{{ $field }}</label>
            <textarea class="form-control" rows="2" placeholder="Completar {{ strtolower($field) }}"></textarea>
        </div>
    @endforeach
</div>
@if($hasContrast)
    <h5 class="bg-primary text-white text-center py-2 mt-4">DATOS PARA CONTRASTE</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">Alergia</label><input class="form-control" placeholder="Alergia probable/medicamento"></div>
        <div class="col-md-4"><label class="form-label fw-bold">Ayunas</label><select class="form-select"><option value=""></option><option>Sí</option><option>No</option></select></div>
        <div class="col-md-4"><label class="form-label fw-bold">Creatinina</label><input class="form-control" placeholder="Valor de creatinina"></div>
    </div>
@endif
<div class="row text-center mt-5"><div class="col"><div class="border rounded mx-auto mb-2" style="height:90px;max-width:220px"></div>Firma del paciente</div><div class="col"><div class="border rounded mx-auto mb-2" style="height:90px;max-width:220px"></div>Huella del paciente</div></div>
