<!doctype html><html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#003b75}.title{text-align:center;font-size:18px;font-weight:bold;text-decoration:underline}.box{border:1px solid #1f6fb2;margin-bottom:6px}.row{display:table;width:100%;table-layout:fixed}.cell{display:table-cell;border-right:1px solid #1f6fb2;border-bottom:1px solid #1f6fb2;padding:4px}.cell:last-child{border-right:0}.label{font-weight:bold;color:#0057a8}.yellow{background:#fff200}.head{background:#0c55a2;color:white;text-align:center;font-weight:bold;padding:4px}.red{color:red;font-weight:bold}.sig{height:70px;border:1px solid #1f6fb2;border-radius:8px}.muted{color:#666}.full{min-height:34px;padding:5px;border-bottom:1px solid #1f6fb2}.section-title{background:#0c55a2;color:white;text-align:center;font-weight:bold;padding:4px;margin-top:8px}
</style></head><body>
<div class="title">FICHA DE INGRESO</div>
<div style="text-align:center;font-weight:bold">PARTICULAR</div>
<div class="box">
 <div class="row"><div class="cell"><span class="label">N° de Solicitud:</span> {{ $order->codigo_orden ?? $order->id }}</div><div class="cell"><span class="label">Fecha - Hora de atención:</span> {{ $order->fecha_orden->format('d/m/Y') }}</div><div class="cell yellow"><span class="label">Unidad:</span> {{ $order->unidad }}</div></div>
 <div class="head">DATOS DEL PACIENTE</div>
 <div class="row"><div class="cell"><span class="label">Nombres:</span> {{ $order->patient->apellidos }} {{ $order->patient->nombres }}</div><div class="cell"><span class="label">DNI:</span> {{ $order->patient->dni }}</div><div class="cell"><span class="label">Cel:</span> {{ $order->patient->telefono ?? '—' }}</div></div>
 <div class="row"><div class="cell"><span class="label">Fecha de nacimiento:</span> {{ optional($order->patient->fecha_nacimiento)->format('d/m/Y') ?? '—' }}</div><div class="cell"><span class="label">Edad:</span> {{ $order->patient->edad ?? ($order->patient->fecha_nacimiento?->age ?? '—') }}</div><div class="cell"><span class="label">Años</span></div></div>
 <div class="row"><div class="cell yellow"><span class="label">Solicitado por:</span> {{ $order->medicoSolicitante?->nombre_completo ?? '—' }}</div><div class="cell"><span class="label">Condición:</span> {{ $order->agreement->nombre_institucion }}</div><div class="cell"><span class="red">{{ $hasContrast ? 'CON CONTRASTE' : 'SIN CONTRASTE' }}</span></div></div>
</div>
<div class="section-title">PROCEDENCIA</div><div class="full"><b>Estudio solicitado:</b> {{ $order->orderExams->pluck('exam.nombre_examen')->join(', ') }}<br><b>Observaciones:</b> {{ $order->observaciones ?? '—' }}</div>
<div class="section-title">ANTECEDENTES</div>
<div class="full"><b>Causa:</b> ________________________________________________________________________________</div>
<div class="full"><b>Sintomatología:</b> _______________________________________________________________________</div>
<div class="full"><b>Intervenciones quirúrgicas:</b> _____________________________________________________________</div>
<div class="full"><b>Medicación:</b> ____________________________________________________________________________</div>
@if($hasContrast)
<div class="section-title">DATOS PARA CONTRASTE</div>
<div class="row box"><div class="cell"><span class="label">Alergia probable/medicamento:</span> ____________</div><div class="cell"><span class="label">¿Está en ayunas?</span> Sí ( ) No ( )</div><div class="cell"><span class="label">Hace 4 horas:</span> Sí ( ) No ( )</div></div>
<div class="row box"><div class="cell"><span class="label">Prueba de creatinina:</span> ______</div><div class="cell"><span class="label">Valor:</span> ______</div><div class="cell"><span class="label">Fecha:</span> ____/____/______</div></div>
<div class="section-title">DOCUMENTOS / ENTREGA</div><div class="full">CD ( ) &nbsp;&nbsp; Informe ( ) &nbsp;&nbsp; Bolsa de cortesía ( )</div>
@endif
<br><br><div class="row"><div class="cell" style="border:0;text-align:center"><div class="sig"></div>FIRMA DEL PACIENTE</div><div class="cell" style="border:0;text-align:center"><div class="sig"></div>HUELLA DEL PACIENTE</div></div>
</body></html>
