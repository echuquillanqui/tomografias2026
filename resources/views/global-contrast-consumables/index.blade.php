@extends('layouts.app')

@section('content')
@php
    $initial = collect(['Con contraste', 'Sin contraste'])->mapWithKeys(fn ($contrast) => [
        $contrast => collect($configurations->get($contrast, []))->map(fn ($row) => [
            'reagent_id' => (string) $row->reagent_id,
            'cantidad_estimada' => (float) $row->cantidad_estimada,
        ])->values()->all(),
    ]);
@endphp
<div class="container" x-data="globalConsumables(@js($initial))">
    <section class="clinic-page-hero mb-4">
        <div class="clinic-eyebrow mb-2">Configuración automática</div>
        <h1 class="display-6 fw-bold">Insumos globales por contraste</h1>
        <p class="mb-0 opacity-75">Define una sola vez los insumos que se precargarán al registrar cada tomografía.</p>
    </section>

    <form method="POST" action="{{ route('global-contrast-consumables.update') }}">
        @csrf
        @method('PUT')
        <div class="alert alert-info border-0">
            Estos valores funcionan como plantilla. Al registrar un examen podrás conservarlos, modificarlos o agregar insumos particulares sin cambiar esta configuración global.
        </div>

        <div class="row g-4">
            @foreach(['Con contraste', 'Sin contraste'] as $contrast)
                @php($tone = $contrast === 'Con contraste' ? 'primary' : 'success')
                <div class="col-lg-6">
                    <div class="card clinic-card h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <div>
                                <h5 class="mb-1 text-{{ $tone }}">{{ $contrast }}</h5>
                                <small class="text-clinic-muted">Plantilla para exámenes {{ strtolower($contrast) }}.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-{{ $tone }}" @click="add('{{ $contrast }}')">+ Agregar</button>
                        </div>
                        <div class="card-body">
                            <template x-for="(row, index) in rows['{{ $contrast }}']" :key="row.key">
                                <div class="row g-2 align-items-end mb-3">
                                    <div class="col-sm-7">
                                        <label class="form-label small fw-semibold">Insumo</label>
                                        <select class="form-select" :name="`configurations[{{ $contrast }}][${index}][reagent_id]`" x-model="row.reagent_id" required>
                                            <option value="">Seleccionar...</option>
                                            @foreach($reagents as $reagent)
                                                <option value="{{ $reagent->id }}">{{ $reagent->nombre }} ({{ $reagent->unidad }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-9 col-sm-3">
                                        <label class="form-label small fw-semibold">Cantidad</label>
                                        <input type="number" min="0.01" step="0.01" class="form-control" :name="`configurations[{{ $contrast }}][${index}][cantidad_estimada]`" x-model="row.cantidad_estimada" required>
                                    </div>
                                    <div class="col-3 col-sm-2 text-end">
                                        <button type="button" class="btn btn-outline-danger" @click="remove('{{ $contrast }}', row.key)" aria-label="Quitar insumo">×</button>
                                    </div>
                                </div>
                            </template>
                            <div class="text-center text-clinic-muted py-4" x-show="rows['{{ $contrast }}'].length === 0">
                                No hay insumos asignados a esta modalidad.
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button class="btn btn-clinic-primary px-4">Guardar asignación global</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('globalConsumables', (initialRows) => ({
        rows: initialRows,
        nextKey: 0,
        init() {
            Object.keys(this.rows).forEach(type => {
                this.rows[type] = this.rows[type].map(row => ({ ...row, key: ++this.nextKey }));
            });
        },
        add(type) {
            this.rows[type].push({ key: ++this.nextKey, reagent_id: '', cantidad_estimada: 1 });
        },
        remove(type, key) {
            this.rows[type] = this.rows[type].filter(row => row.key !== key);
        },
    }));
});
</script>
@endpush
