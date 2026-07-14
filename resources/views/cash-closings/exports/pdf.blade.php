<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { margin: 0 0 4px; font-size: 22px; }
        h2 { margin: 18px 0 6px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 5px; }
        th { background: #e5e7eb; text-align: left; }
        .summary td { font-weight: bold; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>Cuadre de caja</h1>
    <div class="muted">Periodo {{ $periods[$period] }}: {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</div>

    <table class="summary">
        <tr><td>Ingresos efectivo</td><td class="right">S/ {{ number_format($cashIncome, 2) }}</td><td>Egresos efectivo</td><td class="right">S/ {{ number_format($expenseTotal, 2) }}</td></tr>
        <tr><td>Saldo efectivo</td><td class="right">S/ {{ number_format($cashBalance, 2) }}</td><td>Yape/Plin</td><td class="right">S/ {{ number_format($yapePlinIncome, 2) }}</td></tr>
        <tr><td>Transferencias</td><td class="right">S/ {{ number_format($transferIncome, 2) }}</td><td>Total ingresos</td><td class="right">S/ {{ number_format($incomeTotal, 2) }}</td></tr>
    </table>

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
