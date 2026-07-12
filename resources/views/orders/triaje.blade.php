@extends('layouts.app')

@section('content')
@php
    $fields = [
        'unit' => 'Unidad',
        'cause' => 'Causa / motivo',
        'symptomatology' => 'Sintomatología',
        'surgeries' => 'Cirugías previas',
        'allergy' => 'Alergia',
        'fasting' => 'Ayuno',
        'creatinine' => 'Creatinina',
        'observations' => 'Observaciones',
    ];

    $initialForm = collect($fields)->mapWithKeys(fn ($label, $key) => [
        $key => old($key, $admissionData[$key] ?? ($key === 'unit' ? $order->unidad : '')),
    ]);
    $reagentOptions = $reagents->map(fn ($r) => [
        'id' => (string) $r->id,
        'name' => $r->nombre,
        'unit' => $r->unidad,
    ])->values();
    $initialMedications = collect(old('medications', $admissionData['medications'] ?? []))
        ->whenEmpty(function ($items) use ($admissionData) {
            return collect(preg_split('/\r\n|\r|\n/', (string) ($admissionData['medication'] ?? '')));
        })
        ->map(fn ($item) => is_array($item) ? ($item['name'] ?? '') : $item)
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->values();

    $initialConsumables = collect(old('consumables', $triageConsumables ?? []))->map(function ($row) use ($reagents) {
        $reagent = $reagents->firstWhere('id', (int) ($row['reagent_id'] ?? 0));

        return [
            'reagent_id' => (string) ($row['reagent_id'] ?? ''),
            'name' => $row['name'] ?? $reagent?->nombre ?? 'Consumible',
            'unit' => $row['unit'] ?? $reagent?->unidad_medida ?? '',
            'cantidad' => (float) ($row['cantidad'] ?? 0),
        ];
    })->filter(fn ($row) => $row['reagent_id'] !== '')->values();
@endphp
<div class="container py-4" x-data="triageForm()">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <div class="clinic-eyebrow mb-2">Parte de triaje</div>
                <h1 class="display-6 fw-bold mb-1">{{ $order->codigo_orden ?? 'Orden #'.$order->id }}</h1>
                <p class="mb-0 opacity-75">Complete el triaje desde el modal y luego guarde los cambios.</p>
            </div>
            <div class="d-flex gap-2 align-self-start">
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#triageModal">Rellenar triaje</button>
                <a class="btn btn-outline-light" href="{{ route('orders.show', $order) }}">Volver</a>
            </div>
        </div>
    </section>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('orders.triaje.update', $order) }}">
        @csrf
        @method('PUT')

        <div class="card clinic-card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold text-primary">DATOS DEL PACIENTE</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Paciente</strong><br>{{ $order->patient->nombres }} {{ $order->patient->apellidos }}</div>
                    <div class="col-md-2"><strong>DNI</strong><br>{{ $order->patient->dni }}</div>
                    <div class="col-md-2"><strong>Edad</strong><br>{{ $order->patient->edad ?? $order->patient->fecha_nacimiento?->age ?? '—' }}</div>
                    <div class="col-md-2"><strong>Teléfono</strong><br>{{ $order->patient->telefono ?? '—' }}</div>
                    <div class="col-md-2"><strong>Fecha</strong><br>{{ $order->fecha_orden->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card clinic-card shadow-sm">
                    <div class="card-header bg-white fw-bold text-primary d-flex justify-content-between align-items-center">
                        <span>ÍNDICE DE TRIAJE</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#triageModal">Editar datos</button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-clinic mb-0">
                            <tbody>
                                @foreach($fields as $key => $label)
                                    <tr>
                                        <th style="width: 220px;">{{ $label }}</th>
                                        <td x-text="form.{{ $key }} || '—'"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card clinic-card shadow-sm mt-4">
                    <div class="card-header bg-white fw-bold text-primary d-flex justify-content-between align-items-center">
                        <span>MEDICAMENTOS</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#triageModal">Editar medicamentos</button>
                    </div>
                    <div class="card-body">
                        <template x-if="medications.length > 0">
                            <ul class="mb-0"><template x-for="(medication, index) in medications" :key="index"><li x-text="medication"></li></template></ul>
                        </template>
                        <div class="text-muted" x-show="medications.length === 0">Sin medicamentos registrados.</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card clinic-card shadow-sm">
                    <div class="card-header bg-white fw-bold text-primary d-flex justify-content-between align-items-center">
                        <span>CONSUMIBLES</span>
                        <span class="badge bg-light text-primary" x-text="consumables.length + ' cargado(s)'"></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <select class="form-select" x-model="selectedReagent">
                                    <option value="">Agregar consumible...</option>
                                    <template x-for="reagent in reagents" :key="reagent.id"><option :value="reagent.id" x-text="reagent.name + (reagent.unit ? ' (' + reagent.unit + ')' : '')"></option></template>
                                </select>
                            </div>
                            <div class="col-4"><button type="button" class="btn btn-outline-primary w-100" @click="addConsumable()">Agregar</button></div>
                        </div>
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Consumible</th><th>Cant.</th><th>Unidad</th><th></th></tr></thead>
                            <tbody>
                                <template x-for="(item, index) in consumables" :key="item.reagent_id">
                                    <tr>
                                        <td><span x-text="item.name"></span><input type="hidden" :name="`consumables[${index}][reagent_id]`" :value="item.reagent_id"></td>
                                        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" :name="`consumables[${index}][cantidad]`" x-model.number="item.cantidad"></td>
                                        <td x-text="item.unit || '—'"></td>
                                        <td><button type="button" class="btn btn-sm btn-outline-danger" @click="consumables.splice(index, 1)">Eliminar</button></td>
                                    </tr>
                                </template>
                                <tr x-show="consumables.length === 0"><td colspan="4" class="text-center text-muted">Sin consumibles.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @foreach($fields as $key => $label)<input type="hidden" name="{{ $key }}" :value="form.{{ $key }}">@endforeach

        <div class="modal fade" id="triageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white"><h5 class="modal-title">Rellenar parte de triaje</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-bold">Unidad</label><select class="form-select" x-model="form.unit"><option value="">Seleccionar...</option>@foreach($unidades as $unidad)<option value="{{ $unidad }}">{{ $unidad }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Alergia</label><input class="form-control" x-model="form.allergy"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Ayuno</label><input class="form-control" x-model="form.fasting"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Creatinina</label><input class="form-control" x-model="form.creatinine"></div>
                        <div class="col-12"><label class="form-label small fw-bold">Causa / motivo</label><textarea class="form-control" rows="2" x-model="form.cause"></textarea></div>
                        <div class="col-12"><label class="form-label small fw-bold">Sintomatología</label><textarea class="form-control" rows="2" x-model="form.symptomatology"></textarea></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Cirugías previas</label><textarea class="form-control" rows="2" x-model="form.surgeries"></textarea></div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Medicamentos</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" x-model="newMedication" placeholder="Nombre del medicamento" @keydown.enter.prevent="addMedication()">
                                <button type="button" class="btn btn-outline-primary" @click="addMedication()">Agregar</button>
                            </div>
                            <div class="list-group">
                                <template x-for="(medication, index) in medications" :key="index">
                                    <div class="list-group-item d-flex align-items-center gap-2">
                                        <input type="text" class="form-control form-control-sm" :name="`medications[${index}]`" x-model="medications[index]">
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeMedication(index)">Eliminar</button>
                                    </div>
                                </template>
                                <div class="list-group-item text-muted" x-show="medications.length === 0">Sin medicamentos registrados.</div>
                            </div>
                        </div>
                        <div class="col-12"><label class="form-label small fw-bold">Observaciones</label><textarea class="form-control" rows="2" x-model="form.observations"></textarea></div>
                    </div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary">Guardar triaje</button></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4"><button class="btn btn-clinic-primary px-4" type="submit">Guardar parte de triaje</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function triageForm() {
    return {
        form: {{ Illuminate\Support\Js::from($initialForm) }},
        selectedReagent: '',
        newMedication: '',
        medications: {{ Illuminate\Support\Js::from($initialMedications) }},
        reagents: {{ Illuminate\Support\Js::from($reagentOptions) }},
        consumables: {{ Illuminate\Support\Js::from($initialConsumables) }},
        addMedication() {
            const medication = String(this.newMedication || '').trim();
            if (!medication) return;
            this.medications.push(medication);
            this.newMedication = '';
        },
        removeMedication(index) {
            this.medications.splice(index, 1);
        },
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
