<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;line-height:1.45}.title{text-align:center;font-weight:bold;font-size:15px}.line{border-bottom:1px dotted #000;display:inline-block;min-width:180px}.box{border:1px solid #265dba;border-radius:8px;padding:10px}.sig{width:120px;height:120px;border:1px solid #265dba;margin:20px auto 5px}</style></head><body>
<div class="title">DECLARACIÓN JURADA / CONSENTIMIENTO PARA MENOR DE EDAD</div>
<p>Yo, <span class="line">&nbsp;</span>, identificado(a) con DNI N° <span class="line">&nbsp;</span>, en calidad de padre/madre/apoderado(a) del menor <b>{{ $order->patient->nombres }} {{ $order->patient->apellidos }}</b>, DNI N° <b>{{ $order->patient->dni }}</b>, autorizo la realización del estudio de tomografía computarizada solicitado.</p>
<p><b>Estudio(s):</b> {{ $order->orderExams->pluck('exam.nombre_examen')->join(', ') }}.</p>
<p>Declaro haber recibido información clara sobre el procedimiento, sus beneficios, riesgos y alternativas, y asumo la responsabilidad de la autorización otorgada para el menor.</p>
<p>Datos adicionales a completar: parentesco <span class="line">&nbsp;</span>, teléfono <span class="line">&nbsp;</span>, dirección <span class="line">&nbsp;</span>.</p>
<p>Puno, {{ now()->format('d') }} de {{ now()->translatedFormat('F') }} de {{ now()->format('Y') }}. Hora: <span class="line">&nbsp;</span></p>
<div class="sig"></div><p style="text-align:center">Firma y huella del representante legal<br>DNI N° <span class="line">&nbsp;</span></p>
<div class="box"><b>REVOCATORIA:</b><br>Yo, <span class="line">&nbsp;</span>, revoco el consentimiento otorgado, asumiendo los riesgos y consecuencias de mi decisión respecto a la salud del menor.<br><br>Fecha: <span class="line">&nbsp;</span><br><br>Firma: <span class="line">&nbsp;</span></div>
</body></html>
