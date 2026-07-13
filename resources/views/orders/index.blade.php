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
                        <th>Unidad</th>
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
                            <td>{{ $o->unidad ?? '—' }}</td>
                            <td>{{ $o->patient->nombres }} {{ $o->patient->apellidos }}</td>
                            <td>{{ $o->agreement->nombre_institucion }}</td>
                            <td>{{ $o->fecha_orden->format('d/m/Y') }}</td>
                            <td>@if($o->agreement->mostrar_precio_orden) S/ {{ $o->total }} @else <span class="text-muted">Oculto</span> @endif</td>
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
                                <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#file{{ $o->id }}">Subir orden</button>
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#triage{{ $o->id }}">Triaje</button>
                                <a class="btn btn-sm btn-outline-success" target="_blank" href="{{ route('orders.ficha-ingreso', $o) }}">Ficha PDF</a>
                                @if(($o->patient->fecha_nacimiento && $o->patient->fecha_nacimiento->age < 18) || (! $o->patient->fecha_nacimiento && $o->patient->edad !== null && $o->patient->edad < 18))
                                    <a class="btn btn-sm btn-outline-warning" target="_blank" href="{{ route('orders.declaracion-jurada', $o) }}">DJ PDF</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-5">Sin órdenes.</td></tr>
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

    <div class="modal fade user-modal" id="file{{ $o->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" action="{{ route('orders.update-file', $o) }}" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header text-white">
                    <h5 class="modal-title">Subir archivo de {{ $o->codigo_orden ?? 'orden #'.$o->id }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-bold">ARCHIVO DE ORDEN</label>
                    <input name="archivo_orden" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                    @if($o->archivo_orden_path)<div class="form-text">Actual: {{ basename($o->archivo_orden_path) }}</div>@endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-clinic-primary">Guardar archivo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade user-modal" id="triage{{ $o->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title">Parte de triaje de {{ $o->codigo_orden ?? 'orden #'.$o->id }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>Paciente:</strong> {{ $o->patient->nombres }} {{ $o->patient->apellidos }}</p>
                    <p class="mb-0 text-muted">Abre el formulario de triaje para completar o actualizar el índice, datos clínicos y consumibles de la orden.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <a class="btn btn-clinic-primary" href="{{ route('orders.triaje', $o) }}">Abrir triaje</a>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
