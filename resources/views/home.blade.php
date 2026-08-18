@extends('layouts.app')

@section('content')
@php
    $formatNumber = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    $monthlyMax = max(1, ...$monthlyConsumption->flatMap(fn ($row) => [$row['plates'], $row['plate_envelopes'], $row['cds'], $row['iopamidol']])->all());
@endphp
<div class="container py-4">
    <div class="p-4 p-lg-5 mb-4 rounded-4 text-white shadow-sm" style="background: linear-gradient(135deg, #064e7a, #0f766e);">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="text-uppercase small fw-bold opacity-75 mb-2">Home · consumo generado</div>
                <h1 class="display-6 fw-bold mb-2">Dashboard de insumos utilizados</h1>
                <p class="mb-0 opacity-75">Resumen acumulado de placas RX, sobres de placas, CD e Iopamidol según fichas de ingreso y consumibles registrados en las órdenes.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="fs-1 fw-bold">{{ number_format($summary['totals']['orders']) }}</div>
                <div class="opacity-75">órdenes generadas analizadas</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($summary['cards'] as $card)
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted small fw-bold text-uppercase">{{ $card['label'] }}</span>
                            <span class="badge bg-{{ $card['accent'] }}-subtle text-{{ $card['accent'] }}">{{ $card['unit'] }}</span>
                        </div>
                        <div class="display-6 fw-bold">{{ $formatNumber($card['value']) }}</div>
                        <div class="progress mt-3" style="height: 8px;">
                            <div class="progress-bar bg-{{ $card['accent'] }}" style="width: {{ min(100, ($card['value'] / $summary['max']) * 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h2 class="h5 fw-bold mb-1">Consumo por mes</h2>
                    <p class="text-muted mb-0">Últimos meses con órdenes generadas.</p>
                </div>
                <div class="card-body px-4">
                    @forelse($monthlyConsumption as $month)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-bold mb-1"><span>{{ $month['label'] }}</span><span>{{ $formatNumber($month['plates'] + $month['plate_envelopes'] + $month['cds'] + $month['iopamidol']) }} total</span></div>
                            @foreach([['Placas RX', 'plates', 'primary'], ['Sobres', 'plate_envelopes', 'warning'], ['CD', 'cds', 'success'], ['Iopamidol', 'iopamidol', 'info']] as [$label, $key, $color])
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <div class="text-muted small" style="width: 88px;">{{ $label }}</div>
                                    <div class="progress flex-grow-1" style="height: 10px;"><div class="progress-bar bg-{{ $color }}" style="width: {{ min(100, ($month[$key] / $monthlyMax) * 100) }}%"></div></div>
                                    <div class="small fw-semibold text-end" style="width: 54px;">{{ $formatNumber($month[$key]) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">Aún no hay consumo registrado para graficar.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 fw-bold mb-1">Top consumibles</h2><p class="text-muted mb-0">Sumatoria directa de consumibles registrados.</p></div>
                <div class="card-body px-4">
                    @forelse($topConsumables as $item)
                        <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                            <div><div class="fw-semibold">{{ $item->reagent?->nombre ?? 'Consumible' }}</div><div class="small text-muted">{{ $item->orders_count }} orden(es)</div></div>
                            <div class="fw-bold text-primary">{{ $formatNumber($item->total_used) }} {{ $item->reagent?->unidad }}</div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">Sin consumibles registrados.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 fw-bold mb-1">Últimas órdenes con consumo</h2><p class="text-muted mb-0">Detalle de lo utilizado según la ficha de ingreso de cada orden.</p></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Fecha</th><th>Orden</th><th>Paciente</th><th>Convenio</th><th class="text-end">Placas RX</th><th class="text-end">Sobres</th><th class="text-end">CD</th><th class="text-end">Iopamidol</th></tr></thead>
                    <tbody>
                        @forelse($recentOrders as $row)
                            <tr>
                                <td>{{ $row['order']->fecha_orden?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td><a class="fw-bold" href="{{ route('orders.show', $row['order']) }}">{{ $row['order']->codigo_orden ?? '#'.$row['order']->id }}</a></td>
                                <td>{{ trim(($row['order']->patient->nombres ?? '').' '.($row['order']->patient->apellidos ?? '')) ?: '—' }}</td>
                                <td>{{ $row['order']->agreement->nombre_institucion ?? '—' }}</td>
                                <td class="text-end">{{ $formatNumber($row['plates']) }}</td>
                                <td class="text-end">{{ $formatNumber($row['plate_envelopes']) }}</td>
                                <td class="text-end">{{ $formatNumber($row['cds']) }}</td>
                                <td class="text-end">{{ $formatNumber($row['iopamidol']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Sin órdenes generadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
