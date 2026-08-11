<table>
    <thead><tr><th>Fecha</th><th>Orden</th><th>Paciente</th><th>Convenio</th><th>Pago</th><th>Tipo comprobante</th><th>N° comprobante</th><th>Placas</th><th>Iopamidol</th><th>Total</th></tr></thead>
    <tbody>
        @forelse($sheetOrders as $order)
            @php $platesUsed = (float) ($order->admissionForm?->data['delivery_quantities']['PLACAS'] ?? $order->admissionForm?->data['plates_count'] ?? 0); $iopamidolUsed = (float) $order->consumables->filter(fn ($item) => str_contains(strtolower($item->reagent->nombre ?? ''), 'iopamidol'))->sum('cantidad'); @endphp
            <tr><td>{{ $order->fecha_orden->format('d/m/Y H:i') }}</td><td>{{ $order->codigo_orden ?? '#'.$order->id }}</td><td>{{ $order->patient->nombres ?? '' }} {{ $order->patient->apellidos ?? '' }}</td><td>{{ $order->agreement->nombre_institucion ?? '—' }}</td><td>{{ $order->tipo_pago ?? '—' }}</td><td>{{ $order->tipo_comprobante ?? '—' }}</td><td>{{ $order->numero_comprobante ?? '—' }}</td><td class="right">{{ number_format($platesUsed, 2) }}</td><td class="right">{{ number_format($iopamidolUsed, 2) }}</td><td class="right">S/ {{ number_format($order->total, 2) }}</td></tr>
        @empty
            <tr><td colspan="10">Sin registros.</td></tr>
        @endforelse
    </tbody>
</table>
