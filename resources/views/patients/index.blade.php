@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="clinic-eyebrow mb-2">Panel de admisión</div>
                <h1 class="display-6 fw-bold mb-2">Gestión de pacientes</h1>
                <p class="mb-0 opacity-75">Registra, actualiza y consulta la información básica de los pacientes antes de generar sus órdenes.</p>
            </div>
            <button type="button" class="btn btn-clinic-primary px-4 py-3" data-bs-toggle="modal" data-bs-target="#createPatientModal">
                + Nuevo paciente
            </button>
        </div>
    </section>

    <div class="card clinic-card">
        <div class="card-header clinic-toolbar border-0 p-3 p-md-4">
            <form class="clinic-search-form" method="GET" action="{{ route('patients.index') }}" data-reactive-search>
                <div class="clinic-search">
                    <span class="clinic-search-icon">⌕</span>
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar por DNI, nombres, apellidos o teléfono..." autocomplete="off" data-search-input>
                    <span class="clinic-search-status" data-search-status>Busca al escribir</span>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-clinic">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>DNI</th>
                            <th>Teléfono</th>
                            <th>Fecha nacimiento</th>
                            <th>Edad</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patients as $patient)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="user-avatar">{{ strtoupper(substr($patient->nombres, 0, 1).substr($patient->apellidos, 0, 1)) }}</span>
                                        <div>
                                            <span class="fw-bold d-block">{{ $patient->nombres }} {{ $patient->apellidos }}</span>
                                            <small class="text-clinic-muted">Código #{{ str_pad($patient->id, 5, '0', STR_PAD_LEFT) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge rounded-pill badge-role px-3 py-2">{{ $patient->dni }}</span></td>
                                <td><span class="text-clinic-muted">{{ $patient->telefono ?: '—' }}</span></td>
                                <td>{{ $patient->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ filled($patient->edad) ? $patient->edad.' años' : '—' }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-soft-primary" data-bs-toggle="modal" data-bs-target="#editPatientModal{{ $patient->id }}">Editar</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-soft-danger" data-bs-toggle="modal" data-bs-target="#deletePatientModal{{ $patient->id }}">Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-clinic-muted">No se encontraron pacientes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($patients->hasPages())
            <div class="card-footer bg-white border-0 p-3 p-md-4">
                {{ $patients->links() }}
            </div>
        @endif
    </div>
</div>

@include('patients.partials.form-modal', ['modalId' => 'createPatientModal', 'title' => 'Crear paciente', 'action' => route('patients.store'), 'method' => 'POST', 'patient' => null])

@foreach ($patients as $patient)
    @include('patients.partials.form-modal', ['modalId' => 'editPatientModal'.$patient->id, 'title' => 'Editar paciente', 'action' => route('patients.update', $patient), 'method' => 'PUT', 'patient' => $patient])
    @include('patients.partials.delete-modal', ['patient' => $patient])
@endforeach
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchForm = document.querySelector('[data-reactive-search]');
        const searchInput = document.querySelector('[data-search-input]');
        const searchStatus = document.querySelector('[data-search-status]');
        let searchTimer;

        const calculateAge = (birthDateValue) => {
            if (!birthDateValue) return '';

            const birthDate = new Date(`${birthDateValue}T00:00:00`);
            if (Number.isNaN(birthDate.getTime())) return '';

            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age -= 1;
            }

            return age >= 0 ? `${age} años` : '';
        };

        document.querySelectorAll('.user-modal').forEach((modal) => {
            const birthDateInput = modal.querySelector('[data-birth-date]');
            const ageOutput = modal.querySelector('[data-age-output]');

            if (!birthDateInput || !ageOutput) return;

            const syncAge = () => {
                ageOutput.value = calculateAge(birthDateInput.value);
            };

            birthDateInput.addEventListener('change', syncAge);
            birthDateInput.addEventListener('input', syncAge);
            syncAge();
        });

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
