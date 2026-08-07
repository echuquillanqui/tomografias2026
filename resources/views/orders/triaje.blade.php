@extends('layouts.app')

@section('content')
@php
    $reagentOptions = $reagents->map(fn ($reagent) => [
        'id' => (string) $reagent->id,
        'name' => $reagent->nombre,
        'unit' => $reagent->unidad,
    ])->values();
    $initialConsumables = collect(old('consumables', $triageConsumables ?? []))->map(function ($row) use ($reagents) {
        $reagent = $reagents->firstWhere('id', (int) ($row['reagent_id'] ?? 0));
        return [
            'reagent_id' => (string) ($row['reagent_id'] ?? ''),
            'name' => $row['name'] ?? $reagent?->nombre ?? 'Consumible',
            'unit' => $row['unit'] ?? $reagent?->unidad ?? '',
            'cantidad' => (float) ($row['cantidad'] ?? 0),
        ];
    })->filter(fn ($row) => $row['reagent_id'] !== '')->values();
@endphp
<div class="container py-4" x-data="consumablesForm()">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <div class="clinic-eyebrow mb-2">Triaje y consumibles</div>
                <h1 class="display-6 fw-bold mb-1">{{ $order->codigo_orden ?? 'Orden #'.$order->id }}</h1>
                <p class="mb-0 opacity-75">Registre los consumibles utilizados en el estudio.</p>
            </div>
            <a class="btn btn-outline-light align-self-start" href="{{ route('triajes.index') }}">Volver</a>
        </div>
    </section>

    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card clinic-card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold text-primary">DATOS DEL PACIENTE Y ESTUDIO</div>
        <div class="card-body"><div class="row g-3">
            <div class="col-md-4"><strong>Paciente</strong><br>{{ $order->patient->nombres }} {{ $order->patient->apellidos }}</div>
            <div class="col-md-2"><strong>DNI</strong><br>{{ $order->patient->dni }}</div>
            <div class="col-md-2"><strong>Edad</strong><br>{{ $order->patient->edad ?? $order->patient->fecha_nacimiento?->age ?? '—' }}</div>
            <div class="col-md-4"><strong>Tipo de estudio</strong><br>{{ $order->orderExams->pluck('exam.nombre_examen')->filter()->join(', ') ?: 'Sin estudios registrados' }}</div>
        </div></div>
    </div>

    <form method="POST" action="{{ route('triajes.consumables.update', $order) }}">
        @csrf
        @method('PUT')
        <div class="card clinic-card shadow-sm">
            <div class="card-header bg-white fw-bold text-primary d-flex justify-content-between align-items-center">
                <span>CONSUMIBLES</span>
                <span class="badge bg-light text-primary" x-text="consumables.length + ' cargado(s)'"></span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-9"><select class="form-select" x-model="selectedReagent"><option value="">Agregar consumible...</option><template x-for="reagent in reagents" :key="reagent.id"><option :value="reagent.id" x-text="reagent.name + (reagent.unit ? ' (' + reagent.unit + ')' : '')"></option></template></select></div>
                    <div class="col-md-3"><button type="button" class="btn btn-outline-primary w-100" @click="addConsumable()">Agregar</button></div>
                </div>
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Consumible</th><th style="width:160px">Cantidad</th><th>Unidad</th><th></th></tr></thead>
                    <tbody>
                        <template x-for="(item, index) in consumables" :key="item.reagent_id"><tr><td><span x-text="item.name"></span><input type="hidden" :name="`consumables[${index}][reagent_id]`" :value="item.reagent_id"></td><td><input type="number" min="0" step="0.01" class="form-control form-control-sm" :name="`consumables[${index}][cantidad]`" x-model.number="item.cantidad"></td><td x-text="item.unit || '—'"></td><td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" @click="consumables.splice(index, 1)">Eliminar</button></td></tr></template>
                        <tr x-show="consumables.length === 0"><td colspan="4" class="text-center text-muted">Sin consumibles.</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-4"><button class="btn btn-clinic-primary px-4" type="submit">Guardar consumibles</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function consumablesForm() {
    return {
        selectedReagent: '',
        reagents: {{ Illuminate\Support\Js::from($reagentOptions) }},
        consumables: {{ Illuminate\Support\Js::from($initialConsumables) }},
        addConsumable() {
            const reagent = this.reagents.find((item) => item.id === String(this.selectedReagent));
            if (!reagent) return;
            const existing = this.consumables.find((item) => item.reagent_id === reagent.id);
            if (existing) existing.cantidad = Number(existing.cantidad || 0) + 1;
            else this.consumables.push({ reagent_id: reagent.id, name: reagent.name, unit: reagent.unit, cantidad: 1 });
            this.selectedReagent = '';
        },
    };
}
</script>
@endpush
