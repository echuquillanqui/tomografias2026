@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex justify-content-between">
            <div>
                <div class="clinic-eyebrow mb-2">Admisión</div>
                <h1 class="display-6 fw-bold">Órdenes</h1>
                <p class="mb-0 opacity-75">La generación de órdenes se realiza en páginas individuales para mayor precisión.</p>
            </div>
            <a class="btn btn-clinic-primary" href="{{ route('orders.create') }}">+ Generar orden</a>
        </div>
    </section>

    <div class="card clinic-card">
        <div class="card-body p-0">
            <table class="table table-clinic mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Paciente</th>
                        <th>Convenio</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Pago</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                        <tr>
                            <td class="fw-bold">{{ $o->codigo_orden ?? '—' }}</td>
                            <td>{{ $o->patient->nombres }} {{ $o->patient->apellidos }}</td>
                            <td>{{ $o->agreement->nombre_institucion }}</td>
                            <td>{{ $o->fecha_orden->format('d/m/Y') }}</td>
                            <td>S/ {{ $o->total }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#payment{{ $o->id }}">
                                    {{ $o->tipo_pago ?? 'Actualizar pago' }}
                                </button>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm badge badge-role border-0" data-bs-toggle="modal" data-bs-target="#status{{ $o->id }}">
                                    {{ $o->estado }}
                                </button>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('orders.show', $o) }}">Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('orders.edit', $o) }}">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5">Sin órdenes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $orders->links() }}</div>
    </div>
</div>

@foreach($orders as $o)
    <div class="modal fade user-modal" id="payment{{ $o->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('orders.update-payment', $o) }}" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header text-white">
                    <h5 class="modal-title">Actualizar pago de {{ $o->codigo_orden ?? 'orden #'.$o->id }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-bold">MÉTODO DE PAGO</label>
                    <select name="tipo_pago" class="form-select" required>
                        @foreach($tiposPago as $tipo)
                            <option value="{{ $tipo }}" @selected(($o->tipo_pago ?? 'Efectivo') === $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-clinic-primary">Guardar pago</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade user-modal" id="status{{ $o->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('orders.update-status', $o) }}" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header text-white">
                    <h5 class="modal-title">Actualizar estado de {{ $o->codigo_orden ?? 'orden #'.$o->id }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-bold">ESTADO</label>
                    <select name="estado" class="form-select fw-bold" required>
                        @foreach($estados as $estado)
                            <option value="{{ $estado }}" @selected($o->estado === $estado)>{{ $estado }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-clinic-primary">Guardar estado</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
