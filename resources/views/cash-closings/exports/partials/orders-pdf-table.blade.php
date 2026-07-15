<table>
    <thead><tr><th>Fecha</th><th>Orden</th><th>Paciente</th><th>Convenio</th><th>Pago</th><th>Total</th></tr></thead>
    <tbody>
        @forelse($sheetOrders as $order)
            <tr><td>{{ $order->fecha_orden->format('d/m/Y H:i') }}</td><td>{{ $order->codigo_orden ?? '#'.$order->id }}</td><td>{{ $order->patient->nombres ?? '' }} {{ $order->patient->apellidos ?? '' }}</td><td>{{ $order->agreement->nombre_institucion ?? '—' }}</td><td>{{ $order->tipo_pago ?? '—' }}</td><td class="right">S/ {{ number_format($order->total, 2) }}</td></tr>
        @empty
            <tr><td colspan="6">Sin registros.</td></tr>
        @endforelse
    </tbody>
</table>
