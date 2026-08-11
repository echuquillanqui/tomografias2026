<div class="modal fade user-modal" id="{{ $id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ $action }}" class="modal-content">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif
            <div class="modal-header text-white">
                <h5 class="modal-title">{{ $e ? 'Editar examen' : 'Registrar examen' }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="name-{{ $id }}">Nombre del examen</label>
                    <input id="name-{{ $id }}" name="nombre_examen" class="form-control" required
                           value="{{ old('nombre_examen', $e?->nombre_examen) }}" placeholder="Ej. TEM Abdomen">
                    <div class="form-text">El contraste y los consumibles se definirán al registrar la orden usando la configuración global.</div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" name="activo" value="1" type="checkbox" id="active-{{ $id }}"
                           @checked(old('activo', $e?->activo ?? true))>
                    <label class="form-check-label" for="active-{{ $id }}">Examen activo</label>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-clinic-primary">Guardar examen</button></div>
        </form>
    </div>
</div>
