<?php
    $setting = \App\Models\SystemSetting::current();
    $admissionData = $admissionData ?? [];
    $patientName = $admissionData['patient_name'] ?? trim(($order->patient->apellidos ?? '').' '.($order->patient->nombres ?? ''));
    $birthdate = $admissionData['patient_birthdate'] ?? (optional($order->patient->fecha_nacimiento)->format('d/m/Y') ?? '—');
    $patientAge = $admissionData['patient_age'] ?? ($order->patient->edad ?? ($order->patient->fecha_nacimiento?->age ?? '—'));
    $deliveryItems = ['PLACAS', 'CD', 'INFORME'];
    $deliveryOptions = $admissionData['delivery_options'] ?? $deliveryItems;
    $deliveryOptions = empty($deliveryOptions) ? $deliveryItems : (array) $deliveryOptions;
    $deliveryQuantities = $admissionData['delivery_quantities'] ?? [];
    $deliveryQuantities = is_array($deliveryQuantities) ? $deliveryQuantities : [];
    $surgeries = ($admissionData['surgeries'] ?? 'Ninguna') === 'Otros'
        ? ($admissionData['surgeries_detail'] ?? 'Otros')
        : ($admissionData['surgeries'] ?? 'Ninguna');
?>
<!doctype html><html><head><meta charset="utf-8"><style>@page{margin:34px 42px}.company-header{width:100%;border-bottom:1.4px solid #1f6fb2;margin-bottom:8px;padding-bottom:6px}.company-logo{max-height:44px;max-width:110px}.company-name{font-size:14px;font-weight:bold}.company-data{font-size:9.5px;color:#555}</style><style>
body{font-family:DejaVu Sans,sans-serif;font-size:10.5px;line-height:1.32;color:#003b75;margin:0}.sheet{min-height:760px;border:1.4px solid #1f6fb2;border-radius:8px;padding:12px 14px 14px;background:#fff}.title{text-align:center;font-size:20px;line-height:1.1;font-weight:bold;text-decoration:underline;margin:6px 0 3px}.agreement{text-align:center;font-size:10.5px;line-height:1.15;font-weight:bold;margin-bottom:8px}.box{border:1px solid #1f6fb2;margin-bottom:7px}.row{display:table;width:100%;table-layout:fixed}.cell{display:table-cell;border-right:1px solid #1f6fb2;border-bottom:1px solid #1f6fb2;padding:5px 6px;vertical-align:top}.cell:last-child{border-right:0}.label{font-weight:bold;color:#0057a8}.yellow{background:#fff9a8}.head{background:#0c55a2;color:white;text-align:center;font-weight:bold;padding:4px}.red{color:red;font-weight:bold}.sig{height:62px;border:1px solid #1f6fb2;border-radius:8px}.muted{color:#666}.full{min-height:34px;padding:6px 7px;border:1px solid #1f6fb2;border-top:0}.section-title{background:#0c55a2;color:white;text-align:center;font-weight:bold;padding:4px;margin-top:8px}.delivery-table{width:100%;border-collapse:collapse;font-size:10.5px;margin-top:6px}.delivery-table th,.delivery-table td{border:1px solid #1f6fb2;padding:6px 7px}.delivery-table th{background:#eaf3fb;color:#0057a8;text-align:left}.delivery-check{font-weight:bold;font-size:11px}.delivery-number{text-align:center;font-weight:bold;font-size:11px}.signature-row{margin-top:14px}
</style></head><body><div class="sheet">
<table class="company-header"><tr><td style="width:110px"><?php if($setting->logo_path && file_exists(storage_path('app/public/'.$setting->logo_path))): ?><img class="company-logo" src="<?= e(storage_path('app/public/'.$setting->logo_path)) ?>" alt="Logo"><?php endif; ?></td><td><div class="company-name"><?= e($setting->razon_social) ?></div><div class="company-data"><?= e(collect([$setting->ruc ? 'RUC '.$setting->ruc : null, $setting->direccion, $setting->telefono])->filter()->implode(' · ')) ?></div></td></tr></table>
<h1 class="title">FICHA DE INGRESO</h1>
<div class="agreement"><?= e($admissionData['agreement'] ?? 'PARTICULAR') ?></div>
<div class="box">
 <div class="row"><div class="cell"><span class="label">N° de Solicitud:</span> <?= e($admissionData['request_number'] ?? ($order->codigo_orden ?? $order->id)) ?></div><div class="cell"><span class="label">Fecha - Hora de atención:</span> <?= e($admissionData['date'] ?? $order->fecha_orden->format('d/m/Y')) ?></div><div class="cell yellow"><span class="label">Unidad:</span> <?= e($admissionData['unit'] ?? $order->unidad) ?></div></div>
 <div class="head">DATOS DEL PACIENTE</div>
 <div class="row"><div class="cell"><span class="label">Nombres:</span> <?= e($patientName) ?></div><div class="cell"><span class="label">DNI:</span> <?= e($admissionData['patient_dni'] ?? $order->patient->dni) ?></div><div class="cell"><span class="label">Cel:</span> <?= e($admissionData['patient_phone'] ?? ($order->patient->telefono ?? '—')) ?></div></div>
 <div class="row"><div class="cell"><span class="label">Fecha de nacimiento:</span> <?= e($birthdate) ?></div><div class="cell"><span class="label">Edad:</span> <?= e($patientAge) ?></div><div class="cell"><span class="label">Años</span></div></div>
 <div class="row"><div class="cell yellow"><span class="label">Solicitado por:</span> <?= e($admissionData['requested_by'] ?? ($order->medicoSolicitante?->nombre_completo ?? '—')) ?></div><div class="cell"><span class="label">Condición:</span> <?= e($admissionData['condition'] ?? ($order->agreement?->nombre_institucion ?? 'PARTICULAR')) ?></div><div class="cell"><span class="red"><?= e($admissionData['contrast_label'] ?? ($hasContrast ? 'CON CONTRASTE' : 'SIN CONTRASTE')) ?></span></div></div>
</div>
<div class="section-title">PROCEDENCIA</div><div class="full"><b>Estudio solicitado:</b> <?= e($admissionData['study'] ?? $order->orderExams->pluck('exam.nombre_examen')->join(', ')) ?><br><b>Descartar:</b> <?= e($admissionData['rule_out'] ?? ($admissionData['observations'] ?? ($order->observaciones ?? '—'))) ?></div>
<div class="section-title">ANTECEDENTES</div>
<div class="full"><b>Causa:</b> <?= e($admissionData['cause'] ?? '') ?></div>
<div class="full"><b>Sintomatología:</b> <?= e($admissionData['symptomatology'] ?? '') ?></div>
<div class="full"><b>Intervenciones quirúrgicas:</b> <?= e($surgeries) ?></div>
<div class="full"><b>Medicación:</b> <?= nl2br(e($admissionData['medication'] ?? '')) ?></div>
<div class="full"><b>Antecedentes:</b> <?= nl2br(e($admissionData['antecedents'] ?? '')) ?></div>
<div class="full"><b>Informado por:</b> <?= e($admissionData['informed_by'] ?? '') ?></div>
<?php if($hasContrast): ?>
<div class="section-title">USO INTERNO / DATOS PARA CONTRASTE</div>
<div class="row box"><div class="cell"><span class="label">Alergia probable/medicamento:</span> <?= e($admissionData['allergy'] ?? '') ?></div><div class="cell"><span class="label">¿Está en ayunas?</span> <?= e($admissionData['fasting'] ?? '') ?></div><div class="cell"><span class="label">Hace 4 horas:</span> <?= e($admissionData['fasting'] ?? '') ?></div></div>
<div class="row box"><div class="cell"><span class="label">Prueba de creatinina:</span> <?= e($admissionData['creatinine'] ?? '') ?></div><div class="cell"><span class="label">Valor:</span> <?= e($admissionData['creatinine'] ?? '') ?></div><div class="cell"><span class="label">Fecha:</span> <?= e($admissionData['date'] ?? '') ?></div></div>
<div class="row box"><div class="cell"><span class="label">Vía periférica:</span> <?= e($admissionData['peripheral_route'] ?? '') ?></div><div class="cell"><span class="label">Uso interno:</span> Contraste aplicado ( )</div></div>
<?php endif; ?>
<div class="section-title">DOCUMENTOS / ENTREGA</div>
<table class="delivery-table">
    <thead><tr><th style="width:65%">Documento</th><th style="width:35%">Número</th></tr></thead>
    <tbody>
        <?php foreach($deliveryItems as $option): ?>
            <tr>
                <td class="delivery-check">(<?= in_array($option, $deliveryOptions, true) ? 'X' : ' ' ?>) <?= e($option) ?></td>
                <td class="delivery-number"><?= e($deliveryQuantities[$option] ?? ($option === 'PLACAS' ? ($admissionData['plates_count'] ?? '—') : '—')) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div class="row signature-row"><div class="cell" style="border:0;text-align:center"><div class="sig"></div>FIRMA DEL PACIENTE</div><div class="cell" style="border:0;text-align:center"><div class="sig"></div>HUELLA DEL PACIENTE</div></div>
</div></body></html>
