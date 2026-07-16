<div class="modal fade user-modal" id="{{ $id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ $action }}" class="modal-content">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif
            <div class="modal-header text-white">
                <h5 class="modal-title">Médico solicitante</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Nombre</label>
                    <input name="nombre" class="form-control" required value="{{ old('nombre', $doctor?->nombre) }}" placeholder="Nombre completo">
                </div>
                <div class="col-12 form-check form-switch ms-2">
                    <input class="form-check-input" name="activo" value="1" type="checkbox" @checked(old('activo', $doctor?->activo ?? true))>
                    <label class="form-check-label">Activo</label>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-clinic-primary">Guardar</button></div>
        </form>
    </div>
</div>
