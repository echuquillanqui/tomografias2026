@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <div class="clinic-eyebrow mb-2">Informes</div>
                <h1 class="display-6 fw-bold">Atenciones por informar</h1>
                <p class="mb-0 opacity-75">Listado de atenciones generadas desde las órdenes para completar y descargar reportes.</p>
            </div>
        </div>
    </section>

    <form class="card clinic-card p-3 mb-4" method="GET" action="{{ route('reports.index') }}">
        <div class="input-group">
            <input name="search" class="form-control" value="{{ $search }}" placeholder="Buscar por orden, DNI o paciente">
            <button class="btn btn-clinic-primary">Buscar</button>
        </div>
    </form>

    <div class="card clinic-card">
        <div class="card-body p-0">
            <table class="table table-clinic mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Paciente</th>
                        <th>Convenio</th>
                        <th>Fecha</th>
                        <th>Exámenes</th>
                        <th>Médico firmante</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="fw-bold">{{ $order->codigo_orden ?? 'Orden #'.$order->id }}</td>
                            <td>{{ $order->patient->nombres }} {{ $order->patient->apellidos }}<br><small class="text-muted">{{ $order->patient->dni }}</small></td>
                            <td>{{ $order->agreement->nombre_institucion }}</td>
                            <td>{{ $order->fecha_orden->format('d/m/Y') }}</td>
                            <td>{{ $order->order_exams_count }}</td>
                            <td>{{ $order->report?->medicoFirmante?->nombre_completo ?? $order->medicoInforme?->nombre_completo ?? '—' }}</td>
                            <td><span class="badge badge-role">{{ $order->estado }}</span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('reports.edit', $order) }}">Rellenar</a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.pdf', $order) }}" target="_blank">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5">Sin atenciones generadas por órdenes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $orders->links() }}</div>
    </div>
</div>
@endsection
