@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="clinic-eyebrow mb-2">Panel administrativo</div>
                <h1 class="display-6 fw-bold mb-2">Gestión de usuarios</h1>
                <p class="mb-0 opacity-75">Administra accesos, roles y datos médicos del personal con una experiencia rápida, clara y segura.</p>
            </div>
            <button type="button" class="btn btn-clinic-primary px-4 py-3" data-bs-toggle="modal" data-bs-target="#createUserModal">
                + Nuevo usuario
            </button>
        </div>
    </section>

    <div class="card clinic-card">
        <div class="card-header clinic-toolbar border-0 p-3 p-md-4">
            <form class="clinic-search-form" method="GET" action="{{ route('users.index') }}" data-reactive-search>
                <div class="clinic-search">
                    <span class="clinic-search-icon">⌕</span>
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar por usuario, nombre, correo o rol..." autocomplete="off" data-search-input>
                    <span class="clinic-search-status" data-search-status>Busca al escribir</span>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-clinic">
                    <thead>
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
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="user-avatar">{{ strtoupper(substr($user->username, 0, 1)) }}</span>
                                        <span class="fw-bold">{{ $user->username }}</span>
                                    </div>
                                </td>
                                <td>{{ $user->nombre_completo }}</td>
                                <td><span class="text-clinic-muted">{{ $user->email }}</span></td>
                                <td><span class="badge rounded-pill badge-role px-3 py-2">{{ $user->rol }}</span></td>
                                <td>
                                    @if ($user->rol === 'Médico')
                                        <small class="d-block fw-semibold">Tipo: {{ $user->tipo_medico ?: '—' }}</small>
                                        <small class="d-block text-clinic-muted">CMP: {{ $user->cmp ?: '—' }} | RNE: {{ $user->rne ?: '—' }}</small>
                                        <small class="d-block text-clinic-muted">Comisión: {{ $user->comision_porcentaje ?? '0.00' }}%</small>
                                    @else
                                        <span class="text-clinic-muted">No aplica</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge rounded-pill {{ $user->activo ? 'badge-active' : 'badge-inactive' }} px-3 py-2">{{ $user->activo ? 'Activo' : 'Inactivo' }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-soft-primary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">Editar</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-soft-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal{{ $user->id }}" @disabled(auth()->id() === $user->id)>Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-clinic-muted">No se encontraron usuarios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer bg-white border-0 p-3 p-md-4">
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

        const searchForm = document.querySelector('[data-reactive-search]');
        const searchInput = document.querySelector('[data-search-input]');
        const searchStatus = document.querySelector('[data-search-status]');
        let searchTimer;

        searchInput?.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            if (searchStatus) searchStatus.textContent = 'Buscando...';
            searchTimer = window.setTimeout(() => searchForm?.requestSubmit(), 450);
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
