@extends('layouts.app')

@section('content')
@php
    $examRows = collect(old('exams', $order->orderExams->toArray() ?: []))->filter(fn ($row) => ! empty($row['exam_id']))->values();
    $initialItems = $examRows->map(function ($row) use ($exams) {
        $exam = $exams->firstWhere('id', (int) ($row['exam_id'] ?? 0));

        return [
            'uid' => 'exam'.($row['exam_id'] ?? ''),
            'id' => (string) ($row['exam_id'] ?? ''),
            'name' => $exam?->nombre_examen ?? 'Examen seleccionado',
            'type' => 'exam',
            'area' => 'TOMOGRAFÍA',
            'tipo_contraste' => $row['tipo_contraste'] ?? 'Sin contraste',
            'estado' => $row['estado'] ?? 'Pendiente',
            'price' => (float) ($row['precio'] ?? 0),
        ];
    })->values();
    $consumableRows = collect(old('consumables', $order->consumables->toArray() ?: []))->filter(fn ($row) => ! empty($row['reagent_id']))->values();
    $initialConsumables = $consumableRows->map(function ($row) use ($reagents) {
        $reagent = $reagents->firstWhere('id', (int) ($row['reagent_id'] ?? 0));
        return ['reagent_id' => (string) ($row['reagent_id'] ?? ''), 'name' => $reagent?->nombre ?? 'Consumible', 'unit' => $reagent?->unidad_medida ?? '', 'cantidad' => (float) ($row['cantidad'] ?? 0)];
    })->values();
@endphp

<div class="container py-4" x-data="orderSystem()">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <h1 class="display-6 fw-bold mb-1">{{ $mode === 'create' ? 'Generar orden' : 'Editar orden '.$order->codigo_orden }}</h1>
            </div>
            <a class="btn btn-light align-self-start" href="{{ route('orders.index') }}">Volver</a>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm border-0">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ $mode === 'create' ? route('orders.store') : route('orders.update', $order) }}" @submit="isSubmitting = true">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4 clinic-card">
                    <div class="card-header bg-white py-3 border-bottom text-primary fw-bold">DATOS DEL PACIENTE</div>
                    <div class="card-body">
                        <div class="order-search-panel mb-4">
                            <div class="order-search-panel__icon"><i class="bi bi-person-vcard"></i></div>
                            <div class="flex-grow-1">
                                <label class="form-label small fw-bold">PACIENTE</label>
                                <select name="patient_id" class="form-select js-tom-select" data-placeholder="Buscar paciente por DNI, nombres o apellidos" required>
                                    <option value=""></option>
                                    @foreach($patients as $p)
                                        <option value="{{ $p->id }}" @selected(old('patient_id', $order->patient_id) == $p->id)>{{ $p->dni }} - {{ $p->nombres }} {{ $p->apellidos }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">CONVENIO</label>
                                <select name="agreement_id" class="form-select" x-model="selectedAgreement" @change="applyAgreementPrices()" required>
                                    @foreach($agreements as $a)
                                        <option value="{{ $a->id }}" @selected(old('agreement_id', $order->agreement_id) == $a->id)>{{ $a->nombre_institucion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">CÓDIGO DE ORDEN</label>
                                <input name="codigo_orden" class="form-control" value="{{ old('codigo_orden', $order->codigo_orden) }}" placeholder="Opcional / si aplica">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">UNIDAD</label>
                                <select name="unidad" class="form-select" required>
                                    @foreach($unidades as $unidad)
                                        <option value="{{ $unidad }}" @selected(old('unidad', $order->unidad ?? 'Topico') === $unidad)>{{ $unidad }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">FECHA</label>
                                <input name="fecha_orden" type="date" class="form-control" value="{{ old('fecha_orden', optional($order->fecha_orden)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">ESTADO</label>
                                <select name="estado" class="form-select fw-bold" required>
                                    @foreach($estados as $e)
                                        <option @selected(old('estado', $order->estado) === $e)>{{ $e }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">MÉDICO SOLICITANTE</label>
                                <select name="medico_solicitante_id" class="form-select js-tom-select" data-placeholder="Buscar médico solicitante">
                                    <option value=""></option>
                                    @foreach($medicosSolicitantes as $m)
                                        <option value="{{ $m->id }}" @selected(old('medico_solicitante_id', $order->medico_solicitante_id) == $m->id)>{{ $m->nombre_completo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">MÉDICO INFORMANTE</label>
                                <select name="medico_informe_id" class="form-select js-tom-select" data-placeholder="Buscar médico informante">
                                    <option value=""></option>
                                    @foreach($medicosInformantes as $m)
                                        <option value="{{ $m->id }}" @selected(old('medico_informe_id', $order->medico_informe_id) == $m->id)>{{ $m->nombre_completo }} ({{ $m->comision_porcentaje ?? 0 }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">ARCHIVO DE ORDEN</label>
                                <input name="archivo_orden" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                @if($order->archivo_orden_path)<div class="form-text">Archivo cargado: {{ basename($order->archivo_orden_path) }}</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">OBSERVACIONES</label>
                                <input name="observaciones" class="form-control" value="{{ old('observaciones', $order->observaciones) }}" placeholder="Indicaciones u observaciones">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm clinic-card">
                    <div class="card-header bg-white py-3 border-bottom text-primary fw-bold">BÚSQUEDA DE EXÁMENES</div>
                    <div class="card-body">
                        <div class="order-search-panel order-search-panel--exam mb-4">
                            <div class="order-search-panel__icon"><i class="bi bi-clipboard2-pulse"></i></div>
                            <div class="flex-grow-1">
                                <label class="form-label small fw-bold">AGREGAR EXAMEN</label>
                                <select id="item_select" placeholder="Buscar exámenes... (mínimo 2 letras)"></select>
                                <div class="form-text">Escribe al menos 2 letras y selecciona un examen para agregarlo a la orden.</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-clinic-order align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted">
                                        <th>DESCRIPCIÓN</th>
                                        <th>CONTRASTE</th>
                                        <th>ESTADO</th>
                                        <th class="text-end">PRECIO</th>
                                        <th class="text-center">ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in filteredCart()" :key="item.uid">
                                        <tr>
                                            <td>
                                                <div class="fw-bold" x-text="item.name"></div>
                                                <span class="fw-bold text-uppercase text-primary" x-text="' [' + item.area + ']'"></span>
                                                <input type="hidden" :name="`exams[${cart.indexOf(item)}][exam_id]`" :value="item.id">
                                            </td>
                                            <td style="min-width: 160px;">
                                                <select class="form-select form-select-sm" :name="`exams[${cart.indexOf(item)}][tipo_contraste]`" x-model="item.tipo_contraste" @change="item.price = priceFor(item.id, item.tipo_contraste)">
                                                    <option>Sin contraste</option>
                                                    <option>Con contraste</option>
                                                </select>
                                            </td>
                                            <td style="min-width: 150px;">
                                                <select class="form-select form-select-sm" :name="`exams[${cart.indexOf(item)}][estado]`" x-model="item.estado">
                                                    <option>Pendiente</option>
                                                    <option>Realizado</option>
                                                    <option>Informado</option>
                                                    <option>Anulado</option>
                                                </select>
                                            </td>
                                            <td class="text-end" style="max-width: 140px;">
                                                <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end fw-bold" :name="`exams[${cart.indexOf(item)}][precio]`" x-model.number="item.price" required>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" @click="removeByUid(item.uid)" class="btn btn-sm btn-outline-danger border-0">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredCart().length === 0">
                                        <td colspan="5" class="text-center text-muted py-3">No hay exámenes seleccionados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm clinic-card mt-4">
                    <div class="card-header bg-white py-3 border-bottom text-primary fw-bold d-flex justify-content-between align-items-center">
                        <span>CONSUMIBLES DE LA ORDEN</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="preloadConsumablesFromCart(true)">Precargar desde exámenes</button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info py-2 small">Los consumibles configurados en cada examen se precargan automáticamente al agregarlo. Puedes ajustar las cantidades antes de guardar.</div>
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <select x-model="selectedReagent" class="form-select">
                                    <option value="">Agregar consumible...</option>
                                    <template x-for="reagent in reagents" :key="reagent.id">
                                        <option :value="reagent.id" x-text="reagent.name + (reagent.unit ? ' (' + reagent.unit + ')' : '')"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-4"><button type="button" class="btn btn-outline-primary w-100" @click="addConsumable()">Agregar</button></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Consumible</th><th style="width:150px;">Cantidad</th><th>Unidad</th><th></th></tr></thead>
                                <tbody>
                                    <template x-for="(item, index) in consumables" :key="item.reagent_id">
                                        <tr>
                                            <td><span x-text="item.name"></span><input type="hidden" :name="`consumables[${index}][reagent_id]`" :value="item.reagent_id"></td>
                                            <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" :name="`consumables[${index}][cantidad]`" x-model.number="item.cantidad"></td>
                                            <td x-text="item.unit || '—'"></td>
                                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger border-0" @click="consumables.splice(index, 1)"><i class="bi bi-trash3-fill"></i></button></td>
                                        </tr>
                                    </template>
                                    <tr x-show="consumables.length === 0"><td colspan="4" class="text-center text-muted">Sin consumibles.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top clinic-card" style="top: 20px;">
                    <div class="card-header bg-primary text-white py-3 text-center fw-bold">RESUMEN DE COBRO</div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">MÉTODO DE PAGO</label>
                            <select name="tipo_pago" class="form-select" required>
                                @foreach($tiposPago as $tipo)
                                    <option value="{{ $tipo }}" @selected(old('tipo_pago', $order->tipo_pago ?? 'Efectivo') === $tipo)>{{ $tipo }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">DESCUENTO</label>
                            <input name="descuento" type="number" min="0" step="0.01" class="form-control" x-model.number="discount">
                        </div>

                        <div class="bg-light p-3 rounded mb-4 border text-center">
                            <div class="small text-muted">Subtotal: S/ <span x-text="subtotal().toFixed(2)"></span></div>
                            <h2 class="fw-bold text-primary mb-0">S/ <span x-text="total().toFixed(2)"></span></h2>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 shadow fw-bold" :disabled="cart.length === 0 || isSubmitting">
                            <span x-show="!isSubmitting">CONFIRMAR Y GUARDAR</span>
                            <span x-show="isSubmitting">GUARDANDO...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
<style>
    .ts-dropdown { z-index: 2000 !important; position: absolute !important; }
    .card, .table-responsive { overflow: visible !important; }
</style>
<script>
function orderSystem() {
    return {
        cart: {{ Illuminate\Support\Js::from($initialItems) }},
        consumables: {{ Illuminate\Support\Js::from($initialConsumables) }},
        selectedReagent: '',
        reagents: {{ Illuminate\Support\Js::from($reagents->map(fn ($r) => ['id' => (string) $r->id, 'name' => $r->nombre, 'unit' => $r->unidad_medida])->values()) }},
        examConsumables: {{ Illuminate\Support\Js::from($exams->mapWithKeys(fn ($e) => [(string) $e->id => $e->reagents->map(fn ($r) => ['reagent_id' => (string) $r->id, 'name' => $r->nombre, 'unit' => $r->unidad_medida, 'cantidad' => (float) $r->pivot->cantidad_estimada])->values()])) }},
        agreementPrices: {{ Illuminate\Support\Js::from($agreementPrices->map(fn ($price) => [
            'agreement_id' => (string) $price->agreement_id,
            'exam_id' => (string) $price->exam_id,
            'tipo_contraste' => $price->tipo_contraste,
            'price' => (float) $price->precio_pactado,
        ])->values()) }},
        selectedAgreement: String({{ Illuminate\Support\Js::from(old('agreement_id', $order->agreement_id ?? $agreements->first()?->id)) }} || ''),
        discount: Number({{ Illuminate\Support\Js::from(old('descuento', $order->descuento ?? 0)) }}) || 0,
        cartSearch: '',
        isSubmitting: false,
        init() {
            this.preloadConsumablesFromCart(false);

            document.querySelectorAll('.js-tom-select').forEach((select) => {
                if (!select.tomselect) {
                    new TomSelect(select, {
                        create: false,
                        allowEmptyOption: true,
                        placeholder: select.dataset.placeholder || 'Buscar...',
                        plugins: ['clear_button']
                    });
                }
            });

            const exams = {{ Illuminate\Support\Js::from($exams->map(fn ($e) => ['id' => (string) $e->id, 'name' => $e->nombre_examen, 'uid' => 'exam'.$e->id, 'area' => 'TOMOGRAFÍA'])->values()) }};
            new TomSelect('#item_select', {
                valueField: 'uid',
                labelField: 'display_name',
                searchField: ['name', 'display_name'],
                options: exams.map((exam) => ({ ...exam, display_name: `${exam.name} [EXAMEN]` })),
                maxOptions: 50,
                shouldLoad: (query) => query.length >= 2,
                render: {
                    option: (data, escape) => `<div>${escape(data.name)} <span class="text-primary fw-bold">[EXAMEN]</span></div>`,
                    item: (data, escape) => `<div>${escape(data.name)} <span class="text-primary fw-bold">[EXAMEN]</span></div>`
                },
                onChange: (value) => {
                    if (!value) return;
                    const item = this.$el.querySelector('#item_select').tomselect.options[value];
                    if (!this.cart.find((cartItem) => cartItem.uid === item.uid)) {
                        this.cart.push({ ...item, type: 'exam', tipo_contraste: 'Sin contraste', estado: 'Pendiente', price: this.priceFor(item.id, 'Sin contraste') });
                        this.mergeExamConsumables(item.id);
                    }
                    this.$el.querySelector('#item_select').tomselect.clear();
                }
            });
        },
        filteredCart() {
            const term = this.cartSearch.toLowerCase();
            return this.cart.filter((item) => !term || item.name.toLowerCase().includes(term) || (item.area || '').toLowerCase().includes(term));
        },
        removeByUid(uid) {
            const index = this.cart.findIndex((item) => item.uid === uid);
            if (index !== -1) this.cart.splice(index, 1);
        },
        priceFor(examId, contrast) {
            const match = this.agreementPrices.find((price) => price.agreement_id === String(this.selectedAgreement) && price.exam_id === String(examId) && price.tipo_contraste === contrast);
            return match ? Number(match.price) : 0;
        },
        preloadConsumablesFromCart(force = false) {
            if (!force && this.consumables.length > 0) return;
            if (force) this.consumables = [];
            this.cart.forEach((item) => this.mergeExamConsumables(item.id));
        },
        addConsumable() {
            const reagent = this.reagents.find((item) => item.id === String(this.selectedReagent));
            if (!reagent) return;
            const existing = this.consumables.find((item) => item.reagent_id === reagent.id);
            if (existing) existing.cantidad = Number(existing.cantidad || 0) + 1;
            else this.consumables.push({ reagent_id: reagent.id, name: reagent.name, unit: reagent.unit, cantidad: 1 });
            this.selectedReagent = '';
        },
        mergeExamConsumables(examId) {
            (this.examConsumables[String(examId)] || []).forEach((row) => {
                const existing = this.consumables.find((item) => item.reagent_id === row.reagent_id);
                if (existing) existing.cantidad = Number(existing.cantidad || 0) + Number(row.cantidad || 0);
                else this.consumables.push({ ...row });
            });
        },
        applyAgreementPrices() {
            this.cart = this.cart.map((item) => ({ ...item, price: this.priceFor(item.id, item.tipo_contraste) }));
        },
        subtotal() {
            return this.cart.reduce((sum, item) => sum + (Number(item.price) || 0), 0);
        },
        total() {
            return Math.max(this.subtotal() - (Number(this.discount) || 0), 0);
        }
    }
}
</script>
@endpush
