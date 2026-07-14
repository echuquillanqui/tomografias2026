@php
    $admissionData = $admissionData ?? [];
    $deliveryItems = ['PLACAS', 'CD', 'INFORME'];
    $deliveryOptions = old('delivery_options', $admissionData['delivery_options'] ?? $deliveryItems);
    $deliveryOptions = empty($deliveryOptions) ? $deliveryItems : (array) $deliveryOptions;
    $deliveryQuantities = old('delivery_quantities', $admissionData['delivery_quantities'] ?? []);
    $deliveryQuantities = is_array($deliveryQuantities) ? $deliveryQuantities : [];
@endphp
<div class="text-center mb-3">
    <h2 class="fw-bold text-decoration-underline">FICHA DE INGRESO</h2>
    <div class="fw-bold"><input name="agreement" class="form-control form-control-sm text-center fw-bold" value="{{ old('agreement', $admissionData['agreement'] ?? ($order->agreement->nombre_institucion ?? 'PARTICULAR')) }}"></div>
</div>
<table class="table table-bordered align-middle">
    <tbody>
        <tr>
            <th>N° de solicitud</th><td><input name="request_number" class="form-control form-control-sm" value="{{ old('request_number', $admissionData['request_number'] ?? ($order->codigo_orden ?? $order->id)) }}"></td>
            <th>Fecha</th><td><input name="date" class="form-control form-control-sm" value="{{ old('date', $admissionData['date'] ?? $order->fecha_orden->format('d/m/Y')) }}"></td>
            <th>Unidad</th><td><input name="unit" class="form-control form-control-sm" value="{{ old('unit', $admissionData['unit'] ?? $order->unidad) }}"></td>
        </tr>
        <tr><th>Paciente</th><td colspan="3"><input name="patient_name" class="form-control form-control-sm" value="{{ old('patient_name', $admissionData['patient_name'] ?? ($order->patient->apellidos.' '.$order->patient->nombres)) }}"></td><th>DNI</th><td><input name="patient_dni" class="form-control form-control-sm" value="{{ old('patient_dni', $admissionData['patient_dni'] ?? $order->patient->dni) }}"></td></tr>
        <tr>
            <th>Celular</th><td><input name="patient_phone" class="form-control form-control-sm" value="{{ old('patient_phone', $admissionData['patient_phone'] ?? ($order->patient->telefono ?? '—')) }}"></td>
            <th>F. nacimiento</th><td><input name="patient_birthdate" class="form-control form-control-sm" value="{{ old('patient_birthdate', $admissionData['patient_birthdate'] ?? (optional($order->patient->fecha_nacimiento)->format('d/m/Y') ?? '—')) }}"></td>
            <th>Edad</th><td><input name="patient_age" class="form-control form-control-sm" value="{{ old('patient_age', $admissionData['patient_age'] ?? ($order->patient->edad ?? ($order->patient->fecha_nacimiento?->age ?? '—'))) }}"></td>
        </tr>
        <tr><th>Solicitado por</th><td colspan="3"><input name="requested_by" class="form-control form-control-sm" value="{{ old('requested_by', $admissionData['requested_by'] ?? ($order->medicoSolicitante?->nombre_completo ?? '—')) }}"></td><th>Contraste</th><td><input name="contrast_label" class="form-control form-control-sm" value="{{ old('contrast_label', $admissionData['contrast_label'] ?? ($hasContrast ? 'CON CONTRASTE' : 'SIN CONTRASTE')) }}"></td></tr>
        <tr><th>Condición</th><td colspan="5"><input name="condition" class="form-control form-control-sm" value="{{ old('condition', $admissionData['condition'] ?? ($order->agreement?->nombre_institucion ?? 'PARTICULAR')) }}"></td></tr>
        <tr><th>Estudio solicitado</th><td colspan="5"><textarea name="study" class="form-control form-control-sm" rows="2">{{ old('study', $admissionData['study'] ?? $order->orderExams->pluck('exam.nombre_examen')->join(', ')) }}</textarea></td></tr>
        <tr><th>Descartar</th><td colspan="5"><textarea name="rule_out" class="form-control form-control-sm" rows="2">{{ old('rule_out', $admissionData['rule_out'] ?? ($admissionData['observations'] ?? ($order->observaciones ?? '—'))) }}</textarea></td></tr>
    </tbody>
</table>
<h5 class="bg-primary text-white text-center py-2 mt-4">ANTECEDENTES</h5>
<div class="row g-3">
    <div class="col-md-6"><label class="form-label fw-bold">Causa</label><textarea name="cause" class="form-control" rows="2" placeholder="Completar causa">{{ old('cause', $admissionData['cause'] ?? '') }}</textarea></div>
    <div class="col-md-6"><label class="form-label fw-bold">Sintomatología</label><textarea name="symptomatology" class="form-control" rows="2" placeholder="Completar sintomatología">{{ old('symptomatology', $admissionData['symptomatology'] ?? '') }}</textarea></div>
    <div class="col-md-6"><label class="form-label fw-bold">Intervenciones quirúrgicas</label><select name="surgeries" class="form-select" onchange="document.getElementById('surgeries-detail').classList.toggle('d-none', this.value !== 'Otros')"><option value="Ninguna" @selected(old('surgeries', $admissionData['surgeries'] ?? 'Ninguna') === 'Ninguna')>Ninguna</option><option value="Otros" @selected(old('surgeries', $admissionData['surgeries'] ?? 'Ninguna') === 'Otros')>Otros</option></select><input id="surgeries-detail" name="surgeries_detail" class="form-control mt-2 {{ old('surgeries', $admissionData['surgeries'] ?? 'Ninguna') === 'Otros' ? '' : 'd-none' }}" value="{{ old('surgeries_detail', $admissionData['surgeries_detail'] ?? '') }}" placeholder="Especificar intervención"></div>
    <div class="col-md-6"><label class="form-label fw-bold">Medicación</label><textarea name="medication" class="form-control" rows="2" placeholder="Completar medicación">{{ old('medication', $admissionData['medication'] ?? '') }}</textarea></div>
    <div class="col-12"><label class="form-label fw-bold">Antecedentes</label><textarea name="antecedents" class="form-control" rows="3" placeholder="Completar antecedentes del paciente">{{ old('antecedents', $admissionData['antecedents'] ?? '') }}</textarea></div>
    <div class="col-md-6"><label class="form-label fw-bold">Informado por</label><input name="informed_by" class="form-control" value="{{ old('informed_by', $admissionData['informed_by'] ?? '') }}" placeholder="Nombre de quien informa"></div>
    <div class="col-md-6">
        <label class="form-label fw-bold">Se entrega</label>
        <div class="border rounded p-3 bg-light">
            <div class="row g-2 fw-bold small text-uppercase text-muted mb-1">
                <div class="col-7">Documento</div>
                <div class="col-5">Número</div>
            </div>
            @foreach(($deliveryItems ?? ['PLACAS', 'CD', 'INFORME']) as $option)
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-7">
                        <div class="form-check fs-5">
                            <input class="form-check-input" type="checkbox" name="delivery_options[]" value="{{ $option }}" id="delivery{{ $option }}" @checked(in_array($option, ($deliveryOptions ?? ['PLACAS', 'CD', 'INFORME']), true))>
                            <label class="form-check-label fw-bold" for="delivery{{ $option }}">{{ $option }}</label>
                        </div>
                    </div>
                    <div class="col-5">
                        <input name="delivery_quantities[{{ $option }}]" type="number" min="0" step="1" class="form-control form-control-lg" value="{{ ($deliveryQuantities ?? [])[$option] ?? ($option === 'PLACAS' ? old('plates_count', $admissionData['plates_count'] ?? '') : '') }}" placeholder="N°">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@if($hasContrast)
    <h5 class="bg-primary text-white text-center py-2 mt-4">USO INTERNO / DATOS PARA CONTRASTE</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">Alergia</label><input name="allergy" class="form-control" value="{{ old('allergy', $admissionData['allergy'] ?? '') }}" placeholder="Alergia probable/medicamento"></div>
        <div class="col-md-4"><label class="form-label fw-bold">Ayunas</label><select name="fasting" class="form-select"><option value=""></option><option @selected(old('fasting', $admissionData['fasting'] ?? '') === 'Sí')>Sí</option><option @selected(old('fasting', $admissionData['fasting'] ?? '') === 'No')>No</option></select></div>
        <div class="col-md-4"><label class="form-label fw-bold">Creatinina</label><input name="creatinine" class="form-control" value="{{ old('creatinine', $admissionData['creatinine'] ?? '') }}" placeholder="Valor de creatinina"></div>
        <div class="col-md-4"><label class="form-label fw-bold">Vía periférica</label><input name="peripheral_route" class="form-control" value="{{ old('peripheral_route', $admissionData['peripheral_route'] ?? '') }}" placeholder="Detalle de vía periférica"></div>
    </div>
@endif
<div class="row text-center mt-5"><div class="col"><div class="border rounded mx-auto mb-2" style="height:90px;max-width:220px"></div>Firma del paciente</div><div class="col"><div class="border rounded mx-auto mb-2" style="height:90px;max-width:220px"></div>Huella del paciente</div></div>
