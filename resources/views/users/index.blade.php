@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--clinic-dark-blue);">Gestión de usuarios</h1>
            <p class="text-muted mb-0">Administra accesos, roles y datos médicos del personal.</p>
        </div>
        <button type="button" class="btn text-white fw-semibold shadow-sm" style="background-color: var(--clinic-cyan);" data-bs-toggle="modal" data-bs-target="#createUserModal">
            + Nuevo usuario
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <form class="row g-2 align-items-center" method="GET" action="{{ route('users.index') }}">
                <div class="col-md-10">
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar por usuario, nombre, correo o rol...">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: var(--clinic-dark-blue); color: #fff;">
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Datos médico</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="fw-semibold">{{ $user->username }}</td>
                                <td>{{ $user->nombre_completo }}</td>
                                <td>{{ $user->email }}</td>
                                <td><span class="badge rounded-pill" style="background-color: var(--clinic-light-blue);">{{ $user->rol }}</span></td>
                                <td>
                                    @if ($user->rol === 'Médico')
                                        <small class="d-block">Tipo: {{ $user->tipo_medico ?: '—' }}</small>
                                        <small class="d-block text-muted">CMP: {{ $user->cmp ?: '—' }} | RNE: {{ $user->rne ?: '—' }}</small>
                                        <small class="d-block text-muted">Comisión: {{ $user->comision_porcentaje ?? '0.00' }}%</small>
                                    @else
                                        <span class="text-muted">No aplica</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $user->activo ? 'bg-success' : 'bg-secondary' }}">{{ $user->activo ? 'Activo' : 'Inactivo' }}</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">Editar</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal{{ $user->id }}" @disabled(auth()->id() === $user->id)>Eliminar</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No se encontraron usuarios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

@include('users.partials.form-modal', ['modalId' => 'createUserModal', 'title' => 'Crear usuario', 'action' => route('users.store'), 'method' => 'POST', 'user' => null])

@foreach ($users as $user)
    @include('users.partials.form-modal', ['modalId' => 'editUserModal'.$user->id, 'title' => 'Editar usuario', 'action' => route('users.update', $user), 'method' => 'PUT', 'user' => $user])
    @include('users.partials.delete-modal', ['user' => $user])
@endforeach
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleMedicalFields = (modal) => {
            const role = modal.querySelector('[data-role-select]');
            const fields = modal.querySelector('[data-medical-fields]');
            if (!role || !fields) return;
            fields.classList.toggle('d-none', role.value !== 'Médico');
        };

        document.querySelectorAll('.user-modal').forEach((modal) => {
            modal.addEventListener('shown.bs.modal', () => toggleMedicalFields(modal));
            modal.querySelector('[data-role-select]')?.addEventListener('change', () => toggleMedicalFields(modal));
            toggleMedicalFields(modal);
        });

        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Listo', text: @json(session('success')), timer: 2200, showConfirmButton: false });
        @endif

        @if (session('error'))
            Swal.fire({ icon: 'error', title: 'Atención', text: @json(session('error')) });
        @endif

        @if ($errors->any())
            Swal.fire({ icon: 'error', title: 'Revisa el formulario', text: 'Hay campos pendientes o inválidos.' });
        @endif
    });
</script>
@endpush
