@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="clinic-eyebrow mb-2">Informes / Triaje</div>
        <h1 class="display-6 fw-bold">Triajes y consumibles</h1>
        <p class="mb-0 opacity-75">Consulte las órdenes y abra la plantilla correspondiente para completar el triaje y sus consumibles.</p>
    </section>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form class="card clinic-card p-3 mb-4" method="GET" action="{{ route('triajes.index') }}">
        <div class="input-group">
            <input name="search" class="form-control" value="{{ $search }}" placeholder="Buscar por orden, DNI o paciente">
            <button class="btn btn-clinic-primary">Buscar</button>
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
                        <th>Consumibles</th>
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
                            <td>
                                @forelse($order->consumables as $consumable)
                                    <div class="small">
                                        <span class="fw-semibold">{{ $consumable->reagent?->nombre ?? 'Consumible' }}:</span>
                                        {{ $consumable->cantidad }} {{ $consumable->reagent?->unidad_medida }}
                                    </div>
                                @empty
                                    <span class="text-muted small">Sin consumibles registrados</span>
                                @endforelse
                            </td>
                            <td class="text-nowrap">{{ $order->fecha_orden->format('d/m/Y H:i') }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-clinic-primary" href="{{ route('orders.triaje', $order) }}">
                                    Rellenar plantilla
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No se encontraron órdenes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</div>
@endsection
