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

    <form method="GET" action="{{ route('orders.index') }}" class="card clinic-card mb-4"
          x-data="{ loading: false, timer: null, allDates: {{ $allDates ? 'true' : 'false' }}, submitFilters() { clearTimeout(this.timer); this.timer = setTimeout(() => { this.loading = true; this.$root.requestSubmit(); }, 350); } }"
          x-on:submit="loading = true">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label for="order-date-filter" class="form-label small fw-bold">FECHA DE LA ORDEN</label>
                    <input id="order-date-filter" name="date" type="date" class="form-control" value="{{ $date }}"
                           max="9999-12-31" x-bind:disabled="allDates" required x-on:change="loading = true; $root.requestSubmit()">
                </div>
                <div class="col-md-8 col-lg-7">
                    <label for="order-search-filter" class="form-label small fw-bold">PACIENTE</label>
                    <div class="input-group">
                        <span class="input-group-text" aria-hidden="true">&#128269;</span>
                        <input id="order-search-filter" name="search" type="search" class="form-control"
                               value="{{ $search }}" placeholder="Buscar por nombres, apellidos o DNI"
                               autocomplete="off" x-on:input="submitFilters()">
                    </div>
                </div>
                <div class="col-lg-2 d-grid">
                    <a class="btn btn-outline-secondary" href="{{ route('orders.index') }}">Limpiar filtros</a>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input id="order-all-dates-filter" name="all_dates" value="1" type="checkbox" class="form-check-input"
                               @checked($allDates) x-model="allDates" x-on:change="loading = true; $root.requestSubmit()">
                        <label for="order-all-dates-filter" class="form-check-label fw-semibold">Buscar en todas las fechas</label>
                        <div class="form-text">Actívelo para encontrar al paciente en todo el historial, sin limitar la búsqueda a la fecha seleccionada.</div>
                    </div>
                </div>
            </div>
            <div class="small text-muted mt-2" aria-live="polite">
                <span x-show="!loading">{{ $orders->total() }} {{ $orders->total() === 1 ? 'orden encontrada' : 'órdenes encontradas' }}</span>
                <span x-show="loading" x-cloak>Actualizando resultados...</span>
            </div>
        </div>
        <noscript>
            <div class="card-footer bg-white text-end"><button class="btn btn-clinic-primary">Buscar</button></div>
        </noscript>
    </form>

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
                        <th>Comprobante</th>
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
                            <td>{{ $o->fecha_orden->format('d/m/Y H:i') }}</td>
                            <td>@if($o->agreement->mostrar_precio_orden) S/ {{ $o->total }} @else <span class="text-muted">Oculto</span> @endif</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#payment{{ $o->id }}">
                                    {{ $o->tipo_pago ?? 'Actualizar pago' }}
                                </button>
                            </td>
                            <td>
                                @if($o->tipo_comprobante || $o->numero_comprobante)
                                    <span class="fw-semibold">{{ $o->tipo_comprobante ?? 'Comprobante' }}</span>
                                    <span class="text-muted d-block">N° {{ $o->numero_comprobante ?? '—' }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
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
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteOrder{{ $o->id }}">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center py-5">Sin órdenes.</td></tr>
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
                    <div class="row g-3 mt-1">
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold">TIPO DE COMPROBANTE</label>
                            <select name="tipo_comprobante" class="form-select">
                                <option value="">Sin comprobante</option>
                                @foreach($tiposComprobante as $tipo)
                                    <option value="{{ $tipo }}" @selected($o->tipo_comprobante === $tipo)>{{ $tipo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold">NÚMERO DE COMPROBANTE</label>
                            <input name="numero_comprobante" class="form-control" value="{{ $o->numero_comprobante }}" placeholder="Ej. B001-123">
                        </div>
                    </div>
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

    <div class="modal fade user-modal" id="deleteOrder{{ $o->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('orders.destroy', $o) }}" class="modal-content order-delete-form">
                @csrf
                @method('DELETE')
                <div class="modal-header text-white bg-danger">
                    <h5 class="modal-title">Eliminar {{ $o->codigo_orden ?? 'orden #'.$o->id }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Selecciona el motivo por el que deseas eliminar esta orden. Esta acción no se puede deshacer.</p>
                    <label class="form-label small fw-bold">MOTIVO DE ELIMINACIÓN</label>
                    <select name="motivo_eliminacion" class="form-select delete-reason-select" required>
                        <option value="" selected disabled>Seleccionar motivo</option>
                        @foreach($motivosEliminacion as $motivo)
                            <option value="{{ $motivo }}">{{ ucfirst($motivo) }}</option>
                        @endforeach
                    </select>
                    <div class="delete-reason-other d-none mt-3">
                        <label class="form-label small fw-bold">ESPECIFICA EL MOTIVO</label>
                        <textarea name="motivo_eliminacion_otro" class="form-control" rows="3" maxlength="255" placeholder="Escribe el motivo de eliminación"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger">Eliminar orden</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@push('scripts')
<script>
    document.querySelectorAll('.order-delete-form').forEach((form) => {
        const select = form.querySelector('.delete-reason-select');
        const otherWrapper = form.querySelector('.delete-reason-other');
        const otherInput = otherWrapper.querySelector('textarea');
        const toggleOther = () => {
            const show = select.value === 'otros';
            otherWrapper.classList.toggle('d-none', !show);
            otherInput.required = show;
            if (!show) otherInput.value = '';
        };

        select.addEventListener('change', toggleOther);
        toggleOther();
    });
</script>
@endpush
@endsection
