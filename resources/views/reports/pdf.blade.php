@php
    $report = $order->report;
    $doctor = $report?->medicoFirmante;
    $signature = $doctor?->firma_path ? storage_path('app/public/'.$doctor->firma_path) : null;
    $patient = $order->patient;
    $examNames = $order->orderExams->pluck('exam.nombre_examen')->filter()->implode(', ');
    $contrast = $order->orderExams->contains('tipo_contraste', 'Con contraste') ? 'Con contraste endovenoso' : 'Sin contraste';
    $orderCode = $order->codigo_orden ?? 'Orden #'.$order->id;
    $reportDate = optional($report?->updated_at ?? $order->fecha_orden)->format('d/m/Y');

    $normalizeTitle = fn ($title) => trim(str_replace(['###', '**'], '', $title));
    $sections = collect(preg_split('/\n\s*---\s*\n/', (string) $report->contenido))
        ->map(function ($block) use ($normalizeTitle) {
            $lines = preg_split('/\R/', trim($block));
            $heading = $normalizeTitle(array_shift($lines) ?? 'Informe');
            $body = trim(implode("\n", $lines));

            return ['heading' => $heading, 'body' => $body];
        })
        ->filter(fn ($section) => $section['body'] !== '')
        ->values();
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22mm 18mm 20mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; line-height: 1.5; margin: 0; }
        .report-shell { border: 1px solid #dbe4ee; min-height: 100%; padding: 0; }
        .header { background: #0f2a44; color: #ffffff; padding: 18px 22px 16px; }
        .brand-table { width: 100%; border-collapse: collapse; }
        .brand-table td { vertical-align: top; padding: 0; }
        .brand-mark { background: #14b8a6; border-radius: 6px; color: #ffffff; display: inline-block; font-size: 22px; font-weight: 700; height: 42px; line-height: 42px; margin-right: 10px; text-align: center; width: 42px; }
        .brand-name { display: inline-block; font-size: 17px; font-weight: 700; letter-spacing: .04em; padding-top: 2px; text-transform: uppercase; }
        .brand-subtitle { color: #c7d2fe; display: block; font-size: 9px; font-weight: 400; letter-spacing: .14em; margin-top: 1px; text-transform: uppercase; }
        .document-chip { border: 1px solid rgba(255, 255, 255, .35); border-radius: 20px; display: inline-block; font-size: 9px; letter-spacing: .11em; padding: 5px 10px; text-transform: uppercase; }
        .title-band { border-bottom: 4px solid #14b8a6; padding: 18px 22px 14px; }
        .title { color: #0f172a; font-size: 18px; font-weight: 700; letter-spacing: .03em; line-height: 1.25; margin: 0 0 5px; text-align: center; text-transform: uppercase; }
        .subtitle { color: #64748b; font-size: 10px; letter-spacing: .08em; margin: 0; text-align: center; text-transform: uppercase; }
        .content-wrap { padding: 18px 22px 20px; }
        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .info-grid td { border: 1px solid #e2e8f0; padding: 7px 9px; vertical-align: top; width: 50%; }
        .label { color: #64748b; display: block; font-size: 8px; font-weight: 700; letter-spacing: .08em; margin-bottom: 2px; text-transform: uppercase; }
        .value { color: #111827; font-size: 10.5px; font-weight: 700; }
        .section { border: 1px solid #dbeafe; border-radius: 8px; margin-bottom: 12px; overflow: hidden; page-break-inside: avoid; }
        .section-heading { background: #eff6ff; border-bottom: 1px solid #dbeafe; color: #0f2a44; font-size: 10px; font-weight: 700; letter-spacing: .09em; padding: 7px 10px; text-transform: uppercase; }
        .section-body { background: #ffffff; color: #1f2937; font-size: 11.2px; line-height: 1.65; padding: 10px 12px; white-space: pre-line; }
        .signature-table { margin-top: 20px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        .signature-table td { vertical-align: bottom; width: 50%; }
        .validation-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; color: #64748b; font-size: 8.5px; line-height: 1.45; padding: 9px 10px; }
        .signature { text-align: center; }
        .signature-box { height: 66px; margin-bottom: 3px; }
        .signature img { max-height: 66px; max-width: 220px; }
        .line { border-top: 1px solid #0f172a; margin: 0 auto 5px; width: 245px; }
        .doctor-name { color: #0f172a; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .doctor-code { color: #475569; font-size: 9px; margin-top: 1px; }
        .footer { border-top: 1px solid #e2e8f0; color: #64748b; font-size: 8px; margin-top: 18px; padding-top: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="report-shell">
        <div class="header">
            <table class="brand-table">
                <tr>
                    <td>
                        <span class="brand-mark">T</span>
                        <span class="brand-name">Tomografías 2026<span class="brand-subtitle">Centro de diagnóstico por imágenes</span></span>
                    </td>
                    <td style="text-align: right;"><span class="document-chip">Informe médico</span></td>
                </tr>
            </table>
        </div>

        <div class="title-band">
            <h1 class="title">{{ $report->titulo }}</h1>
            <p class="subtitle">Resultado de estudio tomográfico</p>
        </div>

        <div class="content-wrap">
            <table class="info-grid">
                <tr>
                    <td><span class="label">Paciente</span><span class="value">{{ $patient->nombres }} {{ $patient->apellidos }}</span></td>
                    <td><span class="label">DNI / Edad</span><span class="value">{{ $patient->dni }} · {{ $patient->edad ? $patient->edad.' años' : 'No registrada' }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Orden</span><span class="value">{{ $orderCode }}</span></td>
                    <td><span class="label">Fecha de informe</span><span class="value">{{ $reportDate }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Estudio solicitado</span><span class="value">{{ $examNames ?: 'No especificado' }}</span></td>
                    <td><span class="label">Contraste</span><span class="value">{{ $contrast }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Convenio</span><span class="value">{{ $order->agreement->nombre_institucion }}</span></td>
                    <td><span class="label">Médico solicitante</span><span class="value">{{ $order->medicoSolicitante?->nombre_completo ?? '—' }}</span></td>
                </tr>
            </table>

            @foreach($sections as $section)
                <div class="section">
                    <div class="section-heading">{{ $section['heading'] }}</div>
                    <div class="section-body">{{ $section['body'] }}</div>
                </div>
            @endforeach

            <table class="signature-table">
                <tr>
                    <td>
                        <div class="validation-box">
                            Este documento corresponde a la interpretación médica del estudio indicado. La información debe correlacionarse con los antecedentes clínicos y exámenes complementarios del paciente.
                        </div>
                    </td>
                    <td>
                        <div class="signature">
                            <div class="signature-box">
                                @if($signature && file_exists($signature))
                                    <img src="{{ $signature }}" alt="Firma del médico">
                                @endif
                            </div>
                            <div class="line"></div>
                            <div class="doctor-name">{{ $doctor?->nombre_completo ?? 'Médico informante' }}</div>
                            <div class="doctor-code">
                                @if($doctor?->cmp) CMP: {{ $doctor->cmp }} @endif
                                @if($doctor?->rne) &nbsp; RNE: {{ $doctor->rne }} @endif
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                {{ $orderCode }} · Generado por Tomografías 2026 · Documento confidencial de uso médico
            </div>
        </div>
    </div>
</body>
</html>
