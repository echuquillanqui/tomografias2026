<?php
    $setting = \App\Models\SystemSetting::current();
    $admissionData = $admissionData ?? [];
    $patientName = $admissionData['patient_name'] ?? trim(($order->patient->apellidos ?? '').' '.($order->patient->nombres ?? ''));
    $birthdate = $admissionData['patient_birthdate'] ?? (optional($order->patient->fecha_nacimiento)->format('d/m/Y') ?? '—');
    $patientAge = $admissionData['patient_age'] ?? ($order->patient->edad ?? ($order->patient->fecha_nacimiento?->age ?? '—'));
    $patientAgeLabel = is_numeric($patientAge) ? $patientAge.' años' : $patientAge;
    $patientSex = $admissionData['patient_sex'] ?? ($order->patient->sexo ?? '—');
    $normalizedPatientSex = mb_strtoupper(trim((string) $patientSex));
    $patientSexClass = match ($normalizedPatientSex) {
        'MASCULINO' => 'sex-badge sex-masculino',
        'FEMENINO' => 'sex-badge sex-femenino',
        default => 'sex-badge sex-neutral',
    };
    $deliveryItems = ['PLACAS', 'INFORME'];
    $deliveryMediaItems = ['CD', 'LINK'];
    $legacyDeliveryItems = ['PLACAS', 'CD', 'INFORME'];
    $deliveryOptions = $admissionData['delivery_options'] ?? $legacyDeliveryItems;
    $deliveryOptions = empty($deliveryOptions) ? $legacyDeliveryItems : array_values(array_intersect((array) $deliveryOptions, $legacyDeliveryItems));
    $deliveryMediaSelected = $admissionData['delivery_media_options'] ?? [];
    if (empty($deliveryMediaSelected)) {
        $savedDeliveryMedia = $admissionData['delivery_media'] ?? '';
        $deliveryMediaSelected = $savedDeliveryMedia === 'AMBOS' ? ['CD', 'LINK'] : (in_array($savedDeliveryMedia, $deliveryMediaItems, true) ? [$savedDeliveryMedia] : []);
    }
    if (empty($deliveryMediaSelected) && in_array('CD', $deliveryOptions, true)) {
        $deliveryMediaSelected = ['CD'];
    }
    if (empty($deliveryMediaSelected)) {
        $deliveryMediaSelected = [strtoupper(trim((string) ($order->agreement->nombre_institucion ?? 'PARTICULAR'))) === 'PARTICULAR' ? 'CD' : 'LINK'];
    }
    $deliveryMediaSelected = array_values(array_intersect((array) $deliveryMediaSelected, $deliveryMediaItems));
    $deliveryQuantities = $admissionData['delivery_quantities'] ?? [];
    $deliveryQuantities = is_array($deliveryQuantities) ? $deliveryQuantities : [];
    $printableContrastConsumables = collect($contrastConsumables ?? [])->reject(
        fn ($consumable) => str_contains(mb_strtolower((string) ($consumable['name'] ?? '')), 'placa')
    )->values();
    $formatDeliveryQuantity = function ($option) use ($deliveryQuantities, $admissionData) {
        $value = $deliveryQuantities[$option] ?? ($option === 'PLACAS' ? ($admissionData['plates_count'] ?? null) : null);

        if ($value === null || $value === '') {
            return '';
        }

        return ctype_digit((string) $value) ? str_pad((string) $value, 2, '0', STR_PAD_LEFT) : $value;
    };
    $agreementName = mb_strtoupper(trim((string) ($admissionData['agreement'] ?? ($order->agreement->nombre_institucion ?? 'PARTICULAR'))));
    $surgeries = ($admissionData['surgeries'] ?? 'Ninguna') === 'Otros'
        ? ($admissionData['surgeries_detail'] ?? 'Otros')
        : ($admissionData['surgeries'] ?? 'Ninguna');
?>
<!doctype html><html><head><meta charset="utf-8"><style>@page{margin:1cm 24px 18px 24px}.company-header{width:calc(100% - 20px);border-bottom:1.4px solid #1f6fb2;margin:0 10px 8px;padding-bottom:6px}.company-logo{max-height:44px;max-width:110px}.company-name{font-size:14px;font-weight:bold}.company-data{font-size:9.5px;color:#555}</style><style>
body{font-family:DejaVu Sans,sans-serif;font-size:9.2px;line-height:1.18;color:#003b75;margin:0}.sheet{border:1.4px solid #1f6fb2;border-radius:8px;padding:8px 0 10px;background:#fff;page-break-inside:avoid}.title-band{display:table;width:100%;table-layout:fixed;margin:3px 0 5px}.title-spacer,.title,.agreement{display:table-cell;width:33.333%;vertical-align:middle}.title{text-align:center;font-size:23px;line-height:1.05;font-weight:bold;text-decoration:underline;margin:0}.agreement{color:#0057a8;font-size:11px;line-height:1.1;font-weight:bold;text-align:center;padding:5px}.box{border:1px solid #1f6fb2;margin-bottom:4px}.row{display:table;width:100%;table-layout:fixed}.cell{display:table-cell;border-right:1px solid #1f6fb2;border-bottom:1px solid #1f6fb2;padding:3px 5px;vertical-align:top}.cell:last-child{border-right:0}.label{font-weight:bold;color:#0057a8}.patient-birthdate-cell{width:34%;white-space:nowrap}.patient-age-cell{width:16%;white-space:nowrap}.patient-sex-cell{width:25%;white-space:nowrap}.patient-phone-cell{width:25%;white-space:nowrap}.sex-badge{display:inline-block;border-radius:5px;padding:3px 7px;font-size:10.8px;line-height:1;font-weight:bold;letter-spacing:.3px;color:#111;border:1px solid transparent}.sex-masculino{background:#0057d9;color:#fff;border-color:#003b91}.sex-femenino{background:#ff4fa3;color:#111;border-color:#c2186a}.sex-neutral{background:#f1f5f9;color:#111;border-color:#94a3b8}.yellow{background:#fff9a8}.head{background:#0c55a2;color:white;text-align:center;font-weight:bold;padding:3px}.red{color:red;font-weight:bold}.contrast-cell{font-size:11px;text-align:center;background:#fff4c7}.study-cell{font-size:11px;font-weight:bold}.wrap-cell{word-wrap:break-word;overflow-wrap:break-word;white-space:normal}.sig{height:82px;width:100%;box-sizing:border-box;border:1px solid #1f6fb2;border-radius:8px}.fingerprint{height:98px;margin:0 auto}.muted{color:#666}.full{min-height:22px;padding:4px 6px;border:1px solid #1f6fb2;border-top:0}.section-title{background:#0c55a2;color:white;text-align:center;font-weight:bold;padding:3px;margin-top:4px}.delivery-table{width:100%;border-collapse:collapse;font-size:9.2px;margin-top:4px;table-layout:fixed}.delivery-table td{width:25%;border:1px solid #1f6fb2;padding:3px 5px;text-align:center;white-space:nowrap;vertical-align:middle}.delivery-item{display:inline-table;table-layout:auto;margin:0 auto}.delivery-name,.delivery-check,.delivery-quantity{display:table-cell;vertical-align:middle}.delivery-name{color:#0057a8;font-weight:bold;padding-right:8px}.delivery-check{font-weight:bold;padding-right:8px}.delivery-quantity{width:30px;min-width:30px;height:13px;line-height:13px;border:1px solid #1f6fb2;padding:1px 4px;text-align:center;font-weight:bold}.signatures-box{margin-top:0;padding:7px 15px 10px}.informed-title{border:1px solid #1f6fb2;border-radius:6px;font-weight:bold;color:#0057a8;margin-bottom:14px;padding:4px 6px;}.signature-layout{width:100%;border-collapse:collapse;table-layout:fixed}.signature-left{width:56%;text-align:center;vertical-align:top;padding-right:72px}.signature-right{text-align:center;vertical-align:top;padding-left:32px}.signature-space{height:158px;border:1px solid #1f6fb2;border-radius:7px}.signature-label{display:block;margin-top:4px;margin-bottom: 15px ;font-weight:bold}.patient-name-line{height:38px;border-bottom:1px solid #1f6fb2;margin-bottom:23px}.receipt-space{height:68px;border:1px solid #1f6fb2;border-radius:7px}.receipt-label{display:block;margin-top:2px}.fingerprint-space{width:110px;height:140px;border:1px solid #1f6fb2;border-radius:7px;margin:0 auto}.fingerprint-label{display:block;margin-top:3px}
body{ text-transform:uppercase; }
.with-contrast{padding-bottom:6px}.with-contrast .box{margin-bottom:3px}.with-contrast .full{min-height:16px;padding:2px 6px}.with-contrast .section-title{padding:2px;margin-top:3px}.with-contrast .cell{padding:2px 5px}.with-contrast .delivery-table{margin-top:3px}.with-contrast .delivery-table td{padding:2px 5px}.with-contrast .signatures-box{padding-top:5px}.with-contrast .informed-title{margin-bottom:8px}.with-contrast .signature-space{height:100px}.with-contrast .fingerprint-space{height:120px}.with-contrast .patient-name-line{height:12px;margin-bottom:10px}.with-contrast .receipt-space{height:86px}.without-contrast .full{min-height:34px;padding-top:8px;padding-bottom:8px}.without-contrast .signatures-box{padding-top:10px}.without-contrast .informed-title{margin-bottom:14px}.without-contrast .signature-space{height:118px}.without-contrast .fingerprint-space{height:140px}.without-contrast .patient-name-line{height:38px;margin-bottom:23px}.without-contrast .receipt-space{height:68px}
</style></head><body><div class="sheet <?= $hasContrast ? 'with-contrast' : 'without-contrast' ?>">
<table class="company-header"><tr><td style="width:110px"><?php if($setting->logo_path && file_exists(storage_path('app/public/'.$setting->logo_path))): ?><img class="company-logo" src="<?= e(storage_path('app/public/'.$setting->logo_path)) ?>" alt="Logo"><?php endif; ?></td><td><div class="company-name"><?= e($setting->razon_social) ?></div><div class="company-data"><?= e(collect([$setting->ruc ? 'RUC '.$setting->ruc : null, $setting->direccion, $setting->telefono])->filter()->implode(' · ')) ?></div></td></tr></table>
<div class="title-band"><div class="title-spacer"></div><h1 class="title">FICHA DE INGRESO</h1><div class="agreement"><?= e($agreementName) ?></div></div>
<div class="box">
 <div class="row"><div class="cell"><span class="label">N° de Solicitud:</span> <?= e($admissionData['request_number'] ?? ($order->codigo_orden ?? $order->id)) ?></div><div class="cell"><span class="label">Fecha y hora de atención:</span> <?= e($admissionData['date'] ?? $order->fecha_orden->format('d/m/Y H:i')) ?></div></div>
 <div class="head">DATOS DEL PACIENTE</div>
 <div class="row"><div class="cell" style="width:66.666%"><span class="label">Nombres:</span> <?= e($patientName) ?></div><div class="cell"><span class="label">DNI:</span> <?= e($admissionData['patient_dni'] ?? $order->patient->dni) ?></div></div>
 <div class="row"><div class="cell patient-birthdate-cell"><span class="label">Fecha de nacimiento:</span> <?= e($birthdate) ?></div><div class="cell patient-age-cell"><span class="label">Edad:</span> <?= e($patientAgeLabel) ?></div><div class="cell patient-sex-cell"><span class="label">Sexo:</span> <span class="<?= e($patientSexClass) ?>"><?= e($patientSex) ?></span></div><div class="cell patient-phone-cell"><span class="label">Cel:</span> <?= e($admissionData['patient_phone'] ?? ($order->patient->telefono ?? '—')) ?></div></div>
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
<?php if($hasContrast && $printableContrastConsumables->isNotEmpty()): ?>
<div class="section-title">INSUMOS Y MATERIALES DE USO INTERNO PARA ESTUDIO <?= $hasContrast ? 'CON CONTRASTE' : 'SIN CONTRASTE' ?></div>
<div class="box"><div class="row"><div class="cell label">Insumo / material</div><div class="cell label" style="width:18%">Cantidad</div><div class="cell label" style="width:18%">Unidad</div><div class="cell label" style="width:24%">Bránula</div></div><?php $branulaPdf = in_array(($admissionData['peripheral_route'] ?? ''), ['18','20','22'], true) ? 'N° '.($admissionData['peripheral_route'] ?? '') : ($admissionData['peripheral_route'] ?? ''); ?><?php foreach($printableContrastConsumables as $index => $consumable): ?><div class="row"><div class="cell"><span class="label"><?= e($consumable['name']) ?></span></div><div class="cell" style="width:18%"><?= e($consumable['cantidad']) ?></div><div class="cell" style="width:18%"><?= e($consumable['unit'] ?? '') ?></div><div class="cell red" style="width:24%"><?= $index === 0 ? e($branulaPdf) : '' ?></div></div><?php endforeach; ?></div>
<?php endif; ?>
<?php if($hasContrast): ?>
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
                        <?php $quantity = $formatDeliveryQuantity($option); ?><?php if($quantity !== ''): ?><span class="delivery-quantity"><?= e($quantity) ?></span><?php endif; ?>
                    </span>
                </td>
            <?php endforeach; ?>
            <?php foreach($deliveryMediaItems as $option): ?>
                <td>
                    <span class="delivery-item">
                        <span class="delivery-name"><?= e($option) ?></span>
                        <span class="delivery-check">(<?= in_array($option, $deliveryMediaSelected, true) ? 'X' : '—' ?>)</span>
                        <?php $quantity = $formatDeliveryQuantity($option); ?><?php if($quantity !== ''): ?><span class="delivery-quantity"><?= e($quantity) ?></span><?php endif; ?>
                    </span>
                </td>
            <?php endforeach; ?>
        </tr>
    </tbody>
</table>
<div class="signatures-box">
    <div class="informed-title">INFORMADO POR: <?= e($order->medicoInforme?->nombre_completo ?? ($admissionData['informed_by'] ?? '')) ?></div>
    <table class="signature-layout">
        <tr>
            <td class="signature-left">
                <div class="signature-space"></div>
                <span class="signature-label">FIRMA DE PACIENTE O APODERADO</span>
                <div class="patient-name-line"></div>
                <div class="receipt-space"></div>
                <span class="receipt-label">RECIBÍ CONFORME / APODERADO</span>
            </td>
            <td class="signature-right">
                <div class="fingerprint-space"></div>
                <span class="fingerprint-label">HUELLA DE PACIENTE O APODERADO</span>
            </td>
        </tr>
    </table>
</div>
</div></body></html>
