@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 px-xl-5 py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Plantilla de ficha de ingreso</h1>
            <p class="text-muted mb-0">Complete los campos, guarde los cambios y genere el PDF precargado.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" target="_blank" href="{{ route('orders.ficha-ingreso', $order) }}">Ver PDF</a>
            <a class="btn btn-outline-secondary" href="{{ route('orders.show', $order) }}">Volver</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('orders.ficha-ingreso.update', $order) }}">
        @csrf
        @method('PUT')
        <div class="card clinic-card shadow-sm ficha-ingreso-card mx-auto">
            <div class="card-body bg-white p-4 p-lg-5">
                @include('orders.templates.partials.ficha-ingreso-content', ['editable' => true])
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('orders.show', $order) }}">Cancelar</a>
                <button class="btn btn-clinic-primary px-4" type="submit">Guardar ficha</button>
            </div>
        </div>
    </form>
</div>
@endsection
@push('styles')
<style>
    .ficha-ingreso-card {
        max-width: 1320px;
    }

    .ficha-ingreso-card .form-control,
    .ficha-ingreso-card .form-select,
    .ficha-ingreso-card .form-check-label {
        font-size: 1rem;
    }

    .ficha-ingreso-card .table th,
    .ficha-ingreso-card .table td {
        padding: .85rem;
    }

    [x-cloak] {
        display: none !important;
    }
</style>
@endpush
