<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de venta {{ $order->sale_note_number }}</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #102f50; }
        table { width: 100%; border-collapse: collapse; }
        .frame { border: 2px solid #173b62; padding: 10px; }
        .header td { vertical-align: middle; }
        .logo { width: 115px; max-height: 70px; object-fit: contain; }
        .company { text-align: center; font-weight: bold; line-height: 1.4; }
        .document { width: 27%; border: 1px solid #222; text-align: center; }
        .document-title { background: #d7d7d7; padding: 8px; font-size: 12px; letter-spacing: 1px; }
        .document-number { background: #eee; padding: 12px; font-size: 15px; font-weight: bold; }
        .spacer { height: 24px; }
        .details { border: 1px solid #111; margin-bottom: 14px; }
        .details td { border: 1px solid #111; padding: 4px 6px; }
        .label { width: 14%; font-weight: bold; color: #173b62; }
        .value { font-weight: bold; }
        .items th, .items td { border: 1px solid #111; padding: 7px 5px; }
        .items th { background: #d5d5d5; text-align: center; font-size: 9px; }
        .items .number { text-align: center; }
        .items .money { text-align: right; white-space: nowrap; }
        .empty-row td { height: 27px; }
        .totals-label { text-align: right; font-weight: bold; background: #eee; }
        .payment { margin-top: 13px; }
        .cancelled { margin: 28px 0 12px auto; width: 42%; border: 1px solid #333; background: #ffe59b; padding: 12px; text-align: center; font-size: 22px; }
        .footer { margin-top: 18px; font-size: 10px; color: #38516b; }
        .fiscal-notice { display: inline-block; background: #ffe59b; padding: 3px 5px; font-weight: bold; line-height: 1.4; }
        .contact { float: right; }
    </style>
</head>
<body>
<div class="frame">
    <table class="header">
        <tr>
            <td style="width:22%">
                @if($setting->logo_path && file_exists(storage_path('app/public/'.$setting->logo_path)))
                    <img class="logo" src="{{ storage_path('app/public/'.$setting->logo_path) }}" alt="Logo">
                @endif
            </td>
            <td class="company">
                <div style="font-size:13px">{{ mb_strtoupper($setting->razon_social) }}</div>
                @if($setting->descripcion_empresa)<div style="font-size:10px;font-weight:normal">{{ mb_strtoupper($setting->descripcion_empresa) }}</div>@endif
                @if($setting->direccion)<div>{{ $setting->direccion }}</div>@endif
                @if($setting->ruc)<div>RUC: {{ $setting->ruc }}</div>@endif
            </td>
            <td class="document">
                <div class="document-title">NOTA DE VENTA</div>
                <div class="document-number">{{ $order->sale_note_number }}</div>
            </td>
        </tr>
    </table>

    <div class="spacer"></div>
    <table class="details">
        <tr><td class="label">Cliente:</td><td class="value">{{ mb_strtoupper($order->patient->nombres.' '.$order->patient->apellidos) }}</td><td class="label">ID Cliente:</td><td>{{ $order->patient->dni }}</td></tr>
        <tr><td class="label">Dirección:</td><td>—</td><td class="label">Teléfono:</td><td>{{ $order->patient->telefono ?: '—' }}</td></tr>
        <tr><td class="label">Fecha de emisión:</td><td class="value">{{ $order->fecha_orden->locale('es')->translatedFormat('l, d \d\e F \d\e Y') }}</td><td class="label">Tipo de moneda:</td><td>Soles (PEN)</td></tr>
        <tr><td class="label">Observaciones:</td><td colspan="3">{{ $order->observaciones ?: '—' }}</td></tr>
    </table>

    <table class="items">
        <thead><tr><th style="width:9%">CANTIDAD</th><th style="width:11%">UNIDAD DE MEDIDA</th><th>DESCRIPCIÓN</th><th style="width:18%">PRECIO UNITARIO</th><th style="width:18%">PRECIO TOTAL</th></tr></thead>
        <tbody>
        @foreach($order->orderExams as $item)
            <tr><td class="number">1</td><td class="number">1</td><td>{{ mb_strtoupper($item->exam->nombre_examen) }}@if($item->tipo_contraste) ({{ $item->tipo_contraste }})@endif</td><td class="money">S/ {{ number_format((float) $item->precio, 2) }}</td><td class="money">S/ {{ number_format((float) $item->precio, 2) }}</td></tr>
        @endforeach
        @for($i = $order->orderExams->count(); $i < 6; $i++)
            <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor
        <tr><td colspan="3"></td><td class="totals-label">IMPORTE TOTAL</td><td class="money">S/ {{ number_format((float) $order->subtotal, 2) }}</td></tr>
        @if((float) $order->descuento > 0)<tr><td colspan="3"></td><td class="totals-label">DESCUENTO</td><td class="money">- S/ {{ number_format((float) $order->descuento, 2) }}</td></tr>@endif
        <tr><td colspan="3"></td><td class="totals-label">TOTAL A PAGAR</td><td class="money"><strong>S/ {{ number_format((float) $order->total, 2) }}</strong></td></tr>
        </tbody>
    </table>

    <div class="payment"><strong>Medio de pago:</strong> {{ $order->payment_summary }}</div>
    <div class="cancelled">CANCELADO</div>
    <div class="footer"><span class="fiscal-notice">Documento no válido para crédito fiscal.<br>Si desea cambio por factura o boleta de venta, solicítelo dentro de las 72 horas.</span><span class="contact">{{ $setting->telefono ? 'CEL. '.$setting->telefono : '' }}</span></div>
</div>
</body>
</html>
