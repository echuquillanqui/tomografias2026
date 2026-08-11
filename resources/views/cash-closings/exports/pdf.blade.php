<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        .report-header { background: #0f766e; color: #fff; padding: 14px 16px; border-radius: 5px; }
        h1 { margin: 0 0 4px; font-size: 20px; letter-spacing: .3px; }
        h2 { margin: 16px 0 6px; padding: 6px 8px; font-size: 12px; color: #0f766e; border-left: 4px solid #14b8a6; background: #f0fdfa; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #94a3b8; padding: 5px 4px; vertical-align: middle; }
        th { background: #0f766e; color: #fff; text-align: center; font-size: 8px; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        .summary td { font-weight: bold; border-color: #99f6e4; }
        .summary td:nth-child(odd) { color: #115e59; background: #ccfbf1; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="report-header"><h1>Reporte de atenciones</h1><div>Cuadre de caja · Periodo {{ $periods[$period] }}: {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</div></div>

    <table class="summary">
        <tr><td>Ingresos efectivo</td><td class="right">S/ {{ number_format($cashIncome, 2) }}</td><td>Egresos efectivo</td><td class="right">S/ {{ number_format($expenseTotal, 2) }}</td></tr>
        <tr><td>Saldo efectivo</td><td class="right">S/ {{ number_format($cashBalance, 2) }}</td><td>Yape/Plin</td><td class="right">S/ {{ number_format($yapePlinIncome, 2) }}</td></tr>
        <tr><td>Transferencias</td><td class="right">S/ {{ number_format($transferIncome, 2) }}</td><td>Total ingresos</td><td class="right">S/ {{ number_format($incomeTotal, 2) }}</td></tr>
        <tr><td>Placas inicial</td><td class="right">{{ $plateSummary['initial'] }}</td><td>Placas final</td><td class="right">{{ $plateSummary['final'] }}</td></tr>
        <tr><td>Iopamidol utilizado</td><td class="right">{{ number_format($iopamidolSummary['delivered'], 2) }}</td><td>Iopamidol final</td><td class="right">{{ number_format($iopamidolSummary['final'], 2) }}</td></tr>
    </table>

    <h2>Cuadre diario - placas</h2>
    <table><thead><tr><th>Fecha</th><th>Paciente</th><th>Tomografía</th><th>Convenio / Institución</th><th>Total</th><th>Placas usadas</th><th>Saldo placas</th><th>Iopamidol usado</th><th>Saldo Iopamidol</th><th>Gasto</th><th>Monto</th></tr></thead><tbody>@php $plateRunning = $plateSummary['initial']; $iopamidolRunning = $iopamidolSummary['initial']; $maxRows = max($orders->count(), $expenses->count()); @endphp @for($i = 0; $i < $maxRows; $i++) @php $order = $orders->values()->get($i); $expense = $expenses->values()->get($i); $plates = $order ? (float) (($order->admissionForm?->data['delivery_quantities']['PLACAS'] ?? $order->admissionForm?->data['plates_count'] ?? 0)) : 0; $iopamidol = $order ? (float) $order->consumables->filter(fn ($item) => str_contains(strtolower($item->reagent->nombre ?? ''), 'iopamidol'))->sum('cantidad') : 0; $plateRunning -= $plates; $iopamidolRunning -= $iopamidol; @endphp <tr><td>{{ $order?->fecha_orden?->format('d/m/Y') }}</td><td>{{ $order ? trim(($order->patient->nombres ?? '').' '.($order->patient->apellidos ?? '')) : '' }}</td><td>{{ $order?->orderExams?->pluck('exam.nombre_examen')->filter()->join(' + ') }}</td><td>{{ $order?->agreement?->nombre_institucion ?? '' }}</td><td class="right">{{ $order ? 'S/ '.number_format($order->total, 2) : '' }}</td><td class="right">{{ $order ? number_format($plates, 2) : '' }}</td><td class="right">{{ $order ? number_format($plateRunning, 2) : '' }}</td><td class="right">{{ $order ? number_format($iopamidol, 2) : '' }}</td><td class="right">{{ $order ? number_format($iopamidolRunning, 2) : '' }}</td><td>{{ $expense->descripcion ?? '' }}</td><td class="right">{{ $expense ? 'S/ '.number_format($expense->monto, 2) : '' }}</td></tr> @endfor</tbody></table>

    <h2>Ingresos en efectivo</h2>
    @include('cash-closings.exports.partials.orders-pdf-table', ['sheetOrders' => $cashOrders])

    <h2>Egresos en efectivo</h2>
    <table>
        <thead><tr><th>Fecha</th><th>Descripción</th><th>Monto</th><th>Usuario</th></tr></thead>
        <tbody>
            @forelse($expenses as $expense)
                <tr><td>{{ $expense->fecha_egreso->format('d/m/Y') }}</td><td>{{ $expense->descripcion }}</td><td class="right">S/ {{ number_format($expense->monto, 2) }}</td><td>{{ $expense->creator->username ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="4">Sin egresos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Yape/Plin</h2>
    @include('cash-closings.exports.partials.orders-pdf-table', ['sheetOrders' => $yapePlinOrders])

    <h2>Transferencias</h2>
    @include('cash-closings.exports.partials.orders-pdf-table', ['sheetOrders' => $transferOrders])
</body>
</html>
