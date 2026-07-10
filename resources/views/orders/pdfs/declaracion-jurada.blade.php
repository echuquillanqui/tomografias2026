@php($declarationData = $declarationData ?? [])
<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:11px;line-height:1.45}.title{text-align:center;font-weight:bold;font-size:14px}.subtitle{text-align:center;font-size:10px}.line{border-bottom:1px dotted #000;display:inline-block;min-width:140px}.sig{width:150px;height:80px;border:1px solid #333;margin:18px auto 5px}.small{font-size:10px}</style></head><body>
<div class="title">CONSENTIMIENTO INFORMADO PARA ESTUDIO DE TOMOGRAFÍA COMPUTARIZADA</div>
<p class="subtitle">(Este formato debe proveerse al paciente y/o representante legal para su información)</p>
<p><b>RECOMENDACIONES PARA LLENAR EL FORMATO CORRECTAMENTE</b></p>
<p>El registro del consentimiento Informado, posterior a la información que debe dar el Médico Tratante, será efectuado por el paciente o su representante legal, sin borrones o enmendaduras y con la misma letra.</p>
<p>Yo, <span class="line" style="min-width:230px">{{ $declarationData['patient_name'] ?? ($order->patient->nombres.' '.$order->patient->apellidos) }}</span> con DNI Nº <span class="line">{{ $declarationData['patient_dni'] ?? $order->patient->dni }}</span> en mi condición de paciente/o representante legal del DNI Nº <span class="line">{{ $declarationData['legal_representative_dni'] ?? '&nbsp;' }}</span> autorizo a los profesionales Médico tratante, Médico Radiólogo, Tecnólogo Médico, Enfermera y Anestesiólogo, a efectuar los procedimientos necesarios para la realización del Estudio de Tomografía Computarizada. Manifiesto haber recibido la información siguiente:</p>
<p><b>Estudio(s):</b> {{ $declarationData['study'] ?? $order->orderExams->pluck('exam.nombre_examen')->join(', ') }}.</p>
<ol>
<li>La Tomografía Computarizada es una técnica para el diagnóstico que utiliza rayos – X, y conlleva un riesgo potencial para mi condición clínica, propio de las radiaciones ionizantes.</li>
<li>En caso de Embarazo el uso de radiación ionizante puede ocasionar daños al feto; por lo que su indicación en estado de gravidez, debe ser cuidadosamente justificada por el médico tratante.</li>
<li>Los procedimientos adicionales son Uso de contraste y/o sedación que pueden ocasionar riesgos de reacción adversa no previsible o daño renal.</li>
</ol>
<p>Manifiesto que se han absuelto todas mis dudas sobre este procedimiento.</p>
<p>Con pleno uso de mis facultades mentales y físicas (paciente/representante legal), habiendo ventajas y beneficios que sobrepasan los posibles riesgos respecto a mi enfermedad; y, luego de haber leído detenidamente y comprendido el contenido de las tres (03) declaraciones arriba descritas; doy fe que la información consignada fue efectuada por mi persona (paciente/representante legal), por lo que firmo el presente.</p>
<p>Puno, <span class="line" style="min-width:35px">{{ $declarationData['day'] ?? now()->format('d') }}</span> de <span class="line">{{ $declarationData['month'] ?? now()->translatedFormat('F') }}</span> de <span class="line" style="min-width:55px">{{ $declarationData['year'] ?? now()->format('Y') }}</span>. Hora: <span class="line">{{ $declarationData['hour'] ?? '&nbsp;' }}</span></p>
<table style="width:100%;margin-top:30px"><tr><td style="text-align:center;width:50%"><div class="sig"></div>Firma Paciente/Representante Legal</td><td style="text-align:center;width:50%"><div class="sig"></div>(Huella digital)<br>DNI Nº <span class="line">{{ $declarationData['patient_dni'] ?? $order->patient->dni }}</span></td></tr></table>
<p><b>REVOCATORIA:</b></p>
<p class="small">__________________________________________________________________________________________</p>
<p class="small">__________________________________________________________________________________________</p>
</body></html>
