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
            'configured_contrast' => $exam?->tipo_contraste ?? ($row['tipo_contraste'] ?? 'Sin contraste'),
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
    $paymentRows = collect(old('payments', $order->payments->toArray() ?: [[
        'payment_method' => $order->tipo_pago ?? 'Efectivo',
        'amount' => (float) ($order->total ?? 0),
    ]]));
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
        <div id="order-validation-errors" class="alert alert-danger shadow-sm border-0" role="alert" tabindex="-1">
            <div class="fw-bold mb-1">No se pudo guardar la orden.</div>
            <div class="small mb-2">Corrige los siguientes campos. La información que ingresaste se conserva en el formulario.</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ $mode === 'create' ? route('orders.store') : route('orders.update', $order) }}" @submit="submitOrder($event)">
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
                                <select id="patient_select" name="patient_id" class="form-select js-tom-select @error('patient_id') is-invalid @enderror" data-placeholder="Buscar paciente por DNI, nombres o apellidos" required x-model="selectedPatientId" @error('patient_id') aria-invalid="true" aria-describedby="patient-error" @enderror>
                                    <option value=""></option>
                                    @foreach($patients as $p)
                                        <option value="{{ $p->id }}" @selected(old('patient_id', $order->patient_id) == $p->id)>{{ $p->dni }} - {{ $p->nombres }} {{ $p->apellidos }}</option>
                                    @endforeach
                                </select>
                                @error('patient_id')<div id="patient-error" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="openPatientModal()">
                                        <i class="bi bi-person-plus"></i> Registrar paciente
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="openPatientModal(selectedPatientId)" :disabled="!selectedPatientId">
                                        <i class="bi bi-pencil-square"></i> Editar paciente
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">CONVENIO</label>
                                <select name="agreement_id" class="form-select @error('agreement_id') is-invalid @enderror" x-model="selectedAgreement" @change="applyAgreementPrices()" required>
                                    @foreach($agreements as $a)
                                        <option value="{{ $a->id }}" @selected(old('agreement_id', $order->agreement_id) == $a->id)>{{ $a->nombre_institucion }}</option>
                                    @endforeach
                                </select>
                                @error('agreement_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">BOLETA O FACTURA</label>
                                <select name="tipo_comprobante" class="form-select">
                                    <option value="">Seleccionar...</option>
                                    @foreach($tiposComprobante as $tipo)
                                        <option value="{{ $tipo }}" @selected(old('tipo_comprobante', $order->tipo_comprobante) === $tipo)>{{ $tipo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">NÚMERO DE COMPROBANTE</label>
                                <input name="numero_comprobante" class="form-control" value="{{ old('numero_comprobante', $order->numero_comprobante) }}" placeholder="Número de boleta o factura">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">CÓDIGO DE ORDEN</label>
                                <input name="codigo_orden" class="form-control" value="{{ old('codigo_orden', $order->codigo_orden) }}" placeholder="Opcional / si aplica">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">FECHA Y HORA</label>
                                <input name="fecha_orden" type="text" inputmode="text" class="form-control @error('fecha_orden') is-invalid @enderror" value="{{ old('fecha_orden', optional($order->fecha_orden)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i')) }}" placeholder="dd/mm/aaaa 14:30 o dd/mm/aaaa 2:30 PM" pattern="\d{1,2}/\d{1,2}/\d{4}\s+([01]?\d|2[0-3]):[0-5]\d(\s*[AaPp]\.?[Mm]\.?)?" required>
                                @error('fecha_orden')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Usa 24 horas (ej. 15/07/2026 14:30). También se acepta AM/PM y se convierte automáticamente.</div>
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
                                        <option value="{{ $m->id }}" @selected(old('medico_solicitante_id', $order->medico_solicitante_id) == $m->id)>{{ $m->nombre }}</option>
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
                    <div class="card-header bg-white py-3 border-bottom">
                        <ul class="nav nav-tabs card-header-tabs order-form-tabs" id="orderDetailsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold" id="exams-tab" data-bs-toggle="tab" data-bs-target="#exams-pane" type="button" role="tab" aria-controls="exams-pane" aria-selected="true">
                                    BÚSQUEDA DE EXÁMENES
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold" id="consumables-tab" data-bs-toggle="tab" data-bs-target="#consumables-pane" type="button" role="tab" aria-controls="consumables-pane" aria-selected="false">
                                    CONSUMIBLES DE LA ORDEN
                                    <span class="badge bg-primary ms-2" x-text="consumables.length"></span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="orderDetailsTabsContent">
                            <div class="tab-pane fade show active" id="exams-pane" role="tabpanel" aria-labelledby="exams-tab" tabindex="0">
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
                                                        <select class="form-select form-select-sm order-contrast-select" :class="{ 'order-contrast-select--standard': item.tipo_contraste === 'Con contraste' }" :name="`exams[${cart.indexOf(item)}][tipo_contraste]`" x-model="item.tipo_contraste" @change="handleContrastChange(item)">
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
                                                        <button type="button" @click="removeByUid(item.uid)" class="btn btn-sm btn-danger" aria-label="Eliminar examen" title="Eliminar examen">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                                                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                                            </svg>
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

                            <div class="tab-pane fade" id="consumables-pane" role="tabpanel" aria-labelledby="consumables-tab" tabindex="0">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <div class="alert alert-info py-2 small mb-0 flex-grow-1">Los consumibles se precargan desde la configuración global según el contraste elegido. Puedes ajustar las cantidades antes de guardar.</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="preloadConsumablesFromCart(true)">Precargar configuración global</button>
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
                                                    <td class="text-end"><span class="badge text-bg-light">Configuración global</span></td>
                                                </tr>
                                            </template>
                                            <tr x-show="consumables.length === 0"><td colspan="4" class="text-center text-muted">Sin consumibles.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top clinic-card" style="top: 20px;">
                    <div class="card-header bg-primary text-white py-3 text-center fw-bold">RESUMEN DE COBRO</div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-bold mb-0">MÉTODOS DE PAGO</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="addPayment()" :disabled="payments.length >= paymentMethods.length">+ Agregar</button>
                            </div>
                            <template x-for="(payment, index) in payments" :key="index">
                                <div class="row g-2 mb-2 align-items-center">
                                    <div class="col-7">
                                        <select class="form-select form-select-sm" :name="`payments[${index}][payment_method]`" x-model="payment.payment_method" required>
                                            <template x-for="method in paymentMethods" :key="method"><option :value="method" x-text="method"></option></template>
                                        </select>
                                    </div>
                                    <div class="col-4"><input type="number" min="0" step="0.01" class="form-control form-control-sm text-end" :name="`payments[${index}][amount]`" x-model.number="payment.amount" required></div>
                                    <div class="col-1"><button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removePayment(index)" x-show="payments.length > 1" aria-label="Quitar método">×</button></div>
                                </div>
                            </template>
                            <div class="small" :class="paymentDifference() === 0 ? 'text-success' : 'text-danger'">
                                <span x-show="paymentDifference() === 0">Pago completo.</span>
                                <span x-show="paymentDifference() > 0">Falta asignar: S/ <span x-text="paymentDifference().toFixed(2)"></span></span>
                                <span x-show="paymentDifference() < 0">Excede el total: S/ <span x-text="Math.abs(paymentDifference()).toFixed(2)"></span></span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">DESCUENTO</label>
                            <input name="descuento" type="number" min="0" step="0.01" class="form-control" x-model.number="discount">
                        </div>

                        <div class="bg-light p-3 rounded mb-4 border text-center">
                            <div class="small text-muted">Subtotal: S/ <span x-text="subtotal().toFixed(2)"></span></div>
                            <h2 class="fw-bold text-primary mb-0">S/ <span x-text="total().toFixed(2)"></span></h2>
                        </div>

                        <div class="alert alert-danger py-2 small" x-show="clientError" x-text="clientError" x-cloak></div>
                        <button type="submit" class="btn btn-primary w-100 py-3 shadow fw-bold" :disabled="cart.length === 0 || isSubmitting">
                            <span x-show="!isSubmitting">CONFIRMAR Y GUARDAR</span>
                            <span x-show="isSubmitting">GUARDANDO...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="modal fade" id="patientModal" tabindex="-1" aria-labelledby="patientModalLabel" aria-hidden="true" x-ref="patientModal">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="patientModalLabel" x-text="patientForm.id ? 'Editar paciente' : 'Registrar paciente'"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" x-show="patientError" x-text="patientError"></div>
                    <div class="alert alert-warning" x-show="patientNotice" x-text="patientNotice"></div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">DNI</label>
                            <div class="input-group">
                                <input type="text" inputmode="numeric" maxlength="8" class="form-control" x-model="patientForm.dni" placeholder="8 dígitos" @input="handleDniInput($event)" @blur="lookupReniec()">
                                <button type="button" class="btn btn-outline-primary" @click="lookupReniec()" :disabled="reniecLoading || !/^\d{8}$/.test(patientForm.dni)">
                                    <span x-show="!reniecLoading">RENIEC</span>
                                    <span x-show="reniecLoading">Buscando...</span>
                                </button>
                            </div>
                            <div class="form-text">Consulta Decolecta RENIEC por DNI para completar nombres y apellidos.</div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Nombres</label>
                            <input type="text" class="form-control" x-model="patientForm.nombres" x-ref="patientNames">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Apellidos</label>
                            <input type="text" class="form-control" x-model="patientForm.apellidos">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Teléfono</label>
                            <input type="text" class="form-control" x-model="patientForm.telefono">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Fecha de nacimiento</label>
                            <input type="date" class="form-control" x-model="patientForm.fecha_nacimiento" @change="calculatePatientAge()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Edad</label>
                            <input type="number" class="form-control" x-model="patientForm.edad" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="savePatient()" :disabled="patientSaving">
                        <span x-show="!patientSaving">Guardar paciente</span>
                        <span x-show="patientSaving">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

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
        itemSelect: null,
        selectedPatientId: String({{ Illuminate\Support\Js::from(old('patient_id', $order->patient_id)) }} || ''),
        patients: {{ Illuminate\Support\Js::from($patients->map(fn ($p) => ['id' => (string) $p->id, 'dni' => $p->dni, 'nombres' => $p->nombres, 'apellidos' => $p->apellidos, 'telefono' => $p->telefono, 'fecha_nacimiento' => optional($p->fecha_nacimiento)->format('Y-m-d'), 'edad' => $p->edad, 'label' => $p->dni.' - '.$p->nombres.' '.$p->apellidos])->values()) }},
        patientForm: { id: null, dni: '', nombres: '', apellidos: '', telefono: '', fecha_nacimiento: '', edad: '' },
        patientError: '',
        patientNotice: '',
        patientSaving: false,
        reniecLoading: false,
        lastReniecDni: '',
        reagents: {{ Illuminate\Support\Js::from($reagents->map(fn ($r) => ['id' => (string) $r->id, 'name' => $r->nombre, 'unit' => $r->unidad_medida])->values()) }},
        globalConsumables: {{ Illuminate\Support\Js::from($globalConsumables->groupBy('tipo_contraste')->map(fn ($rows) => $rows->map(fn ($row) => ['reagent_id' => (string) $row->reagent_id, 'name' => $row->reagent->nombre, 'unit' => $row->reagent->unidad_medida, 'cantidad' => (float) $row->cantidad_estimada])->values())) }},
        agreementPrices: {{ Illuminate\Support\Js::from($agreementPrices->map(fn ($price) => [
            'agreement_id' => (string) $price->agreement_id,
            'exam_id' => (string) $price->exam_id,
            'tipo_contraste' => $price->tipo_contraste,
            'price' => (float) $price->precio_pactado,
        ])->values()) }},
        selectedAgreement: String({{ Illuminate\Support\Js::from(old('agreement_id', $order->agreement_id ?? $agreements->first()?->id)) }} || ''),
        discount: Number({{ Illuminate\Support\Js::from(old('descuento', $order->descuento ?? 0)) }}) || 0,
        payments: {{ Illuminate\Support\Js::from($paymentRows->map(fn ($payment) => ['payment_method' => $payment['payment_method'], 'amount' => (float) $payment['amount']])->values()) }},
        paymentMethods: {{ Illuminate\Support\Js::from($tiposPago) }},
        cartSearch: '',
        exams: [],
        isSubmitting: false,
        clientError: '',
        init() {
            this.preloadConsumablesFromCart(false);

            this.$nextTick(() => document.getElementById('order-validation-errors')?.focus());

            document.querySelectorAll('.js-tom-select').forEach((select) => {
                if (!select.tomselect) {
                    new TomSelect(select, {
                        create: false,
                        allowEmptyOption: true,
                        placeholder: select.dataset.placeholder || 'Buscar...',
                        plugins: ['clear_button'],
                        onChange: (value) => {
                            if (select.id === 'patient_select') this.selectedPatientId = String(value || '');
                        }
                    });
                }
            });

            const exams = {{ Illuminate\Support\Js::from($exams->map(fn ($e) => ['id' => (string) $e->id, 'name' => $e->nombre_examen, 'uid' => 'exam'.$e->id, 'area' => 'TOMOGRAFÍA', 'configured_contrast' => $e->tipo_contraste])->values()) }};
            this.exams = exams;
            this.itemSelect = new TomSelect('#item_select', {
                valueField: 'uid',
                labelField: 'display_name',
                searchField: ['name', 'display_name'],
                options: this.availableExams().map((exam) => ({ ...exam, display_name: `${exam.name} [EXAMEN]` })),
                maxOptions: 50,
                shouldLoad: (query) => query.length >= 2,
                render: {
                    option: (data, escape) => `<div>${escape(data.name)} <span class="text-primary fw-bold">[EXAMEN]</span></div>`,
                    item: (data, escape) => `<div>${escape(data.name)} <span class="text-primary fw-bold">[EXAMEN]</span></div>`
                },
                onChange: (value) => {
                    if (!value) return;
                    const item = this.$el.querySelector('#item_select').tomselect.options[value];
                    if (!this.cart.find((cartItem) => cartItem.name === item.name)) {
                        const contrast = this.defaultContrastFor(item.name);
                        const variant = this.examVariantFor(item, contrast);
                        this.cart.push({ ...item, ...variant, type: 'exam', tipo_contraste: contrast, estado: 'Pendiente', price: this.priceFor(variant.id, contrast) });
                        this.rebuildConsumablesFromExams();
                    }
                    this.$el.querySelector('#item_select').tomselect.clear();
                }
            });
        },
        resetPatientForm() {
            this.patientForm = { id: null, dni: '', nombres: '', apellidos: '', telefono: '', fecha_nacimiento: '', edad: '' };
            this.patientError = '';
            this.patientNotice = '';
            this.lastReniecDni = '';
        },
        openPatientModal(patientId = null) {
            this.resetPatientForm();
            const patient = this.patients.find((item) => item.id === String(patientId));
            if (patient) this.patientForm = { ...patient };
            bootstrap.Modal.getOrCreateInstance(this.$refs.patientModal).show();
        },
        async lookupReniec() {
            const dni = String(this.patientForm.dni || '').replace(/\D/g, '');
            if (dni.length !== 8 || this.reniecLoading || this.lastReniecDni === dni) return;
            if (this.fillExistingPatientByDni(dni)) return;
            this.patientError = '';
            this.patientNotice = '';
            this.reniecLoading = true;
            try {
                const response = await fetch(`{{ route('patients.reniec') }}?numero=${encodeURIComponent(dni)}`, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (!response.ok) {
                    const error = new Error(data.message || 'No se encontró información para el DNI.');
                    error.manualEntry = Boolean(data.manual_entry);
                    throw error;
                }
                this.patientForm.nombres = data.first_name || '';
                this.patientForm.apellidos = [data.first_last_name, data.second_last_name].filter(Boolean).join(' ');
                this.lastReniecDni = dni;
                this.$nextTick(() => this.$refs.patientNames?.focus());
            } catch (error) {
                this.patientError = error.message || 'No se pudo consultar RENIEC.';
                if (error.manualEntry) this.$nextTick(() => this.$refs.patientNames?.focus());
            } finally {
                this.reniecLoading = false;
            }
        },
        handleDniInput(event) {
            const dni = event.target.value.replace(/\D/g, '').slice(0, 8);
            this.patientForm.dni = dni;
            this.patientNotice = '';
            if (this.lastReniecDni !== dni) this.lastReniecDni = '';
            if (dni.length === 8 && !this.fillExistingPatientByDni(dni)) this.lookupReniec();
        },
        fillExistingPatientByDni(dni) {
            const existing = this.patients.find((patient) => String(patient.dni || '') === String(dni));
            if (!existing) return false;

            this.patientForm = { ...existing };
            this.patientError = '';
            this.patientNotice = 'El paciente ya existe en la base de datos. Se completaron sus datos para evitar una consulta externa innecesaria.';
            this.lastReniecDni = dni;
            this.upsertPatient(existing);

            return true;
        },
        calculatePatientAge() {
            if (!this.patientForm.fecha_nacimiento) return this.patientForm.edad = '';
            const birth = new Date(`${this.patientForm.fecha_nacimiento}T00:00:00`);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) age--;
            this.patientForm.edad = Math.max(0, age);
        },
        async savePatient() {
            this.patientError = '';
            this.patientSaving = true;
            try {
                const isEdit = Boolean(this.patientForm.id);
                const response = await fetch(isEdit ? `/patients/${this.patientForm.id}` : `{{ route('patients.store') }}`, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        dni: this.patientForm.dni,
                        nombres: this.patientForm.nombres,
                        apellidos: this.patientForm.apellidos,
                        telefono: this.patientForm.telefono,
                        fecha_nacimiento: this.patientForm.fecha_nacimiento,
                    }),
                });
                const data = await response.json();
                if (!response.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    throw new Error(errors || 'No se pudo guardar el paciente.');
                }
                this.upsertPatient(data.patient);
                bootstrap.Modal.getInstance(this.$refs.patientModal).hide();
            } catch (error) {
                this.patientError = error.message || 'No se pudo guardar el paciente.';
            } finally {
                this.patientSaving = false;
            }
        },
        upsertPatient(patient) {
            patient.id = String(patient.id);
            const index = this.patients.findIndex((item) => item.id === patient.id);
            if (index === -1) this.patients.push(patient);
            else this.patients.splice(index, 1, patient);
            const select = document.getElementById('patient_select');
            if (select?.tomselect) {
                select.tomselect.addOption({ value: patient.id, text: patient.label });
                select.tomselect.updateOption(patient.id, { value: patient.id, text: patient.label });
                select.tomselect.addItem(patient.id, true);
                select.tomselect.setValue(patient.id, true);
            }
            this.selectedPatientId = patient.id;
        },
        filteredCart() {
            const term = this.cartSearch.toLowerCase();
            return this.cart.filter((item) => !term || item.name.toLowerCase().includes(term) || (item.area || '').toLowerCase().includes(term));
        },
        removeByUid(uid) {
            const index = this.cart.findIndex((item) => item.uid === uid);
            if (index !== -1) {
                this.cart.splice(index, 1);
                this.rebuildConsumablesFromExams();
            }
        },
        handleContrastChange(item) {
            const variant = this.examVariantFor(item, item.tipo_contraste);
            item.id = variant.id;
            item.uid = variant.uid;
            item.configured_contrast = variant.configured_contrast;
            item.price = this.priceFor(item.id, item.tipo_contraste);
            this.rebuildConsumablesFromExams();
        },
        examVariantFor(item, contrast) {
            const exactVariant = this.exams.find((exam) => exam.name === item.name && exam.configured_contrast === contrast && this.priceFor(exam.id, contrast) > 0);
            if (exactVariant) return exactVariant;

            const allowedVariant = this.exams.find((exam) => exam.name === item.name && this.priceFor(exam.id, contrast) > 0);
            if (allowedVariant) return allowedVariant;

            return this.exams.find((exam) => exam.name === item.name && exam.configured_contrast === contrast) || item;
        },
        priceFor(examId, contrast) {
            const match = this.agreementPrices.find((price) => price.agreement_id === String(this.selectedAgreement) && price.exam_id === String(examId) && price.tipo_contraste === contrast);
            return match ? Number(match.price) : 0;
        },
        defaultContrastFor(examName) {
            const variants = this.exams.filter((exam) => exam.name === examName);
            if (variants.some((exam) => this.isExamAllowed(exam.id, 'Sin contraste'))) return 'Sin contraste';
            if (variants.some((exam) => this.isExamAllowed(exam.id, 'Con contraste'))) return 'Con contraste';

            return variants[0]?.configured_contrast || 'Sin contraste';
        },
        preloadConsumablesFromCart(force = false) {
            if (!force && this.consumables.length > 0) return;
            this.rebuildConsumablesFromExams();
        },
        addConsumable() {
            const reagent = this.reagents.find((item) => item.id === String(this.selectedReagent));
            if (!reagent) return;
            const existing = this.consumables.find((item) => item.reagent_id === reagent.id);
            if (existing) existing.cantidad = Number(existing.cantidad || 0) + 1;
            else this.consumables.push({ reagent_id: reagent.id, name: reagent.name, unit: reagent.unit, cantidad: 1 });
            this.selectedReagent = '';
        },
        rebuildConsumablesFromExams() {
            const totals = new Map();
            [...new Set(this.cart.map((item) => item.tipo_contraste))]
                .forEach((contrastType) => {
                    (this.globalConsumables[contrastType] || [])
                        .forEach((row) => {
                            const current = totals.get(row.reagent_id) || { ...row, cantidad: 0 };
                            current.cantidad = Number(current.cantidad || 0) + Number(row.cantidad || 0);
                            totals.set(row.reagent_id, current);
                        });
                });
            this.consumables = Array.from(totals.values()).filter((row) => Number(row.cantidad || 0) > 0);
        },
        mergeExamConsumables(examId) {
            this.rebuildConsumablesFromExams();
        },
        applyAgreementPrices() {
            this.cart = this.cart
                .map((item) => ({ ...item, ...this.examVariantFor(item, item.tipo_contraste) }))
                .map((item) => ({
                    ...item,
                    price: this.isExamAllowed(item.id, item.tipo_contraste)
                        ? this.priceFor(item.id, item.tipo_contraste)
                        : item.price,
                }));
            this.refreshExamOptions();
            this.rebuildConsumablesFromExams();
        },
        allowedExamIds() {
            return new Set(this.agreementPrices
                .filter((price) => price.agreement_id === String(this.selectedAgreement))
                .map((price) => String(price.exam_id)));
        },
        isExamAllowed(examId, contrast = null) {
            return this.agreementPrices.some((price) =>
                price.agreement_id === String(this.selectedAgreement)
                && price.exam_id === String(examId)
                && (contrast === null || price.tipo_contraste === contrast)
            );
        },
        availableExams() {
            const names = new Set();

            return this.exams.filter((exam) => {
                if (names.has(exam.name)) return false;
                names.add(exam.name);

                return true;
            });
        },
        refreshExamOptions() {
            if (!this.itemSelect) return;
            this.itemSelect.clear(true);
            this.itemSelect.clearOptions();
            this.itemSelect.addOptions(this.availableExams().map((exam) => ({ ...exam, display_name: `${exam.name} [EXAMEN]` })));
            this.itemSelect.refreshOptions(false);
        },
        subtotal() {
            return this.cart.reduce((sum, item) => sum + (Number(item.price) || 0), 0);
        },
        submitOrder(event) {
            this.clientError = '';
            if (this.paymentDifference() !== 0 || new Set(this.payments.map((payment) => payment.payment_method)).size !== this.payments.length) {
                event.preventDefault();
                this.clientError = this.paymentDifference() !== 0 ? 'La suma de los pagos debe coincidir con el total de la orden.' : 'No se puede repetir un método de pago.';
                return false;
            }
            if (this.isSubmitting) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return false;
            }

            this.isSubmitting = true;
            event.submitter?.setAttribute('disabled', 'disabled');
            return true;
        },
        total() {
            return Math.max(this.subtotal() - (Number(this.discount) || 0), 0);
        },
        paymentDifference() {
            return Math.round((this.total() - this.payments.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0)) * 100) / 100;
        },
        addPayment() {
            const used = new Set(this.payments.map((payment) => payment.payment_method));
            const method = this.paymentMethods.find((item) => !used.has(item));
            if (method) this.payments.push({ payment_method: method, amount: Math.max(this.paymentDifference(), 0) });
        },
        removePayment(index) {
            this.payments.splice(index, 1);
        }
    }
}
</script>
@endpush
