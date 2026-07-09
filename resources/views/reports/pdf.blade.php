@php
    $report = $order->report;
    $doctor = $report?->medicoFirmante;
    $signature = $doctor?->firma_path ? storage_path('app/public/'.$doctor->firma_path) : null;
    $patient = $order->patient;
    $examNames = $order->orderExams->pluck('exam.nombre_examen')->filter()->implode(', ');
    $contrast = $order->orderExams->contains('tipo_contraste', 'Con contraste') ? 'Con contraste endovenoso' : 'Sin contraste';
    $orderCode = $order->codigo_orden ?? 'Orden #'.$order->id;
    $reportDate = optional($report?->updated_at ?? $order->fecha_orden)->format('d/m/Y');

    $filled = fn ($value) => filled($value) && ! str_contains((string) $value, '[') && trim((string) $value) !== '—';
    $normalizeText = fn ($text) => trim(str_replace(['###', '**'], '', (string) $text));
    $sectionTitle = fn ($title) => trim(str_replace(['REPORTE DE TOMOGRAFÍA COMPUTARIZADA', 'REPORTE DE TOMOGRAFIA COMPUTARIZADA'], 'DATOS DEL ESTUDIO', $normalizeText($title)));
    $isPrintableLine = function ($line) use ($filled) {
        $line = trim($line);

        if (! $filled($line)) {
            return false;
        }

        return ! preg_match('/^(Médico radiólogo|Medico radiologo|CMP|RNE):/iu', str_replace('**', '', $line));
    };

    $sections = collect(preg_split('/\n\s*---\s*\n/', (string) $report->contenido))
        ->map(function ($block) use ($sectionTitle, $normalizeText, $isPrintableLine) {
            $lines = collect(preg_split('/\R/', trim($block)))
                ->map(fn ($line) => $normalizeText($line))
                ->filter(fn ($line) => $isPrintableLine($line))
                ->values();

            $heading = $sectionTitle($lines->shift() ?? 'Informe');
            $body = $lines->implode("\n");

            return ['heading' => $heading ?: 'Informe', 'body' => trim($body)];
        })
        ->filter(fn ($section) => $section['body'] !== '')
        ->values();

    $infoRows = collect([
        ['Paciente', trim($patient->nombres.' '.$patient->apellidos), 'DNI / Edad', trim(($patient->dni ?: '').($patient->edad ? ' · '.$patient->edad.' años' : ''))],
        ['Orden', $orderCode, 'Fecha de informe', $reportDate],
        ['Estudio solicitado', $examNames, 'Contraste', $contrast],
        ['Convenio', $order->agreement?->nombre_institucion, 'Médico solicitante', $order->medicoSolicitante?->nombre_completo],
    ])->map(fn ($row) => collect([$row])->flatten()->all());
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm 13mm 12mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 9.5px; line-height: 1.34; margin: 0; }
        .report-shell { border: 1px solid #d9e2ec; min-height: 100%; }
        .header { background: #102a43; color: #ffffff; padding: 12px 16px 11px; }
        .brand-table, .info-grid, .signature-table { width: 100%; border-collapse: collapse; }
        .brand-table td { vertical-align: middle; padding: 0; }
        .brand-mark { border: 1px solid rgba(255,255,255,.45); color: #ffffff; display: inline-block; font-size: 15px; font-weight: 700; height: 32px; line-height: 30px; margin-right: 9px; text-align: center; width: 32px; }
        .brand-name { display: inline-block; font-size: 15px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; }
        .brand-subtitle { color: #dbeafe; display: block; font-size: 7.5px; font-weight: 400; letter-spacing: .18em; margin-top: 1px; text-transform: uppercase; }
        .document-chip { border-left: 3px solid #38bdf8; display: inline-block; font-size: 8px; letter-spacing: .12em; padding-left: 8px; text-transform: uppercase; }
        .title-band { border-bottom: 2px solid #38bdf8; padding: 10px 16px 8px; }
        .title { color: #0f172a; font-size: 14px; font-weight: 700; letter-spacing: .03em; line-height: 1.18; margin: 0; text-align: center; text-transform: uppercase; }
        .subtitle { color: #64748b; font-size: 8px; letter-spacing: .10em; margin: 3px 0 0; text-align: center; text-transform: uppercase; }
        .content-wrap { padding: 10px 16px 12px; }
        .info-grid { margin-bottom: 9px; }
        .info-grid td { border: 1px solid #e2e8f0; padding: 4px 6px; vertical-align: top; width: 50%; }
        .label { color: #64748b; display: block; font-size: 7px; font-weight: 700; letter-spacing: .08em; margin-bottom: 1px; text-transform: uppercase; }
        .value { color: #111827; font-size: 9px; font-weight: 700; }
        .section { border-left: 3px solid #38bdf8; margin-bottom: 7px; page-break-inside: avoid; }
        .section-heading { background: #eff6ff; color: #102a43; font-size: 8.3px; font-weight: 700; letter-spacing: .09em; padding: 4px 7px; text-transform: uppercase; }
        .section-body { color: #1f2937; font-size: 9.4px; line-height: 1.38; padding: 5px 7px 3px; white-space: pre-line; }
        .signature-table { margin-top: 9px; page-break-inside: avoid; }
        .signature-table td { vertical-align: bottom; width: 50%; }
        .signature-note { color: #64748b; font-size: 7.4px; line-height: 1.28; padding-right: 16px; }
        .signature { text-align: center; }
        .signature-box { height: 42px; margin-bottom: 2px; }
        .signature img { max-height: 42px; max-width: 190px; }
        .line { border-top: 1px solid #0f172a; margin: 0 auto 4px; width: 220px; }
        .doctor-name { color: #0f172a; font-size: 9.4px; font-weight: 700; text-transform: uppercase; }
        .doctor-code { color: #475569; font-size: 7.8px; margin-top: 1px; }
        .footer { border-top: 1px solid #e2e8f0; color: #64748b; font-size: 7px; margin-top: 8px; padding-top: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class="report-shell">
        <div class="header">
            <table class="brand-table">
                <tr>
                    <td><span class="brand-mark">T</span><span class="brand-name">Tomografías 2026<span class="brand-subtitle">Centro de diagnóstico por imágenes</span></span></td>
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
                @foreach($infoRows as $row)
                    @php [$labelA, $valueA, $labelB, $valueB] = $row; @endphp
                    @if($filled($valueA) || $filled($valueB))
                        <tr>
                            @if($filled($valueA))<td><span class="label">{{ $labelA }}</span><span class="value">{{ $valueA }}</span></td>@endif
                            @if($filled($valueB))<td><span class="label">{{ $labelB }}</span><span class="value">{{ $valueB }}</span></td>@endif
                        </tr>
                    @endif
                @endforeach
            </table>
            @foreach($sections as $section)
                <div class="section"><div class="section-heading">{{ $section['heading'] }}</div><div class="section-body">{{ $section['body'] }}</div></div>
            @endforeach
            <table class="signature-table"><tr><td><div class="signature-note">Documento confidencial de uso médico. Correlacionar con antecedentes clínicos y estudios complementarios.</div></td><td><div class="signature"><div class="signature-box">@if($signature && file_exists($signature))<img src="{{ $signature }}" alt="Firma del médico">@endif</div><div class="line"></div>@if($filled($doctor?->nombre_completo))<div class="doctor-name">{{ $doctor->nombre_completo }}</div>@endif@if($filled($doctor?->cmp) || $filled($doctor?->rne))<div class="doctor-code">@if($filled($doctor?->cmp)) CMP: {{ $doctor->cmp }} @endif @if($filled($doctor?->rne)) &nbsp; RNE: {{ $doctor->rne }} @endif</div>@endif</div></td></tr></table>
            <div class="footer">{{ $orderCode }} · Generado por Tomografías 2026</div>
        </div>
    </div>
</body>
</html>
