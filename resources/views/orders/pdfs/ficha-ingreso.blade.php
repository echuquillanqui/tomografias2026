@php($setting = \App\Models\SystemSetting::current())
@php($admissionData = $admissionData ?? [])
<!doctype html><html><head><meta charset="utf-8"><style>.company-header{width:100%;border-bottom:1px solid #1f6fb2;margin-bottom:8px;padding-bottom:6px}.company-logo{max-height:45px;max-width:100px}.company-name{font-size:14px;font-weight:bold}.company-data{font-size:9px;color:#555}</style><style>
body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#003b75}.title{text-align:center;font-size:18px;font-weight:bold;text-decoration:underline}.box{border:1px solid #1f6fb2;margin-bottom:6px}.row{display:table;width:100%;table-layout:fixed}.cell{display:table-cell;border-right:1px solid #1f6fb2;border-bottom:1px solid #1f6fb2;padding:4px}.cell:last-child{border-right:0}.label{font-weight:bold;color:#0057a8}.yellow{background:#fff200}.head{background:#0c55a2;color:white;text-align:center;font-weight:bold;padding:4px}.red{color:red;font-weight:bold}.sig{height:70px;border:1px solid #1f6fb2;border-radius:8px}.muted{color:#666}.full{min-height:34px;padding:5px;border-bottom:1px solid #1f6fb2}.section-title{background:#0c55a2;color:white;text-align:center;font-weight:bold;padding:4px;margin-top:8px}
</style></head><body>
<table class="company-header"><tr><td style="width:110px">@if($setting->logo_path && file_exists(storage_path('app/public/'.$setting->logo_path)))<img class="company-logo" src="{{ storage_path('app/public/'.$setting->logo_path) }}" alt="Logo">@endif</td><td><div class="company-name">{{ $setting->razon_social }}</div><div class="company-data">{{ collect([$setting->ruc ? 'RUC '.$setting->ruc : null, $setting->direccion, $setting->telefono])->filter()->implode(' · ') }}</div></td></tr></table>
<div class="title">FICHA DE INGRESO</div>
<div style="text-align:center;font-weight:bold">{{ $admissionData['agreement'] ?? 'PARTICULAR' }}</div>
<div class="box">
 <div class="row"><div class="cell"><span class="label">N° de Solicitud:</span> {{ $admissionData['request_number'] ?? ($order->codigo_orden ?? $order->id) }}</div><div class="cell"><span class="label">Fecha - Hora de atención:</span> {{ $admissionData['date'] ?? $order->fecha_orden->format('d/m/Y') }}</div><div class="cell yellow"><span class="label">Unidad:</span> {{ $admissionData['unit'] ?? $order->unidad }}</div></div>
 <div class="head">DATOS DEL PACIENTE</div>
 <div class="row"><div class="cell"><span class="label">Nombres:</span> {{ $admissionData['patient_name'] ?? ($order->patient->apellidos.' '.$order->patient->nombres) }}</div><div class="cell"><span class="label">DNI:</span> {{ $admissionData['patient_dni'] ?? $order->patient->dni }}</div><div class="cell"><span class="label">Cel:</span> {{ $admissionData['patient_phone'] ?? ($order->patient->telefono ?? '—') }}</div></div>
 <div class="row"><div class="cell"><span class="label">Fecha de nacimiento:</span> {{ $admissionData['patient_birthdate'] ?? (optional($order->patient->fecha_nacimiento)->format('d/m/Y') ?? '—') }}</div><div class="cell"><span class="label">Edad:</span> {{ $admissionData['patient_age'] ?? ($order->patient->edad ?? ($order->patient->fecha_nacimiento?->age ?? '—')) }}</div><div class="cell"><span class="label">Años</span></div></div>
 <div class="row"><div class="cell yellow"><span class="label">Solicitado por:</span> {{ $admissionData['requested_by'] ?? ($order->medicoSolicitante?->nombre_completo ?? '—') }}</div><div class="cell"><span class="label">Condición:</span> {{ $admissionData['condition'] ?? ($order->agreement?->nombre_institucion ?? 'PARTICULAR') }}</div><div class="cell"><span class="red">{{ $admissionData['contrast_label'] ?? ($hasContrast ? 'CON CONTRASTE' : 'SIN CONTRASTE') }}</span></div></div>
</div>
<div class="section-title">PROCEDENCIA</div><div class="full"><b>Estudio solicitado:</b> {{ $admissionData['study'] ?? $order->orderExams->pluck('exam.nombre_examen')->join(', ') }}<br><b>Descartar:</b> {{ $admissionData['rule_out'] ?? ($admissionData['observations'] ?? ($order->observaciones ?? '—')) }}</div>
<div class="section-title">ANTECEDENTES</div>
<div class="full"><b>Causa:</b> {{ $admissionData['cause'] ?? '' }}</div>
<div class="full"><b>Sintomatología:</b> {{ $admissionData['symptomatology'] ?? '' }}</div>
<div class="full"><b>Intervenciones quirúrgicas:</b> {{ $admissionData['surgeries'] ?? '' }}</div>
<div class="full"><b>Medicación:</b> {{ $admissionData['medication'] ?? '' }}</div>
<div class="full"><b>Informado por:</b> {{ $admissionData['informed_by'] ?? '' }}</div>
@if($hasContrast)
<div class="section-title">USO INTERNO / DATOS PARA CONTRASTE</div>
<div class="row box"><div class="cell"><span class="label">Alergia probable/medicamento:</span> {{ $admissionData['allergy'] ?? '' }}</div><div class="cell"><span class="label">¿Está en ayunas?</span> {{ $admissionData['fasting'] ?? '' }}</div><div class="cell"><span class="label">Hace 4 horas:</span> {{ $admissionData['fasting'] ?? '' }}</div></div>
<div class="row box"><div class="cell"><span class="label">Prueba de creatinina:</span> {{ $admissionData['creatinine'] ?? '' }}</div><div class="cell"><span class="label">Valor:</span> {{ $admissionData['creatinine'] ?? '' }}</div><div class="cell"><span class="label">Fecha:</span> {{ $admissionData['date'] ?? '' }}</div></div>
<div class="row box"><div class="cell"><span class="label">Vía periférica:</span> {{ $admissionData['peripheral_route'] ?? '' }}</div><div class="cell"><span class="label">Uso interno:</span> Contraste aplicado ( )</div></div>
@endif
<div class="section-title">DOCUMENTOS / ENTREGA</div><div class="full">{{ $admissionData['delivery'] ?? '' }}<br>CD ( ) &nbsp;&nbsp; Informe ( ) &nbsp;&nbsp; Bolsa de cortesía ( )</div>
<br><br><div class="row"><div class="cell" style="border:0;text-align:center"><div class="sig"></div>FIRMA DEL PACIENTE</div><div class="cell" style="border:0;text-align:center"><div class="sig"></div>HUELLA DEL PACIENTE</div></div>
</body></html>
