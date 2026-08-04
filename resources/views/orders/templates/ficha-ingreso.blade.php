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

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm" role="alert">
            <div class="fw-bold mb-2">No se pudo guardar la ficha. Revisa los campos marcados en rojo:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('orders.ficha-ingreso.update', $order) }}" novalidate data-highlight-invalid-form>
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

    .ficha-ingreso-card .is-invalid,
    .ficha-ingreso-card :invalid.invalid-submitted {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .15);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const invalidFields = @json($errors->keys());
        const fieldSelector = (name) => `[name="${CSS.escape(name)}"]`;
        const dotKeyToInputName = (key) => key.replace(/\.([^.]*)/g, '[$1]');

        document.querySelectorAll('[data-highlight-invalid-form]').forEach((form) => {
            invalidFields.forEach((key) => {
                const names = [key, dotKeyToInputName(key)];
                names.forEach((name) => form.querySelectorAll(fieldSelector(name)).forEach((field) => {
                    field.classList.add('is-invalid');
                    field.setAttribute('aria-invalid', 'true');
                }));
            });

            form.addEventListener('submit', () => {
                form.querySelectorAll('input, select, textarea').forEach((field) => {
                    if (!field.checkValidity()) field.classList.add('invalid-submitted');
                });
            });

            form.querySelectorAll('input, select, textarea').forEach((field) => {
                field.addEventListener('input', () => field.classList.toggle('invalid-submitted', !field.checkValidity()));
                field.addEventListener('change', () => field.classList.toggle('invalid-submitted', !field.checkValidity()));
            });
        });
    });
</script>
@endpush
