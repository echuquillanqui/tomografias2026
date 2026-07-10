@php
    $setting = $setting ?? \App\Models\SystemSetting::current();
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
    $removePlaceholders = fn ($text) => trim(collect(preg_split('/\R/', (string) $text))->reject(fn ($line) => str_contains($line, '[') && str_contains($line, ']'))->implode("\n"));
    $fallbackSections = collect();

    if (! $filled($report?->tecnica) || ! $filled($report?->informe) || ! $filled($report?->impresion)) {
        $blocks = collect(preg_split('/\n\s*---\s*\n/', (string) $report?->contenido))
            ->map(fn ($block) => trim($block))
            ->filter();

        $fallbackSections = $blocks->mapWithKeys(function ($block) use ($normalizeText, $removePlaceholders) {
            $lines = collect(preg_split('/\R/', $block))->map(fn ($line) => $normalizeText($line))->values();
            $heading = mb_strtoupper($lines->first() ?? '', 'UTF-8');
            $body = $removePlaceholders($lines->slice(1)->implode("\n"));

            if (str_contains($heading, 'TÉCNICA') || str_contains($heading, 'TECNICA')) {
                return ['tecnica' => $body];
            }

            if (str_contains($heading, 'HALLAZGOS') || str_contains($heading, 'INFORME')) {
                return ['informe' => $body];
            }

            if (str_contains($heading, 'IMPRESIÓN') || str_contains($heading, 'IMPRESION')) {
                return ['impresion' => $body];
            }

            if (str_contains($heading, 'RECOMENDACIONES') || str_contains($heading, 'NOTAS')) {
                return ['recomendaciones' => $body];
            }

            return [];
        });
    }

    $sections = collect([
        ['heading' => 'TÉCNICA', 'body' => $report?->tecnica ?: $fallbackSections->get('tecnica')],
        ['heading' => 'INFORME', 'body' => $report?->informe ?: $fallbackSections->get('informe')],
        ['heading' => 'IMPRESIÓN DIAGNÓSTICA', 'body' => $report?->impresion ?: $fallbackSections->get('impresion')],
        ['heading' => 'RECOMENDACIONES / NOTAS', 'body' => $report?->recomendaciones ?: $fallbackSections->get('recomendaciones')],
    ])->map(fn ($section) => ['heading' => $section['heading'], 'body' => $removePlaceholders($section['body'])])
      ->filter(fn ($section) => $filled($section['body']))->values();

    $infoRows = collect([
        ['Paciente', trim($patient->nombres.' '.$patient->apellidos), 'Edad', $patient->edad ? $patient->edad.' años' : null, 'DNI', $patient->dni],
        ['Estudio', $examNames, 'Contraste', $contrast, 'Fecha', $reportDate],
        ['Médico solicitante', $order->medicoSolicitante?->nombre_completo, 'Orden', $orderCode, 'Convenio', $order->agreement?->nombre_institucion],
    ]);
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 6mm 7mm; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.16; margin: 0; }
        .letter { border: 1px solid #222; padding: 5mm 6mm 4mm; }
        .masthead { border-bottom: 1px solid #111; padding-bottom: 3px; }
        .brand-table, .info-table, .signature-table { border-collapse: collapse; width: 100%; }
        .brand-table td { vertical-align: middle; }
        .brand-logo { max-height: 28px; max-width: 82px; vertical-align: middle; }
        .brand-name { font-size: 11px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
        .brand-meta { color: #333; font-size: 6.2px; line-height: 1.15; margin-top: 1px; }
        .doc-type { border: 1px solid #111; display: inline-block; font-size: 6.3px; font-weight: 700; letter-spacing: .08em; padding: 2px 5px; text-transform: uppercase; }
        .title { font-size: 10.5px; font-weight: 700; margin: 4px 0 3px; text-align: center; text-transform: uppercase; }
        .info-table { border: 1px solid #111; margin-bottom: 4px; }
        .info-table td { border: 1px solid #777; padding: 2px 4px; vertical-align: top; width: 33.33%; }
        .label { display: block; font-size: 6px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .value { display: block; font-size: 7.5px; font-weight: 400; margin-top: 0; }
        .section { margin-bottom: 3px; page-break-inside: auto; }
        .section-heading { background: #e9e9e9; border: 1px solid #111; font-size: 7px; font-weight: 700; letter-spacing: .06em; padding: 1.5px 4px; text-transform: uppercase; page-break-after: avoid; }
        .section-body { border: 1px solid #777; border-top: 0; font-size: 7.8px; line-height: 1.16; min-height: 11px; padding: 2.5px 4px; white-space: pre-line; }
        .section.impression .section-heading { background: #dcdcdc; }
        .section.impression .section-body { font-weight: 700; }
        .signature-table { margin-top: 4px; page-break-inside: avoid; }
        .signature-table td { vertical-align: bottom; width: 50%; }
        .note { color: #333; font-size: 6px; line-height: 1.1; padding-right: 14px; }
        .signature { text-align: center; }
        .signature-box { height: 24px; margin-bottom: 0; }
        .signature img { max-height: 24px; max-width: 145px; }
        .line { border-top: 1px solid #111; margin: 0 auto 2px; width: 180px; }
        .doctor-name { font-size: 7.5px; font-weight: 700; text-transform: uppercase; }
        .doctor-code { font-size: 6.4px; margin-top: 0; }
        .footer { border-top: 1px solid #777; color: #333; font-size: 5.9px; margin-top: 3px; padding-top: 2px; text-align: center; }
    </style>
</head>
<body>
    <div class="letter">
        <div class="masthead">
            <table class="brand-table">
                <tr>
                    <td>
                        @if($setting->logo_path && file_exists(storage_path('app/public/'.$setting->logo_path)))
                            <img class="brand-logo" src="{{ storage_path('app/public/'.$setting->logo_path) }}" alt="Logo">
                        @else
                            <span class="brand-name">{{ $setting->razon_social }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="brand-name">{{ $setting->razon_social }}</div>
                        <div class="brand-meta">{{ collect([$setting->ruc ? 'RUC '.$setting->ruc : null, $setting->direccion, $setting->telefono ? 'Tel. '.$setting->telefono : null])->filter()->implode(' · ') }}</div>
                    </td>
                    <td style="text-align: right;"><span class="doc-type">Informe médico</span></td>
                </tr>
            </table>
        </div>
        <h1 class="title">{{ $report?->titulo ?: 'Informe de tomografía computarizada' }}</h1>
        <table class="info-table">
            @foreach($infoRows as $row)
                <tr>
                    @foreach(collect($row)->chunk(2) as $cell)
                        @if($filled($cell->get(1)))
                            <td><span class="label">{{ $cell->get(0) }}</span><span class="value">{{ $cell->get(1) }}</span></td>
                        @else
                            <td></td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </table>
        @foreach($sections as $section)
            <div class="section {{ $section['heading'] === 'IMPRESIÓN DIAGNÓSTICA' ? 'impression' : '' }}">
                <div class="section-heading">{{ $section['heading'] }}</div>
                <div class="section-body">{{ $section['body'] }}</div>
            </div>
        @endforeach
        <table class="signature-table">
            <tr>
                <td><div class="note">Documento confidencial de uso médico. Correlacionar con antecedentes clínicos y estudios complementarios.</div></td>
                <td>
                    <div class="signature">
                        <div class="signature-box">@if($signature && file_exists($signature))<img src="{{ $signature }}" alt="Firma del médico">@endif</div>
                        <div class="line"></div>
                        @if($filled($doctor?->nombre_completo))<div class="doctor-name">{{ $doctor->nombre_completo }}</div>@endif
                        @if($filled($doctor?->cmp) || $filled($doctor?->rne))<div class="doctor-code">@if($filled($doctor?->cmp))CMP: {{ $doctor->cmp }}@endif @if($filled($doctor?->rne)) RNE: {{ $doctor->rne }}@endif</div>@endif
                    </div>
                </td>
            </tr>
        </table>
        <div class="footer">{{ $orderCode }} · {{ $setting->razon_social }}@if($setting->direccion) · {{ $setting->direccion }}@endif @if($setting->telefono) · Tel. {{ $setting->telefono }}@endif</div>
    </div>
</body>
</html>
