@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="clinic-eyebrow mb-2">Catálogos</div>
                <h1 class="display-6 fw-bold">Médicos solicitantes</h1>
                <p class="mb-0 opacity-75">Registra solo el nombre del médico para seleccionarlo en las órdenes.</p>
            </div>
            <button class="btn btn-clinic-primary" data-bs-toggle="modal" data-bs-target="#create">+ Nuevo médico</button>
        </div>
    </section>

    <div class="card clinic-card">
        <div class="card-body border-bottom">
            <form class="row g-2" method="GET">
                <div class="col-md-10"><input name="search" class="form-control" value="{{ $search }}" placeholder="Buscar por nombre"></div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100">Buscar</button></div>
            </form>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-clinic mb-0 align-middle">
                <thead><tr><th>Nombre</th><th>Órdenes</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                    @forelse($requestingDoctors as $doctor)
                        <tr>
                            <td class="fw-bold">{{ $doctor->nombre }}</td>
                            <td>{{ $doctor->orders_count }}</td>
                            <td><span class="badge {{ $doctor->activo ? 'badge-active' : 'badge-inactive' }}">{{ $doctor->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit{{ $doctor->id }}">Editar</button>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#del{{ $doctor->id }}">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-5">Sin médicos solicitantes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $requestingDoctors->links() }}</div>
    </div>
</div>

@include('requesting-doctors.modal', ['id' => 'create', 'action' => route('requesting-doctors.store'), 'method' => 'POST', 'doctor' => null])
@foreach($requestingDoctors as $doctor)
    @include('requesting-doctors.modal', ['id' => 'edit'.$doctor->id, 'action' => route('requesting-doctors.update', $doctor), 'method' => 'PUT', 'doctor' => $doctor])
    @include('shared.delete', ['id' => 'del'.$doctor->id, 'action' => route('requesting-doctors.destroy', $doctor), 'name' => $doctor->nombre])
@endforeach
@endsection
