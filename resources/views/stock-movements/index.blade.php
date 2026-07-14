@extends('layouts.app')
@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex justify-content-between gap-3 flex-wrap">
            <div>
                <div class="clinic-eyebrow mb-2">Kardex</div>
                <h1 class="display-6 fw-bold">Movimientos de stock</h1>
                <p class="mb-0 opacity-75">Registra ingresos, salidas y ajustes con actualización automática.</p>
            </div>
            <button class="btn btn-clinic-primary align-self-start" data-bs-toggle="modal" data-bs-target="#create">+ Movimiento</button>
        </div>
    </section>

    <div class="card clinic-card mb-4">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Reporte de movimientos de consumibles / reactivos</h5>
                    <small class="text-muted">Consulta diaria, semanal, quincenal o mensual de acuerdo al stock registrado.</small>
                </div>
                <a class="btn btn-outline-primary" href="{{ route('stock-movements.report.download', request()->only(['period', 'date'])) }}">Descargar CSV</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('stock-movements.index') }}" class="row g-3 align-items-end mb-4">
                <div class="col-md-4">
                    <label class="form-label">Tipo de consulta</label>
                    <select name="period" class="form-select">
                        <option value="daily" @selected($report['period'] === 'daily')>Diaria</option>
                        <option value="weekly" @selected($report['period'] === 'weekly')>Semanal</option>
                        <option value="biweekly" @selected($report['period'] === 'biweekly')>Quincenal</option>
                        <option value="monthly" @selected($report['period'] === 'monthly')>Mensual</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha base</label>
                    <input name="date" type="date" class="form-control" value="{{ request('date', now()->toDateString()) }}">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-clinic-primary w-100">Consultar reporte</button>
                </div>
            </form>

            <div class="alert alert-info mb-3">
                Periodo {{ strtolower($report['label']) }}: {{ $report['from']->format('d/m/Y H:i') }} al {{ $report['to']->format('d/m/Y H:i') }}.
            </div>

            <div class="table-responsive">
                <table class="table table-clinic mb-0">
                    <thead>
                        <tr>
                            <th>Reactivo / consumible</th>
                            <th>Stock registrado</th>
                            <th>Ingresos</th>
                            <th>Salidas</th>
                            <th>Ajustes</th>
                            <th>Balance</th>
                            <th>Movimientos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['summary'] as $row)
                            <tr>
                                <td>{{ $row['reagent']->nombre }}</td>
                                <td>{{ $row['reagent']->stock_actual }} {{ $row['reagent']->unidad }}</td>
                                <td>{{ number_format($row['Ingreso'], 2) }}</td>
                                <td>{{ number_format($row['Salida'], 2) }}</td>
                                <td>{{ number_format($row['Ajuste'], 2) }}</td>
                                <td>{{ number_format($row['balance'], 2) }}</td>
                                <td>{{ $row['movements_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card clinic-card">
        <div class="card-body p-0">
            <table class="table table-clinic mb-0">
                <thead><tr><th>Fecha</th><th>Reactivo</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th></th></tr></thead>
                <tbody>
                    @foreach($movements as $m)
                        <tr><td>{{ $m->fecha_movimiento->format('d/m/Y H:i') }}</td><td>{{ $m->reagent->nombre }}</td><td><span class="badge badge-role">{{ $m->tipo_movimiento }}</span></td><td>{{ $m->cantidad }}</td><td>{{ $m->motivo ?: '—' }}</td><td class="text-end"><button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#del{{ $m->id }}">Eliminar</button></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $movements->links() }}</div>
    </div>
</div>
<div class="modal fade user-modal" id="create"><div class="modal-dialog"><form method="POST" action="{{ route('stock-movements.store') }}" class="modal-content">@csrf<div class="modal-header text-white"><h5>Movimiento</h5></div><div class="modal-body row g-3"><div class="col-12"><select name="reagent_id" class="form-select">@foreach($reagents as $r)<option value="{{ $r->id }}">{{ $r->nombre }} ({{ $r->stock_actual }} {{ $r->unidad }})</option>@endforeach</select></div><div class="col-md-6"><select name="tipo_movimiento" class="form-select"><option>Ingreso</option><option>Salida</option><option>Ajuste</option></select></div><div class="col-md-6"><input name="cantidad" type="number" step="0.01" class="form-control" placeholder="Cantidad"></div><div class="col-12"><input name="fecha_movimiento" type="datetime-local" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}"></div><div class="col-12"><input name="motivo" class="form-control" placeholder="Motivo"></div></div><div class="modal-footer"><button class="btn btn-clinic-primary">Guardar</button></div></form></div></div>
@foreach($movements as $m) @include('shared.delete',['id'=>'del'.$m->id,'action'=>route('stock-movements.destroy',$m),'name'=>$m->reagent->nombre.' '.$m->cantidad]) @endforeach
@endsection
