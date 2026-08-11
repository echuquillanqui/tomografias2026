@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="clinic-eyebrow mb-2">Informes / Triaje</div>
        <h1 class="display-6 fw-bold">Triajes y consumibles</h1>
        <p class="mb-0 opacity-75">Consulte las órdenes y registre los consumibles utilizados.</p>
    </section>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form class="card clinic-card p-3 mb-4" method="GET" action="{{ route('triajes.index') }}"
          x-data="{ allDates: {{ $allDates ? 'true' : 'false' }} }">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="triage-date">Fecha</label>
                <input id="triage-date" name="date" type="date" class="form-control" value="{{ $date }}" x-bind:disabled="allDates">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="triage-search">Orden o paciente</label>
                <input id="triage-search" name="search" class="form-control" value="{{ $search }}" placeholder="Buscar por orden, DNI o paciente">
            </div>
            <div class="col-md-2"><button class="btn btn-clinic-primary w-100">Buscar</button></div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input id="triage-all-dates" name="all_dates" value="1" type="checkbox" class="form-check-input"
                           @checked($allDates) x-model="allDates">
                    <label class="form-check-label fw-semibold" for="triage-all-dates">Buscar en todas las fechas</label>
                    <div class="form-text">Busca la orden o el paciente en todo el historial, sin aplicar la fecha.</div>
                </div>
            </div>
        </div>
    </form>

    <div class="card clinic-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Orden</th>
                        <th>Paciente</th>
                        <th>Tomografías</th>
                        <th>Fecha</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><strong class="text-primary">{{ $order->codigo_orden ?? 'Orden #'.$order->id }}</strong></td>
                            <td>
                                <div class="fw-semibold">{{ $order->patient->nombres }} {{ $order->patient->apellidos }}</div>
                                <small class="text-muted">DNI {{ $order->patient->dni }}</small>
                            </td>
                            <td>
                                {{ $order->orderExams->pluck('exam.nombre_examen')->filter()->join(', ') ?: 'Sin exámenes' }}
                            </td>
                            <td class="text-nowrap">{{ $order->fecha_orden->format('d/m/Y H:i') }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-clinic-primary" href="{{ route('orders.triaje', $order) }}">
                                    Rellenar plantilla
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">{{ $allDates ? 'No se encontraron órdenes en el historial.' : 'No se encontraron órdenes para la fecha seleccionada.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</div>
@endsection
