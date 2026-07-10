@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Plantilla de declaración jurada</h1>
            <p class="text-muted mb-0">Complete los campos, guarde los cambios y descargue el PDF.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" target="_blank" href="{{ route('orders.declaracion-jurada', $order) }}">Ver PDF</a>
            <a class="btn btn-outline-secondary" href="{{ route('orders.show', $order) }}">Volver</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('orders.declaracion-jurada.update', $order) }}">
        @csrf
        @method('PUT')
        <div class="card clinic-card shadow-sm">
            <div class="card-body bg-white">
                @include('orders.templates.partials.declaracion-jurada-content', ['editable' => true])
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('orders.show', $order) }}">Cancelar</a>
                <button class="btn btn-clinic-primary px-4" type="submit">Guardar declaración</button>
            </div>
        </div>
    </form>
</div>
@endsection
