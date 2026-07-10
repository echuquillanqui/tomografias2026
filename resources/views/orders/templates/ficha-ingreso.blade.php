@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Plantilla de ficha de ingreso</h1>
            <p class="text-muted mb-0">Complete los campos impresos manualmente o genere el PDF precargado.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" target="_blank" href="{{ route('orders.ficha-ingreso', $order) }}">Ver PDF</a>
            <a class="btn btn-outline-secondary" href="{{ route('orders.show', $order) }}">Volver</a>
        </div>
    </div>
    <div class="card clinic-card shadow-sm">
        <div class="card-body bg-white">
            @include('orders.templates.partials.ficha-ingreso-content')
        </div>
    </div>
</div>
@endsection
