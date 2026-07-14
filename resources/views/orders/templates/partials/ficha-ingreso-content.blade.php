@php
    $admissionData = $admissionData ?? [];
    $deliveryItems = ['PLACAS', 'INFORME'];
    $deliveryMediaOptions = ['CD', 'LINK', 'AMBOS'];
    $deliveryOptions = old('delivery_options', $admissionData['delivery_options'] ?? $deliveryItems);
    $deliveryOptions = empty($deliveryOptions) ? $deliveryItems : array_values(array_intersect((array) $deliveryOptions, $deliveryItems));
    $deliveryQuantities = old('delivery_quantities', $admissionData['delivery_quantities'] ?? []);
    $deliveryQuantities = is_array($deliveryQuantities) ? $deliveryQuantities : [];
    $formatDeliveryQuantity = function ($option) use ($deliveryQuantities, $admissionData) {
        $value = ($deliveryQuantities ?? [])[$option] ?? ($option === 'PLACAS' ? old('plates_count', $admissionData['plates_count'] ?? null) : null);

        return ($value === null || $value === '') ? '' : $value;
    };
    $rawPatientAge = old('patient_age', $admissionData['patient_age'] ?? ($order->patient->edad ?? ($order->patient->fecha_nacimiento?->age ?? '—')));
    $patientAgeLabel = is_numeric($rawPatientAge) ? $rawPatientAge.' años' : $rawPatientAge;
@endphp
<div class="text-center mb-3">
    <h2 class="fw-bold text-decoration-underline">FICHA DE INGRESO</h2>
    <div class="fw-bold"><input name="agreement" class="form-control form-control-sm text-center fw-bold" value="{{ old('agreement', $admissionData['agreement'] ?? ($order->agreement->nombre_institucion ?? 'PARTICULAR')) }}"></div>
</div>
<table class="table table-bordered align-middle">
    <tbody>
        <tr>
            <th>N° de solicitud</th><td colspan="2"><input name="request_number" class="form-control form-control-sm" value="{{ old('request_number', $admissionData['request_number'] ?? ($order->codigo_orden ?? $order->id)) }}"></td>
            <th>Fecha y hora de atención</th><td colspan="2"><input name="date" class="form-control form-control-sm" value="{{ old('date', $admissionData['date'] ?? $order->fecha_orden->format('d/m/Y H:i')) }}"></td>
        </tr>
        <tr><th>Paciente</th><td colspan="3"><input name="patient_name" class="form-control form-control-sm" value="{{ old('patient_name', $admissionData['patient_name'] ?? ($order->patient->apellidos.' '.$order->patient->nombres)) }}"></td><th>DNI</th><td><input name="patient_dni" class="form-control form-control-sm" value="{{ old('patient_dni', $admissionData['patient_dni'] ?? $order->patient->dni) }}"></td></tr>
        <tr>
            <th>F. nacimiento</th><td><input name="patient_birthdate" class="form-control form-control-sm" value="{{ old('patient_birthdate', $admissionData['patient_birthdate'] ?? (optional($order->patient->fecha_nacimiento)->format('d/m/Y') ?? '—')) }}"></td>
            <th>Edad</th><td><input name="patient_age" class="form-control form-control-sm" value="{{ $patientAgeLabel }}" placeholder="30 años"></td>
            <th>Celular</th><td><input name="patient_phone" class="form-control form-control-sm" value="{{ old('patient_phone', $admissionData['patient_phone'] ?? ($order->patient->telefono ?? '—')) }}"></td>
        </tr>
        <tr><th>Solicitado por</th><td colspan="5"><input name="requested_by" class="form-control form-control-sm" value="{{ old('requested_by', $admissionData['requested_by'] ?? ($order->medicoSolicitante?->nombre_completo ?? '—')) }}"></td></tr>
        <tr>
            <th>Estudio solicitado</th><td colspan="3"><textarea name="study" class="form-control form-control-lg fw-bold" rows="2" placeholder="Ej.: TEM cerebral">{{ old('study', $admissionData['study'] ?? $order->orderExams->pluck('exam.nombre_examen')->join(', ')) }}</textarea></td>
            <th>Contraste</th><td><input name="contrast_label" class="form-control form-control-lg fw-bold text-danger text-center" value="{{ old('contrast_label', $admissionData['contrast_label'] ?? ($hasContrast ? 'CON CONTRASTE' : 'SIN CONTRASTE')) }}"></td>
        </tr>
        <tr><th>Descartar</th><td colspan="5"><textarea name="rule_out" class="form-control form-control-sm" rows="2">{{ old('rule_out', $admissionData['rule_out'] ?? ($admissionData['observations'] ?? ($order->observaciones ?? '—'))) }}</textarea></td></tr>
    </tbody>
</table>
<h5 class="bg-primary text-white text-center py-2 mt-4">ANAMNESIS</h5>
<div class="row g-3">
    <div class="col-md-6"><label class="form-label fw-bold">Causa</label><textarea name="cause" class="form-control" rows="2" placeholder="Completar causa">{{ old('cause', $admissionData['cause'] ?? '') }}</textarea></div>
    <div class="col-md-6"><label class="form-label fw-bold">Sintomatología</label><textarea name="symptomatology" class="form-control" rows="2" placeholder="Completar sintomatología">{{ old('symptomatology', $admissionData['symptomatology'] ?? '') }}</textarea></div>
    <div class="col-md-6"><label class="form-label fw-bold">Intervenciones quirúrgicas</label><select name="surgeries" class="form-select" onchange="document.getElementById('surgeries-detail').classList.toggle('d-none', this.value !== 'Otros')"><option value="Ninguna" @selected(old('surgeries', $admissionData['surgeries'] ?? 'Ninguna') === 'Ninguna')>Ninguna</option><option value="Otros" @selected(old('surgeries', $admissionData['surgeries'] ?? 'Ninguna') === 'Otros')>Otros</option></select><input id="surgeries-detail" name="surgeries_detail" class="form-control mt-2 {{ old('surgeries', $admissionData['surgeries'] ?? 'Ninguna') === 'Otros' ? '' : 'd-none' }}" value="{{ old('surgeries_detail', $admissionData['surgeries_detail'] ?? '') }}" placeholder="Especificar intervención"></div>
    <div class="col-md-6"><label class="form-label fw-bold">Medicación</label><textarea name="medication" class="form-control" rows="2" placeholder="Completar medicación">{{ old('medication', $admissionData['medication'] ?? '') }}</textarea></div>
    <div class="col-12"><label class="form-label fw-bold">Antecedentes</label><textarea name="antecedents" class="form-control" rows="3" placeholder="Completar antecedentes del paciente">{{ old('antecedents', $admissionData['antecedents'] ?? '') }}</textarea></div>
    <div class="col-md-6">
        <label class="form-label fw-bold">Se entrega</label>
        <div class="border rounded p-3 bg-light">
            <div class="row g-2 fw-bold small text-uppercase text-muted mb-1">
                <div class="col-5">Documento</div>
                <div class="col-3 text-center">Marca</div>
                <div class="col-4">Cantidad</div>
            </div>
            @foreach(($deliveryItems ?? ['PLACAS', 'INFORME']) as $option)
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-5 fw-bold fs-5">{{ $option }}</div>
                    <div class="col-3 text-center">
                        <input class="form-check-input fs-5 m-0" type="checkbox" name="delivery_options[]" value="{{ $option }}" id="delivery{{ $option }}" aria-label="Marcar {{ $option }}" @checked(in_array($option, ($deliveryOptions ?? ['PLACAS', 'INFORME']), true))>
                    </div>
                    <div class="col-4">
                        <input name="delivery_quantities[{{ $option }}]" type="number" min="0" step="1" class="form-control form-control-sm text-center" value="{{ $formatDeliveryQuantity($option) }}" placeholder="0">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="col-md-3"><label class="form-label fw-bold">CD / Link</label><select name="delivery_media" class="form-select"><option value=""></option>@foreach($deliveryMediaOptions as $media)<option value="{{ $media }}" @selected(old('delivery_media', $admissionData['delivery_media'] ?? '') === $media)>{{ $media }}</option>@endforeach</select></div>
</div>
@if($hasContrast)
    <h5 class="bg-primary text-white text-center py-2 mt-4">INSUMOS Y MATERIALES DE USO INTERNO PARA ESTUDIO CON CONTRASTE</h5>
    <div class="table-responsive mb-3"><table class="table table-bordered table-sm align-middle"><thead><tr><th>Insumo / material</th><th style="width:140px">Cantidad</th><th style="width:100px">Unidad</th><th style="width:240px">Bránula</th></tr></thead><tbody>@forelse(($contrastConsumables ?? []) as $index => $consumable)<tr><td>{{ $consumable['name'] }}<input type="hidden" name="consumables[{{ $index }}][reagent_id]" value="{{ $consumable['reagent_id'] }}"></td><td><input name="consumables[{{ $index }}][cantidad]" type="number" min="0" step="0.01" class="form-control form-control-sm" value="{{ $consumable['cantidad'] }}"></td><td>{{ $consumable['unit'] ?? '' }}</td>@if($loop->first)<td rowspan="{{ max(count($contrastConsumables ?? []), 1) }}"><div class="input-group" x-data="{v: '{{ old('peripheral_route', $admissionData['peripheral_route'] ?? '') }}'}"><span class="input-group-text" x-text="['18','20','22'].includes(v) ? 'N°' : ''"></span><select name="peripheral_route" class="form-select" x-model="v"><option value=""></option><option value="18" @selected(old('peripheral_route', $admissionData['peripheral_route'] ?? '') === '18')>18</option><option value="20" @selected(old('peripheral_route', $admissionData['peripheral_route'] ?? '') === '20')>20</option><option value="22" @selected(old('peripheral_route', $admissionData['peripheral_route'] ?? '') === '22')>22</option><option value="Permeable" @selected(old('peripheral_route', $admissionData['peripheral_route'] ?? '') === 'Permeable')>Permeable</option></select></div></td>@endif</tr>@empty<tr><td colspan="3" class="text-muted">Sin insumos precargados.</td><td><div class="input-group" x-data="{v: '{{ old('peripheral_route', $admissionData['peripheral_route'] ?? '') }}'}"><span class="input-group-text" x-text="['18','20','22'].includes(v) ? 'N°' : ''"></span><select name="peripheral_route" class="form-select" x-model="v"><option value=""></option><option value="18" @selected(old('peripheral_route', $admissionData['peripheral_route'] ?? '') === '18')>18</option><option value="20" @selected(old('peripheral_route', $admissionData['peripheral_route'] ?? '') === '20')>20</option><option value="22" @selected(old('peripheral_route', $admissionData['peripheral_route'] ?? '') === '22')>22</option><option value="Permeable" @selected(old('peripheral_route', $admissionData['peripheral_route'] ?? '') === 'Permeable')>Permeable</option></select></div></td></tr>@endforelse</tbody></table></div>
    <h5 class="bg-primary text-white text-center py-2 mt-4">USO INTERNO / DATOS PARA CONTRASTE</h5>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label fw-bold">Alergia probable/medicamento</label><textarea name="allergy" class="form-control" rows="2" placeholder="Alergia probable/medicamento">{{ old('allergy', $admissionData['allergy'] ?? '') }}</textarea></div>
        <div class="col-md-4"><label class="form-label fw-bold">¿Está en ayunas?</label><select name="fasting" class="form-select"><option value=""></option><option value="SI" @selected(old('fasting', $admissionData['fasting'] ?? '') === 'SI')>SI</option><option value="NO" @selected(old('fasting', $admissionData['fasting'] ?? '') === 'NO')>NO</option></select></div>
        <div class="col-md-8"><label class="form-label fw-bold">Prueba de creatinina</label><input name="creatinine" class="form-control" value="{{ old('creatinine', $admissionData['creatinine'] ?? '') }}" placeholder="Valor de creatinina"></div>
        <div class="col-md-4"><label class="form-label fw-bold">Informado por</label><select name="informed_by" class="form-select"><option value=""></option>@foreach(($medicosInformantes ?? collect()) as $medico)<option value="{{ $medico->nombre_completo }}" @selected(old('informed_by', $admissionData['informed_by'] ?? '') === $medico->nombre_completo)>{{ $medico->nombre_completo }}</option>@endforeach</select></div>
    </div>
@endif
<div class="row text-center mt-5"><div class="col"><div class="border rounded mx-auto mb-2" style="height:90px;max-width:220px"></div>Firma del paciente</div><div class="col"><div class="border rounded mx-auto mb-2" style="height:130px;max-width:85px"></div>Huella del paciente</div></div>
