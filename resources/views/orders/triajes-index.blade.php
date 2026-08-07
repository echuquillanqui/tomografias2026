@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="clinic-eyebrow mb-2">Informes / Triaje</div>
        <h1 class="display-6 fw-bold">Triaje y consumibles</h1>
        <p class="mb-0 opacity-75">Revise las órdenes y modifique las cantidades de los consumibles generados.</p>
    </section>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="card clinic-card p-3 mb-4" method="GET" action="{{ route('triajes.index') }}">
        <div class="input-group">
            <input name="search" class="form-control" value="{{ $search }}" placeholder="Buscar por orden, DNI o paciente">
            <button class="btn btn-clinic-primary">Buscar</button>
        </div>
    </form>

    <div class="d-grid gap-3">
        @forelse($orders as $order)
            <article class="card clinic-card">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <strong class="text-primary">{{ $order->codigo_orden ?? 'Orden #'.$order->id }}</strong>
                        <span class="mx-2 text-muted">·</span>
                        {{ $order->patient->nombres }} {{ $order->patient->apellidos }}
                        <small class="text-muted ms-2">DNI {{ $order->patient->dni }}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">{{ $order->fecha_orden->format('d/m/Y H:i') }}</small>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('orders.triaje', $order) }}">Editar triaje completo</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="small text-muted mb-3">
                        <strong>Tomografías:</strong>
                        {{ $order->orderExams->pluck('exam.nombre_examen')->filter()->join(', ') ?: 'Sin exámenes' }}
                    </div>
                    <form method="POST" action="{{ route('triajes.consumables.update', $order) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="search" value="{{ $search }}">
                        <input type="hidden" name="page" value="{{ $orders->currentPage() }}">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-3">
                                <thead><tr><th>Consumible</th><th>Unidad</th><th style="width: 180px">Cantidad</th></tr></thead>
                                <tbody>
                                    @forelse($order->consumables as $index => $consumable)
                                        <tr>
                                            <td>
                                                {{ $consumable->reagent?->nombre ?? 'Consumible' }}
                                                <input type="hidden" name="consumables[{{ $index }}][reagent_id]" value="{{ $consumable->reagent_id }}">
                                            </td>
                                            <td>{{ $consumable->reagent?->unidad ?? '—' }}</td>
                                            <td><input class="form-control form-control-sm" type="number" min="0" step="0.01" name="consumables[{{ $index }}][cantidad]" value="{{ $consumable->cantidad }}" aria-label="Cantidad de {{ $consumable->reagent?->nombre ?? 'consumible' }}"></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">Esta orden no generó consumibles.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($order->consumables->isNotEmpty())
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-sm btn-clinic-primary">Guardar consumibles</button>
                            </div>
                        @endif
                    </form>
                </div>
            </article>
        @empty
            <div class="card clinic-card"><div class="card-body text-center py-5">No se encontraron órdenes.</div></div>
        @endforelse
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</div>
@endsection
