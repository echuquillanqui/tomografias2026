@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Plantilla de declaración jurada</h1>
            <p class="text-muted mb-0">Disponible para completar y para descargar en PDF.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" target="_blank" href="{{ route('orders.declaracion-jurada', $order) }}">Ver PDF</a>
            <a class="btn btn-outline-secondary" href="{{ route('orders.show', $order) }}">Volver</a>
        </div>
    </div>
    <div class="card clinic-card shadow-sm">
        <div class="card-body bg-white">
            @include('orders.templates.partials.declaracion-jurada-content')
        </div>
    </div>
</div>
@endsection
