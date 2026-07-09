@php
    $report = $order->report;
    $doctor = $report?->medicoFirmante;
    $signature = $doctor?->firma_path ? storage_path('app/public/'.$doctor->firma_path) : null;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; line-height: 1.45; }
        .title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 18px; }
        .meta { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .meta td { padding: 3px 6px; vertical-align: top; }
        .content { white-space: pre-wrap; }
        .signature { margin-top: 34px; text-align: center; }
        .signature-box { height: 70px; margin-bottom: 4px; }
        .signature img { max-height: 70px; max-width: 220px; }
        .line { border-top: 1px solid #111827; width: 260px; margin: 0 auto 5px; }
    </style>
</head>
<body>
    <div class="title">{{ $report->titulo }}</div>
    <table class="meta">
        <tr><td><strong>Paciente:</strong> {{ $order->patient->nombres }} {{ $order->patient->apellidos }}</td><td><strong>DNI:</strong> {{ $order->patient->dni }}</td></tr>
        <tr><td><strong>Orden:</strong> {{ $order->codigo_orden ?? 'Orden #'.$order->id }}</td><td><strong>Fecha:</strong> {{ $order->fecha_orden->format('d/m/Y') }}</td></tr>
        <tr><td><strong>Convenio:</strong> {{ $order->agreement->nombre_institucion }}</td><td><strong>Médico solicitante:</strong> {{ $order->medicoSolicitante?->nombre_completo ?? '—' }}</td></tr>
    </table>

    <div class="content">{{ $report->contenido }}</div>

    <div class="signature">
        <div class="signature-box">
            @if($signature && file_exists($signature))
                <img src="{{ $signature }}" alt="Firma del médico">
            @endif
        </div>
        <div class="line"></div>
        <strong>{{ $doctor?->nombre_completo ?? '' }}</strong><br>
        @if($doctor?->cmp) CMP: {{ $doctor->cmp }} @endif
        @if($doctor?->rne) &nbsp; RNE: {{ $doctor->rne }} @endif
    </div>
</body>
</html>
