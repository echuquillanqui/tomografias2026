<div class="modal fade" id="deletePatientModal{{ $patient->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="{{ route('patients.destroy', $patient) }}">
            @csrf
            @method('DELETE')
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Eliminar paciente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">¿Seguro que deseas eliminar a <strong>{{ $patient->nombres }} {{ $patient->apellidos }}</strong>?</p>
                <small class="text-muted">Esta acción no se puede deshacer. Si tiene órdenes asociadas, el sistema impedirá eliminarlo.</small>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Sí, eliminar</button>
            </div>
        </form>
    </div>
</div>
