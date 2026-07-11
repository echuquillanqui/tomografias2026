@php
    $setting = $setting ?? \App\Models\SystemSetting::current();
    $report = $order->report;
    $doctor = $report?->medicoFirmante;
    $signature = $doctor?->firma_path ? storage_path('app/public/'.$doctor->firma_path) : null;
    $patient = $order->patient;
    $examNames = $order->orderExams->pluck('exam.nombre_examen')->filter()->implode(', ');
    $contrast = $order->orderExams->contains('tipo_contraste', 'Con contraste') ? 'Con contraste' : 'Sin contraste';
    $orderCode = $order->codigo_orden ?? 'Orden #'.$order->id;
    $reportDate = optional($report?->updated_at ?? $order->fecha_orden)->format('d/m/Y');

    $filled = fn ($value) => filled($value) && ! str_contains((string) $value, '[') && trim((string) $value) !== '—';
    $cleanText = function ($text) {
        $text = trim(str_replace(['###', '**'], '', (string) $text));

        return trim(collect(preg_split('/\R/', $text))
            ->reject(fn ($line) => str_contains($line, '[') && str_contains($line, ']'))
            ->implode("\n"));
    };
    $extractSection = function ($content, array $headings) use ($cleanText) {
        $content = (string) $content;

        foreach ($headings as $heading) {
            $pattern = '/(?:^|\R)\s*(?:###\s*)?(?:\*\*)?'.preg_quote($heading, '/').'(?:\*\*)?\s*(?:\R)+(.+?)(?=(?:\R\s*---\s*\R)|(?:\R\s*(?:###\s*)?(?:\*\*)?(?:TÉCNICA|TECNICA|HALLAZGOS|INFORME|IMPRESIÓN DIAGNÓSTICA|IMPRESION DIAGNOSTICA|RECOMENDACIONES \/ NOTAS|RECOMENDACIONES|NOTAS)(?:\*\*)?\s*\R)|\z)/isu';

            if (preg_match($pattern, $content, $matches)) {
                return $cleanText($matches[1]);
            }
        }

        return null;
    };

    $fallbackSections = collect([
        'tecnica' => $extractSection($report?->contenido, ['TÉCNICA', 'TECNICA']),
        'informe' => $extractSection($report?->contenido, ['HALLAZGOS', 'INFORME']),
        'impresion' => $extractSection($report?->contenido, ['IMPRESIÓN DIAGNÓSTICA', 'IMPRESION DIAGNOSTICA']),
        'recomendaciones' => $extractSection($report?->contenido, ['RECOMENDACIONES / NOTAS', 'RECOMENDACIONES', 'NOTAS']),
    ]);

    $sections = collect([
        ['heading' => 'TÉCNICA', 'body' => $report?->tecnica ?: $fallbackSections->get('tecnica')],
        ['heading' => 'HALLAZGOS', 'body' => $report?->informe ?: $fallbackSections->get('informe')],
        ['heading' => 'IMPRESIÓN DIAGNÓSTICA', 'body' => $report?->impresion ?: $fallbackSections->get('impresion'), 'required' => true],
        ['heading' => 'RECOMENDACIONES / NOTAS', 'body' => $report?->recomendaciones ?: $fallbackSections->get('recomendaciones')],
    ])->map(function ($section) use ($cleanText) {
        $body = $cleanText($section['body']);

        if (($section['required'] ?? false) && blank($body)) {
            $body = 'Pendiente de completar impresión diagnóstica.';
        }

        return ['heading' => $section['heading'], 'body' => $body];
    })->filter(fn ($section) => filled($section['body']))->values();
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 13mm 16mm 12mm; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.32; margin: 0; }
        .letter { min-height: 265mm; padding-top: 6mm; position: relative; }
        .brand { height: 25mm; }
        .brand-logo { max-height: 23mm; max-width: 58mm; }
        .brand-name { color: #555; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .title { font-size: 16px; font-weight: 700; margin: 8mm 0 6mm; text-align: center; text-decoration: underline; text-transform: uppercase; }
        .meta { border-collapse: collapse; margin: 0 0 2mm 18mm; width: 145mm; }
        .meta td { font-size: 11px; padding: .35mm 0; vertical-align: top; }
        .meta .label { font-weight: 700; text-transform: uppercase; width: 27mm; }
        .meta .sep { font-weight: 700; width: 4mm; }
        .section { margin: 0 0 1.4mm 18mm; width: 145mm; }
        .section-heading { font-size: 11px; font-weight: 700; margin: 0 0 .8mm; text-transform: uppercase; }
        .section-body { font-size: 11px; line-height: 1.32; margin: 0; text-align: left; white-space: pre-line; }
        .section.impression { margin-top: 1.8mm; }
        .section.impression .section-body { font-weight: 700; padding-left: 6mm; }
        .signature { margin: 12mm 0 0 84mm; text-align: center; width: 55mm; }
        .signature-box { height: 18mm; }
        .signature img { max-height: 18mm; max-width: 48mm; }
        .line { border-top: 1px solid #111; margin: 0 auto 1mm; width: 48mm; }
        .doctor-name { font-size: 10px; font-style: italic; font-weight: 700; }
        .doctor-specialty { font-size: 9px; font-style: italic; font-weight: 700; }
        .doctor-code { font-size: 9px; font-style: italic; font-weight: 700; }
        .footer { bottom: 2mm; color: #777; font-size: 8px; position: absolute; right: 0; text-align: right; }
    </style>
</head>
<body>
    <div class="letter">
        <div class="brand">
            @if($setting->logo_path && file_exists(storage_path('app/public/'.$setting->logo_path)))
                <img class="brand-logo" src="{{ storage_path('app/public/'.$setting->logo_path) }}" alt="Logo">
            @else
                <div class="brand-name">{{ $setting->razon_social }}</div>
            @endif
        </div>

        <h1 class="title">INFORME TOMOGRAFICO</h1>

        <table class="meta">
            <tr><td class="label">Paciente</td><td class="sep">:</td><td>{{ trim($patient->nombres.' '.$patient->apellidos) }}</td></tr>
            <tr><td class="label">Estudio</td><td class="sep">:</td><td>{{ $examNames }} {{ $contrast ? ' - '.$contrast : '' }}</td></tr>
            <tr><td class="label">Fecha</td><td class="sep">:</td><td>{{ $reportDate }}</td></tr>
        </table>

        @foreach($sections as $section)
            <div class="section {{ $section['heading'] === 'IMPRESIÓN DIAGNÓSTICA' ? 'impression' : '' }}">
                <div class="section-heading">{{ $section['heading'] }}:</div>
                <div class="section-body">{{ $section['body'] }}</div>
            </div>
        @endforeach

        <div class="signature">
            <div class="signature-box">@if($signature && file_exists($signature))<img src="{{ $signature }}" alt="Firma del médico">@endif</div>
            <div class="line"></div>
            @if($filled($doctor?->nombre_completo))<div class="doctor-name">{{ $doctor->nombre_completo }}</div>@endif
            <div class="doctor-specialty">Médico Radiólogo</div>
            @if($filled($doctor?->cmp) || $filled($doctor?->rne))<div class="doctor-code">@if($filled($doctor?->cmp))CMP {{ $doctor->cmp }}@endif @if($filled($doctor?->rne)) - RNE {{ $doctor->rne }}@endif</div>@endif
        </div>

        <div class="footer">{{ $orderCode }}@if($setting->telefono) · {{ $setting->telefono }}@endif</div>
    </div>
</body>
</html>
