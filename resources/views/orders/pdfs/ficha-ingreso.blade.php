<?php
    $setting = \App\Models\SystemSetting::current();
    $admissionData = $admissionData ?? [];
    $patientName = $admissionData['patient_name'] ?? trim(($order->patient->apellidos ?? '').' '.($order->patient->nombres ?? ''));
    $birthdate = $admissionData['patient_birthdate'] ?? (optional($order->patient->fecha_nacimiento)->format('d/m/Y') ?? '—');
    $patientAge = $admissionData['patient_age'] ?? ($order->patient->edad ?? ($order->patient->fecha_nacimiento?->age ?? '—'));
    $patientAgeLabel = is_numeric($patientAge) ? $patientAge.' años' : $patientAge;
    $deliveryItems = ['PLACAS', 'CD', 'INFORME'];
    $deliveryOptions = $admissionData['delivery_options'] ?? $deliveryItems;
    $deliveryOptions = empty($deliveryOptions) ? $deliveryItems : array_values(array_intersect((array) $deliveryOptions, $deliveryItems));
    $deliveryQuantities = $admissionData['delivery_quantities'] ?? [];
    $deliveryQuantities = is_array($deliveryQuantities) ? $deliveryQuantities : [];
    $formatDeliveryQuantity = function ($option) use ($deliveryQuantities, $admissionData) {
        $value = $deliveryQuantities[$option] ?? ($option === 'PLACAS' ? ($admissionData['plates_count'] ?? null) : null);

        return ($value === null || $value === '') ? '' : $value;
    };
    $surgeries = ($admissionData['surgeries'] ?? 'Ninguna') === 'Otros'
        ? ($admissionData['surgeries_detail'] ?? 'Otros')
        : ($admissionData['surgeries'] ?? 'Ninguna');
?>
<!doctype html><html><head><meta charset="utf-8"><style>@page{margin:18px 24px}.company-header{width:calc(100% - 20px);border-bottom:1.4px solid #1f6fb2;margin:0 10px 8px;padding-bottom:6px}.company-logo{max-height:44px;max-width:110px}.company-name{font-size:14px;font-weight:bold}.company-data{font-size:9.5px;color:#555}</style><style>
body{font-family:DejaVu Sans,sans-serif;font-size:9.2px;line-height:1.18;color:#003b75;margin:0}.sheet{border:1.4px solid #1f6fb2;border-radius:8px;padding:8px 0 10px;background:#fff;page-break-inside:avoid}.title{text-align:center;font-size:17px;line-height:1.05;font-weight:bold;text-decoration:underline;margin:3px 10px 2px}.agreement{text-align:center;font-size:9.2px;line-height:1.1;font-weight:bold;margin:0 10px 5px}.box{border:1px solid #1f6fb2;margin-bottom:4px}.row{display:table;width:100%;table-layout:fixed}.cell{display:table-cell;border-right:1px solid #1f6fb2;border-bottom:1px solid #1f6fb2;padding:3px 5px;vertical-align:top}.cell:last-child{border-right:0}.label{font-weight:bold;color:#0057a8}.yellow{background:#fff9a8}.head{background:#0c55a2;color:white;text-align:center;font-weight:bold;padding:3px}.red{color:red;font-weight:bold}.contrast-cell{font-size:11px;text-align:center;background:#fff4c7}.study-cell{font-size:11px;font-weight:bold}.wrap-cell{word-wrap:break-word;overflow-wrap:break-word;white-space:normal}.sig{height:62px;border:1px solid #1f6fb2;border-radius:8px}.fingerprint{height:78px;width:90px;margin:0 auto}.muted{color:#666}.full{min-height:22px;padding:4px 6px;border:1px solid #1f6fb2;border-top:0}.section-title{background:#0c55a2;color:white;text-align:center;font-weight:bold;padding:3px;margin-top:4px}.delivery-table{width:100%;border-collapse:collapse;font-size:9.2px;margin-top:4px;table-layout:fixed}.delivery-table td{width:33.333%;border:1px solid #1f6fb2;padding:3px 5px;text-align:center;white-space:nowrap;vertical-align:middle}.delivery-item{display:inline-table;table-layout:auto;margin:0 auto}.delivery-name,.delivery-check,.delivery-quantity{display:table-cell;vertical-align:middle}.delivery-name{color:#0057a8;font-weight:bold;padding-right:8px}.delivery-check{font-weight:bold;padding-right:8px}.delivery-quantity{width:30px;min-width:30px;height:13px;line-height:13px;border:1px solid #1f6fb2;padding:1px 4px;text-align:center;font-weight:bold}.signature-row{margin:8px 10px 0}.receipt-sig{height:46px;margin-top:10px}
</style></head><body><div class="sheet">
<table class="company-header"><tr><td style="width:110px"><?php if($setting->logo_path && file_exists(storage_path('app/public/'.$setting->logo_path))): ?><img class="company-logo" src="<?= e(storage_path('app/public/'.$setting->logo_path)) ?>" alt="Logo"><?php endif; ?></td><td><div class="company-name"><?= e($setting->razon_social) ?></div><div class="company-data"><?= e(collect([$setting->ruc ? 'RUC '.$setting->ruc : null, $setting->direccion, $setting->telefono])->filter()->implode(' · ')) ?></div></td></tr></table>
<h1 class="title">FICHA DE INGRESO</h1>
<div class="agreement"><?= e($admissionData['agreement'] ?? 'PARTICULAR') ?></div>
<div class="box">
 <div class="row"><div class="cell"><span class="label">N° de Solicitud:</span> <?= e($admissionData['request_number'] ?? ($order->codigo_orden ?? $order->id)) ?></div><div class="cell"><span class="label">Fecha y hora de atención:</span> <?= e($admissionData['date'] ?? $order->fecha_orden->format('d/m/Y H:i')) ?></div></div>
 <div class="head">DATOS DEL PACIENTE</div>
 <div class="row"><div class="cell" style="width:66.666%"><span class="label">Nombres:</span> <?= e($patientName) ?></div><div class="cell"><span class="label">DNI:</span> <?= e($admissionData['patient_dni'] ?? $order->patient->dni) ?></div></div>
 <div class="row"><div class="cell"><span class="label">Fecha de nacimiento:</span> <?= e($birthdate) ?></div><div class="cell"><span class="label">Edad:</span> <?= e($patientAgeLabel) ?></div><div class="cell"><span class="label">Cel:</span> <?= e($admissionData['patient_phone'] ?? ($order->patient->telefono ?? '—')) ?></div></div>
 <div class="row"><div class="cell yellow"><span class="label">Solicitado por:</span> <?= e($admissionData['requested_by'] ?? ($order->medicoSolicitante?->nombre ?? '—')) ?></div></div>
 <div class="row"><div class="cell study-cell" style="width:66.666%"><span class="label">Estudio solicitado:</span> <?= e($admissionData['study'] ?? $order->orderExams->pluck('exam.nombre_examen')->join(', ')) ?></div><div class="cell contrast-cell"><span class="red"><?= e($admissionData['contrast_label'] ?? ($hasContrast ? 'CON CONTRASTE' : 'SIN CONTRASTE')) ?></span></div></div>
</div>
<div class="full"><b>Descartar:</b> <?= e($admissionData['rule_out'] ?? ($admissionData['observations'] ?? ($order->observaciones ?? '—'))) ?></div>
<div class="section-title">ANAMNESIS</div>
<div class="full"><b>Causa:</b> <?= e($admissionData['cause'] ?? '') ?></div>
<div class="full"><b>Sintomatología:</b> <?= e($admissionData['symptomatology'] ?? '') ?></div>
<div class="full"><b>Intervenciones quirúrgicas:</b> <?= e($surgeries) ?></div>
<div class="full"><b>Medicación:</b> <?= nl2br(e($admissionData['medication'] ?? '')) ?></div>
<div class="full"><b>Antecedentes:</b> <?= nl2br(e($admissionData['antecedents'] ?? '')) ?></div>
<?php if($hasContrast): ?>
<div class="section-title">INSUMOS Y MATERIALES DE USO INTERNO PARA ESTUDIO CON CONTRASTE</div>
<div class="box"><div class="row"><div class="cell label">Insumo / material</div><div class="cell label" style="width:18%">Cantidad</div><div class="cell label" style="width:18%">Unidad</div><div class="cell label" style="width:24%">Bránula</div></div><?php $branulaPdf = in_array(($admissionData['peripheral_route'] ?? ''), ['18','20','22'], true) ? 'N° '.($admissionData['peripheral_route'] ?? '') : ($admissionData['peripheral_route'] ?? ''); ?><?php foreach(($contrastConsumables ?? []) as $index => $consumable): ?><div class="row"><div class="cell"><span class="label"><?= e($consumable['name']) ?></span></div><div class="cell" style="width:18%"><?= e($consumable['cantidad']) ?></div><div class="cell" style="width:18%"><?= e($consumable['unit'] ?? '') ?></div><div class="cell red" style="width:24%"><?= $index === 0 ? e($branulaPdf) : '' ?></div></div><?php endforeach; ?><?php if(empty($contrastConsumables ?? [])): ?><div class="row"><div class="cell muted" style="width:58%">Sin insumos precargados.</div><div class="cell red" style="width:24%"><?= e($branulaPdf) ?></div></div><?php endif; ?></div>
<div class="section-title">DATOS PARA CONTRASTE</div>
<div class="row box"><div class="cell wrap-cell" style="width:66.666%"><span class="label">Alergia probable/medicamento:</span> <?= e($admissionData['allergy'] ?? '') ?></div><div class="cell"><span class="label">¿Está en ayunas?</span> <?= e($admissionData['fasting'] ?? '') ?></div></div>
<div class="row box"><div class="cell wrap-cell"><span class="label">Prueba de creatinina:</span> <?= e($admissionData['creatinine'] ?? '') ?> mg/dl</div></div>
<?php endif; ?>
<div class="section-title">DOCUMENTOS / ENTREGA</div>
<table class="delivery-table">
    <tbody>
        <tr>
            <?php foreach($deliveryItems as $option): ?>
                <td>
                    <span class="delivery-item">
                        <span class="delivery-name"><?= e($option) ?></span>
                        <span class="delivery-check">(<?= in_array($option, $deliveryOptions, true) ? 'X' : '—' ?>)</span>
                        <span class="delivery-quantity"><?= e($formatDeliveryQuantity($option)) ?></span>
                    </span>
                </td>
            <?php endforeach; ?>
        </tr>
    </tbody>
</table>
<div class="box"><div class="row"><div class="cell"><span class="label">Informado por:</span> <?= e($admissionData['informed_by'] ?? '') ?></div></div></div>
<div class="row signature-row"><div class="cell" style="border:0;text-align:center"><div class="sig"></div>FIRMA DEL PACIENTE<div class="sig receipt-sig"></div>RECIBÍ CONFORME</div><div class="cell" style="border:0;text-align:center"><div class="sig fingerprint"></div>HUELLA DEL PACIENTE</div></div>
</div></body></html>
