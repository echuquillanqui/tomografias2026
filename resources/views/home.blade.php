@extends('layouts.app')

@section('content')
@php
    $formatNumber = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    $visibleCards = collect($summary['cards'])->when($filters['product'] !== 'all', fn ($cards) => $cards->where('key', $filters['product']));
    $chartSeries = collect($summary['cards'])->when($filters['product'] !== 'all', fn ($cards) => $cards->where('key', $filters['product']))->values();
@endphp
<div class="dashboard-shell">
    <div class="container py-4 py-lg-5">
        <section class="dashboard-hero mb-4">
            <div class="hero-glow hero-glow-one"></div><div class="hero-glow hero-glow-two"></div>
            <div class="row align-items-center g-4 position-relative">
                <div class="col-lg-8">
                    <span class="hero-kicker"><span class="status-dot"></span> Panel de control</span>
                    <h1 class="hero-title mt-3 mb-2">Consumo de <span>insumos</span></h1>
                    <p class="hero-copy mb-0">Visualiza tendencias, compara periodos y detecta los productos con mayor movimiento.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="hero-stat"><strong>{{ number_format($summary['totals']['orders']) }}</strong><span>órdenes analizadas</span></div>
                    <div class="hero-period mt-2">{{ $filters['period_label'] }}</div>
                </div>
            </div>
        </section>

        <form id="dashboardFilters" method="GET" action="{{ route('home') }}" class="filter-panel mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div><span class="filter-eyebrow">Explorar datos</span><h2 class="h5 fw-bold mb-0">Filtros del dashboard</h2></div>
                <button type="button" class="btn btn-sm btn-light rounded-pill px-3" id="resetFilters">↻ Restablecer</button>
            </div>
            <div class="row g-3 align-items-end">
                <div class="col-md-3"><label class="filter-label" for="start_date">Desde</label><input class="form-control dashboard-control" type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] }}" max="{{ $filters['end_date'] }}"></div>
                <div class="col-md-3"><label class="filter-label" for="end_date">Hasta</label><input class="form-control dashboard-control" type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] }}" min="{{ $filters['start_date'] }}"></div>
                <div class="col-md-4"><label class="filter-label" for="product">Producto</label><select class="form-select dashboard-control" id="product" name="product">@foreach($filters['products'] as $value => $label)<option value="{{ $value }}" @selected($filters['product'] === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2 d-grid"><button class="btn btn-dashboard" type="submit"><span>Aplicar</span> →</button></div>
            </div>
            <div class="quick-ranges mt-3"><span>Rangos rápidos:</span><button type="button" data-range="today">Hoy</button><button type="button" data-range="7">7 días</button><button type="button" data-range="30">30 días</button><button type="button" data-range="month">Este mes</button></div>
        </form>

        <div class="row g-3 mb-4">
            @foreach($visibleCards as $card)
                <div class="{{ $visibleCards->count() === 1 ? 'col-12' : 'col-md-6 col-xl-3' }}">
                    <article class="metric-card metric-{{ $card['accent'] }} h-100">
                        <div class="metric-top"><span class="metric-icon">{{ $card['icon'] }}</span><span class="metric-unit">{{ $card['unit'] }}</span></div>
                        <p class="metric-label">{{ $card['label'] }}</p>
                        <div class="d-flex align-items-end justify-content-between gap-2"><strong class="metric-value">{{ $formatNumber($card['value']) }}</strong>
                            @if($card['change'] !== null)<span class="trend {{ $card['change'] >= 0 ? 'trend-up' : 'trend-down' }}">{{ $card['change'] >= 0 ? '↑' : '↓' }} {{ number_format(abs($card['change']), 1) }}%</span>@else<span class="trend trend-neutral">Sin previo</span>@endif
                        </div>
                        <div class="metric-footer">Comparado con el periodo anterior</div>
                    </article>
                </div>
            @endforeach
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-8"><section class="chart-card h-100"><div class="section-heading"><div><span class="section-kicker">Evolución temporal</span><h2>Consumo por mes</h2></div><span class="active-filter">{{ $filters['product_label'] }}</span></div><div class="chart-wrap"><canvas id="consumptionChart" aria-label="Gráfico de consumo mensual"></canvas></div></section></div>
            <div class="col-xl-4"><section class="chart-card h-100"><div class="section-heading"><div><span class="section-kicker">Distribución</span><h2>Mix de productos</h2></div></div><div class="donut-wrap"><canvas id="mixChart" aria-label="Distribución del consumo"></canvas><div class="donut-center"><strong>{{ $formatNumber($chartSeries->sum('value')) }}</strong><span>consumo total</span></div></div><div id="mixLegend" class="mix-legend"></div></section></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-5"><section class="chart-card h-100"><div class="section-heading"><div><span class="section-kicker">Ranking del periodo</span><h2>Top consumibles</h2></div></div><div class="top-list">@forelse($topConsumables as $item)<div class="top-item"><span class="top-rank">{{ $loop->iteration }}</span><div class="flex-grow-1"><div class="d-flex justify-content-between gap-2"><strong>{{ $item->reagent?->nombre ?? 'Consumible' }}</strong><strong class="top-value">{{ $formatNumber($item->total_used) }} {{ $item->reagent?->unidad }}</strong></div><small>{{ $item->orders_count }} {{ $item->orders_count == 1 ? 'orden' : 'órdenes' }}</small><div class="top-bar"><span style="width: {{ ($item->total_used / max(1, $topConsumables->max('total_used'))) * 100 }}%"></span></div></div></div>@empty<div class="empty-state">◎<span>Sin consumibles en este periodo</span></div>@endforelse</div></section></div>
            <div class="col-xl-7"><section class="chart-card h-100"><div class="section-heading"><div><span class="section-kicker">Actividad reciente</span><h2>Últimas órdenes</h2></div><a href="{{ route('orders.index') }}" class="view-all">Ver todas →</a></div><div class="table-responsive"><table class="table dashboard-table align-middle mb-0"><thead><tr><th>Orden / fecha</th><th>Paciente</th><th class="text-end">Placas</th><th class="text-end">Sobres</th><th class="text-end">CD</th><th class="text-end">Iopamidol</th></tr></thead><tbody>@forelse($recentOrders as $row)<tr><td><a href="{{ route('orders.show', $row['order']) }}">{{ $row['order']->codigo_orden ?? '#'.$row['order']->id }}</a><small>{{ $row['order']->fecha_orden?->format('d/m/Y · H:i') }}</small></td><td>{{ trim(($row['order']->patient->nombres ?? '').' '.($row['order']->patient->apellidos ?? '')) ?: '—' }}<small>{{ $row['order']->agreement->nombre_institucion ?? 'Sin convenio' }}</small></td><td class="text-end fw-semibold">{{ $formatNumber($row['plates']) }}</td><td class="text-end fw-semibold">{{ $formatNumber($row['plate_envelopes']) }}</td><td class="text-end fw-semibold">{{ $formatNumber($row['cds']) }}</td><td class="text-end fw-semibold">{{ $formatNumber($row['iopamidol']) }}</td></tr>@empty<tr><td colspan="6"><div class="empty-state">⌁<span>No hay órdenes para los filtros elegidos</span></div></td></tr>@endforelse</tbody></table></div></section></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    :root{--dash-ink:#132238;--dash-muted:#6b7b91;--dash-border:#e7edf5}.dashboard-shell{background:#f4f7fb;min-height:calc(100vh - 74px);color:var(--dash-ink);margin-top:-3rem;margin-bottom:-3rem}.dashboard-hero{position:relative;overflow:hidden;padding:2rem 2.25rem;border-radius:24px;color:white;background:linear-gradient(120deg,#112d4e 0%,#075985 50%,#0f766e 100%);box-shadow:0 18px 45px rgba(15,73,107,.2)}.hero-glow{position:absolute;border-radius:50%;filter:blur(3px);opacity:.2}.hero-glow-one{width:260px;height:260px;background:#5eead4;right:-55px;top:-130px}.hero-glow-two{width:170px;height:170px;background:#38bdf8;left:38%;bottom:-130px}.hero-kicker{font-size:.75rem;text-transform:uppercase;letter-spacing:.14em;font-weight:800;background:rgba(255,255,255,.12);padding:.5rem .8rem;border-radius:99px}.status-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#5eead4;margin-right:.4rem;box-shadow:0 0 0 4px rgba(94,234,212,.14)}.hero-title{font-weight:800;font-size:clamp(2rem,4vw,3.25rem);letter-spacing:-.045em}.hero-title span{color:#7dd3fc}.hero-copy{color:#d9edf6;max-width:650px;font-size:1.04rem}.hero-stat{display:inline-flex;flex-direction:column;align-items:flex-end}.hero-stat strong{font-size:3rem;line-height:1;font-weight:800}.hero-stat span,.hero-period{color:#cce7ef;font-size:.85rem}.filter-panel,.chart-card{background:#fff;border:1px solid var(--dash-border);border-radius:20px;box-shadow:0 7px 25px rgba(30,55,90,.055)}.filter-panel{padding:1.35rem 1.5rem}.filter-eyebrow,.section-kicker{display:block;color:#0e7490;font-size:.68rem;text-transform:uppercase;letter-spacing:.13em;font-weight:800;margin-bottom:.2rem}.filter-label{font-size:.75rem;font-weight:800;color:#526379;margin:0 0 .4rem .15rem}.dashboard-control{border:1px solid #dce5ef;border-radius:11px;min-height:44px;font-size:.9rem}.dashboard-control:focus{border-color:#0891b2;box-shadow:0 0 0 .22rem rgba(8,145,178,.12)}.btn-dashboard{background:linear-gradient(135deg,#0e7490,#0f766e);color:white;border:0;border-radius:11px;min-height:44px;font-weight:700}.btn-dashboard:hover{color:white;transform:translateY(-1px)}.quick-ranges{display:flex;gap:.45rem;align-items:center;flex-wrap:wrap;font-size:.74rem;color:var(--dash-muted)}.quick-ranges button{border:0;border-radius:99px;padding:.3rem .7rem;background:#edf6f8;color:#087188;font-weight:700}.metric-card{position:relative;overflow:hidden;padding:1.35rem;border:1px solid var(--dash-border);border-radius:19px;background:white;box-shadow:0 7px 25px rgba(30,55,90,.05);transition:.2s}.metric-card:hover{transform:translateY(-4px);box-shadow:0 14px 30px rgba(30,55,90,.1)}.metric-card:after{content:'';position:absolute;width:95px;height:95px;border-radius:50%;right:-45px;bottom:-48px;background:var(--accent);opacity:.1}.metric-blue{--accent:#2563eb}.metric-amber{--accent:#e59514}.metric-green{--accent:#059669}.metric-purple{--accent:#7c3aed}.metric-top{display:flex;justify-content:space-between;align-items:center}.metric-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:color-mix(in srgb,var(--accent) 12%,white);color:var(--accent);font-size:1.25rem}.metric-unit{font-size:.69rem;text-transform:uppercase;letter-spacing:.08em;color:#8290a3;font-weight:800}.metric-label{color:#64748b;font-size:.82rem;font-weight:700;margin:1rem 0 .15rem}.metric-value{font-size:2.25rem;letter-spacing:-.05em}.trend{font-size:.7rem;font-weight:800;border-radius:99px;padding:.3rem .5rem}.trend-up{color:#047857;background:#d1fae5}.trend-down{color:#be123c;background:#ffe4e6}.trend-neutral{color:#64748b;background:#f1f5f9}.metric-footer{border-top:1px solid #edf1f6;margin-top:1rem;padding-top:.75rem;color:#91a0b2;font-size:.7rem}.chart-card{padding:1.4rem}.section-heading{display:flex;justify-content:space-between;align-items:start;gap:1rem;margin-bottom:1.15rem}.section-heading h2{font-size:1.05rem;font-weight:800;margin:0}.active-filter{background:#e8f6f8;color:#0e7490;border-radius:99px;padding:.4rem .7rem;font-size:.7rem;font-weight:800}.chart-wrap{position:relative;height:310px}.donut-wrap{height:225px;position:relative;display:flex;justify-content:center}.donut-center{position:absolute;inset:0;pointer-events:none;display:flex;align-items:center;justify-content:center;flex-direction:column}.donut-center strong{font-size:1.45rem}.donut-center span{font-size:.65rem;color:#8997a9}.mix-legend{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.6rem}.legend-item{display:flex;align-items:center;gap:.45rem;font-size:.72rem;color:#5f6f83}.legend-dot{width:8px;height:8px;border-radius:3px}.top-list{display:flex;flex-direction:column;gap:.95rem}.top-item{display:flex;gap:.8rem;align-items:center}.top-rank{display:grid;place-items:center;flex:0 0 31px;height:31px;border-radius:9px;background:#eff7f9;color:#0e7490;font-weight:800;font-size:.75rem}.top-item strong{font-size:.78rem}.top-item small{color:#93a0b0;font-size:.68rem}.top-value{color:#0e7490;white-space:nowrap}.top-bar{height:4px;background:#edf2f7;border-radius:9px;margin-top:.35rem;overflow:hidden}.top-bar span{display:block;height:100%;border-radius:9px;background:linear-gradient(90deg,#06b6d4,#0f766e)}.view-all{font-size:.75rem;font-weight:800;color:#0e7490;text-decoration:none}.dashboard-table thead th{border:0;background:#f7f9fc;color:#8491a2;font-size:.64rem;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap}.dashboard-table tbody td{border-color:#edf1f6;font-size:.75rem;padding:.75rem}.dashboard-table td a{display:block;color:#0e7490;font-weight:800;text-decoration:none}.dashboard-table td small{display:block;color:#95a1b0;margin-top:.15rem}.empty-state{min-height:170px;display:flex;align-items:center;justify-content:center;gap:.6rem;flex-direction:column;color:#9aa7b8;font-size:1.5rem}.empty-state span{font-size:.8rem}@media(max-width:767px){.dashboard-hero{padding:1.5rem}.hero-stat{align-items:flex-start}.filter-panel{padding:1rem}.chart-card{padding:1rem}.quick-ranges span{width:100%}.dashboard-shell{margin-top:-3rem}}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('dashboardFilters'), start = document.getElementById('start_date'), end = document.getElementById('end_date');
    start.addEventListener('change', () => { end.min = start.value; }); end.addEventListener('change', () => { start.max = end.value; });
    document.getElementById('product').addEventListener('change', () => form.requestSubmit());
    const localDate = date => { const offset = date.getTimezoneOffset(); return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 10); };
    document.querySelectorAll('[data-range]').forEach(button => button.addEventListener('click', () => { const today = new Date(), from = new Date(today), range = button.dataset.range; if (range === 'month') from.setDate(1); else if (range !== 'today') from.setDate(today.getDate() - Number(range) + 1); start.value = localDate(from); end.value = localDate(today); form.requestSubmit(); }));
    document.getElementById('resetFilters').addEventListener('click', () => { window.location.href = @json(route('home')); });

    if (typeof Chart === 'undefined') return;
    Chart.defaults.font.family = "Nunito, sans-serif"; Chart.defaults.color = '#718096';
    const months = @json($monthlyConsumption->pluck('label'));
    const allSeries = @json(collect($summary['cards'])->map(fn ($card) => ['key' => $card['key'], 'label' => $card['label'], 'value' => $card['value']]));
    const selected = @json($filters['product']);
    const colors = {plates:'#2563eb',plate_envelopes:'#e59514',cds:'#059669',iopamidol:'#7c3aed'};
    const chartSeries = selected === 'all' ? allSeries : allSeries.filter(item => item.key === selected);
    new Chart(document.getElementById('consumptionChart'), {type:'line',data:{labels:months,datasets:chartSeries.map(item => ({label:item.label,data:@json($monthlyConsumption).map(row => Number(row[item.key])),borderColor:colors[item.key],backgroundColor:colors[item.key]+'18',fill:true,tension:.38,borderWidth:2.5,pointRadius:3,pointHoverRadius:6}))},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:7,padding:18}}},scales:{x:{grid:{display:false},border:{display:false}},y:{beginAtZero:true,grid:{color:'#edf1f6'},border:{display:false}}}}});
    const mix = new Chart(document.getElementById('mixChart'), {type:'doughnut',data:{labels:chartSeries.map(i=>i.label),datasets:[{data:chartSeries.map(i=>Number(i.value)),backgroundColor:chartSeries.map(i=>colors[i.key]),borderWidth:0,hoverOffset:5}]},options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}});
    document.getElementById('mixLegend').innerHTML = chartSeries.map((item,index) => `<div class="legend-item"><span class="legend-dot" style="background:${colors[item.key]}"></span><span>${item.label}</span><strong class="ms-auto">${Number(item.value).toLocaleString('es-PE')}</strong></div>`).join('');
});
</script>
@endpush
