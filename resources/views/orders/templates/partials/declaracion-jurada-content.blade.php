@php($declarationData = $declarationData ?? [])
<div class="mx-auto" style="max-width: 850px;">
    <h2 class="h4 text-center fw-bold mb-1">CONSENTIMIENTO INFORMADO PARA ESTUDIO DE TOMOGRAFÍA COMPUTARIZADA</h2>
    <p class="text-center small">(Este formato debe proveerse al paciente y/o representante legal para su información)</p>
    <h3 class="h6 fw-bold">RECOMENDACIONES PARA LLENAR EL FORMATO CORRECTAMENTE</h3>
    <p>El registro del consentimiento Informado, posterior a la información que debe dar el Médico Tratante, será efectuado por el paciente o su representante legal, sin borrones o enmendaduras y con la misma letra.</p>
    <p>
        Yo, <input class="form-control form-control-sm d-inline-block mx-1" style="width:260px" value="{{ $declarationData['patient_name'] ?? ($order->patient->nombres.' '.$order->patient->apellidos) }}"> con DNI Nº
        <input class="form-control form-control-sm d-inline-block mx-1" style="width:150px" value="{{ $declarationData['patient_dni'] ?? $order->patient->dni }}"> en mi condición de paciente/o representante legal del DNI Nº
        <input class="form-control form-control-sm d-inline-block mx-1" style="width:150px" value="{{ $declarationData['legal_representative_dni'] ?? '' }}"> autorizo a los profesionales Médico tratante, Médico Radiólogo, Tecnólogo Médico, Enfermera y Anestesiólogo, a efectuar los procedimientos necesarios para la realización del Estudio de Tomografía Computarizada.
    </p>
    <p><strong>Estudio(s):</strong> {{ $declarationData['study'] ?? $order->orderExams->pluck('exam.nombre_examen')->join(', ') }}.</p>
    <p>Manifiesto haber recibido la información siguiente:</p>
    <ol>
        <li>La Tomografía Computarizada es una técnica para el diagnóstico que utiliza rayos – X, y conlleva un riesgo potencial para mi condición clínica, propio de las radiaciones ionizantes.</li>
        <li>En caso de Embarazo el uso de radiación ionizante puede ocasionar daños al feto; por lo que su indicación en estado de gravidez, debe ser cuidadosamente justificada por el médico tratante.</li>
        <li>Los procedimientos adicionales son Uso de contraste y/o sedación que pueden ocasionar riesgos de reacción adversa no previsible o daño renal.</li>
    </ol>
    <p>Manifiesto que se han absuelto todas mis dudas sobre este procedimiento.</p>
    <p>Con pleno uso de mis facultades mentales y físicas (paciente/representante legal), habiendo ventajas y beneficios que sobrepasan los posibles riesgos respecto a mi enfermedad; y, luego de haber leído detenidamente y comprendido el contenido de las tres (03) declaraciones arriba descritas; doy fe que la información consignada fue efectuada por mi persona (paciente/representante legal), por lo que firmo el presente.</p>
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Día</label><input class="form-control" value="{{ $declarationData['day'] ?? now()->format('d') }}"></div>
        <div class="col-md-3"><label class="form-label">Mes</label><input class="form-control" value="{{ $declarationData['month'] ?? now()->translatedFormat('F') }}"></div>
        <div class="col-md-3"><label class="form-label">Año</label><input class="form-control" value="{{ $declarationData['year'] ?? now()->format('Y') }}"></div>
        <div class="col-md-3"><label class="form-label">Hora</label><input class="form-control" value="{{ $declarationData['hour'] ?? '' }}"></div>
    </div>
    <div class="row text-center mt-5">
        <div class="col"><div class="border rounded mx-auto mb-2" style="height:90px;max-width:220px"></div>Firma Paciente/Representante Legal</div>
        <div class="col"><div class="border rounded mx-auto mb-2" style="height:90px;max-width:220px"></div>Huella digital<br>DNI Nº <input class="form-control form-control-sm d-inline-block" style="width:160px" value="{{ $declarationData['patient_dni'] ?? $order->patient->dni }}"></div>
    </div>
    <div class="border rounded p-3 mt-4"><strong>REVOCATORIA:</strong><textarea class="form-control mt-2" rows="3" placeholder="Completar revocatoria si corresponde"></textarea></div>
</div>
