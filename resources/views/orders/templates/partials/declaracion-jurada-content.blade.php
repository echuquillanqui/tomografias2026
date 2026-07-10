<div class="mx-auto" style="max-width: 850px;">
    <h2 class="h4 text-center fw-bold mb-4">DECLARACIÓN JURADA / CONSENTIMIENTO</h2>
    <p>Yo, <span class="d-inline-block border-bottom" style="min-width:260px">&nbsp;</span>, identificado(a) con DNI N° <span class="d-inline-block border-bottom" style="min-width:180px">&nbsp;</span>, en calidad de padre/madre/apoderado(a) o paciente, autorizo la realización del estudio de tomografía computarizada a <strong>{{ $order->patient->nombres }} {{ $order->patient->apellidos }}</strong>, DNI N° <strong>{{ $order->patient->dni }}</strong>.</p>
    <p><strong>Estudio(s):</strong> {{ $order->orderExams->pluck('exam.nombre_examen')->join(', ') }}.</p>
    <p>Declaro haber recibido información clara sobre el procedimiento, sus beneficios, riesgos y alternativas, y asumo la responsabilidad de la autorización otorgada.</p>
    <div class="row g-3 my-3"><div class="col-md-4">Parentesco: ______________</div><div class="col-md-4">Teléfono: ______________</div><div class="col-md-4">Hora: ______________</div></div>
    <p>Puno, {{ now()->format('d') }} de {{ now()->translatedFormat('F') }} de {{ now()->format('Y') }}.</p>
    <div class="border rounded mx-auto mt-4" style="width:140px;height:110px"></div>
    <p class="text-center">Firma y huella<br>DNI N° __________________</p>
    <div class="border rounded p-3 mt-4"><strong>REVOCATORIA:</strong><br>Yo, ________________________________, revoco el consentimiento otorgado, asumiendo los riesgos y consecuencias de mi decisión.<br><br>Fecha: __________________ &nbsp;&nbsp; Firma: __________________</div>
</div>
