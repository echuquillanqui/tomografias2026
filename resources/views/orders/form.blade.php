@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex justify-content-between">
            <div>
                <div class="clinic-eyebrow mb-2">Orden médica</div>
                <h1 class="display-6 fw-bold">{{ $mode === 'create' ? 'Generar orden' : 'Editar orden '.$order->codigo_orden }}</h1>
                <p class="mb-0 opacity-75">Página individual para seleccionar paciente, convenio, médicos y estudios.</p>
            </div>
            <a class="btn btn-light" href="{{ route('orders.index') }}">Volver</a>
        </div>
    </section>

    <form method="POST" action="{{ $mode === 'create' ? route('orders.store') : route('orders.update', $order) }}" class="card clinic-card p-4">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Paciente</label>
                <select name="patient_id" class="form-select js-tom-select" data-placeholder="Buscar paciente por DNI, nombres o apellidos" required>
                    <option value=""></option>
                    @foreach($patients as $p)
                        <option value="{{ $p->id }}" @selected(old('patient_id', $order->patient_id) == $p->id)>{{ $p->dni }} - {{ $p->nombres }} {{ $p->apellidos }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Convenio</label>
                <select name="agreement_id" class="form-select" required>
                    @foreach($agreements as $a)
                        <option value="{{ $a->id }}" @selected(old('agreement_id', $order->agreement_id) == $a->id)>{{ $a->nombre_institucion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha</label>
                <input name="fecha_orden" type="date" class="form-control" value="{{ old('fecha_orden', optional($order->fecha_orden)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select" required>
                    @foreach($estados as $e)
                        <option @selected(old('estado', $order->estado) === $e)>{{ $e }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipo de pago</label>
                <select name="tipo_pago" class="form-select" required>
                    @foreach($tiposPago as $tipo)
                        <option value="{{ $tipo }}" @selected(old('tipo_pago', $order->tipo_pago ?? 'Efectivo') === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Médico solicitante</label>
                <select name="medico_solicitante_id" class="form-select js-tom-select" data-placeholder="Buscar médico solicitante">
                    <option value=""></option>
                    @foreach($medicosSolicitantes as $m)
                        <option value="{{ $m->id }}" @selected(old('medico_solicitante_id', $order->medico_solicitante_id) == $m->id)>{{ $m->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Médico informe</label>
                <select name="medico_informe_id" class="form-select js-tom-select" data-placeholder="Buscar médico informante">
                    <option value=""></option>
                    @foreach($medicosInformantes as $m)
                        <option value="{{ $m->id }}" @selected(old('medico_informe_id', $order->medico_informe_id) == $m->id)>{{ $m->nombre_completo }} ({{ $m->comision_porcentaje ?? 0 }}%)</option>
                    @endforeach
                </select>
            </div>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Estudios</h5>
            <button type="button" class="btn btn-outline-primary btn-sm" id="add-exam-row">+ Agregar examen</button>
        </div>
        @php($rows = old('exams', $order->orderExams->toArray() ?: [[]]))
        <div id="exam-rows" class="vstack gap-2">
            @foreach($rows as $i => $row)
                <div class="row g-2 exam-row align-items-start">
                    <div class="col-md-5">
                        <select name="exams[{{ $i }}][exam_id]" class="form-select js-exam-select" data-placeholder="Buscar y seleccionar examen" required>
                            <option value=""></option>
                            @foreach($exams as $e)
                                <option value="{{ $e->id }}" @selected(($row['exam_id'] ?? null) == $e->id)>{{ $e->nombre_examen }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><select name="exams[{{ $i }}][tipo_contraste]" class="form-select"><option>Sin contraste</option><option @selected(($row['tipo_contraste'] ?? '') === 'Con contraste')>Con contraste</option></select></div>
                    <div class="col-md-2"><input name="exams[{{ $i }}][precio]" type="number" step="0.01" class="form-control" placeholder="Precio" value="{{ $row['precio'] ?? '' }}" required></div>
                    <div class="col-md-2"><select name="exams[{{ $i }}][estado]" class="form-select"><option @selected(($row['estado'] ?? '') === 'Pendiente')>Pendiente</option><option @selected(($row['estado'] ?? '') === 'Realizado')>Realizado</option><option @selected(($row['estado'] ?? '') === 'Informado')>Informado</option><option @selected(($row['estado'] ?? '') === 'Anulado')>Anulado</option></select></div>
                    <div class="col-md-1 d-grid"><button type="button" class="btn btn-outline-danger remove-exam-row">×</button></div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-3 ms-auto"><label class="form-label">Descuento</label><input name="descuento" type="number" step="0.01" class="form-control" value="{{ old('descuento', $order->descuento ?? 0) }}"></div>
            <div class="col-12"><textarea name="observaciones" class="form-control" rows="3" placeholder="Observaciones">{{ old('observaciones', $order->observaciones) }}</textarea></div>
        </div>
        <div class="text-end mt-4"><button class="btn btn-clinic-primary px-5 py-3">Guardar orden</button></div>
    </form>
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const exams = @json($exams->map(fn ($e) => ['id' => $e->id, 'name' => $e->nombre_examen])->values());
    const initTom = (select) => {
        if (select.tomselect) return;
        new TomSelect(select, {create: false, allowEmptyOption: true, placeholder: select.dataset.placeholder || 'Buscar...'});
    };
    document.querySelectorAll('.js-tom-select, .js-exam-select').forEach(initTom);
    const rows = document.getElementById('exam-rows');
    const reindexRows = () => rows.querySelectorAll('.exam-row').forEach((row, index) => row.querySelectorAll('[name]').forEach((field) => field.name = field.name.replace(/exams\[\d+]/, `exams[${index}]`)));
    document.getElementById('add-exam-row').addEventListener('click', () => {
        const index = rows.querySelectorAll('.exam-row').length;
        const options = exams.map(exam => `<option value="${exam.id}">${exam.name}</option>`).join('');
        rows.insertAdjacentHTML('beforeend', `<div class="row g-2 exam-row align-items-start"><div class="col-md-5"><select name="exams[${index}][exam_id]" class="form-select js-exam-select" data-placeholder="Buscar y seleccionar examen" required><option value=""></option>${options}</select></div><div class="col-md-2"><select name="exams[${index}][tipo_contraste]" class="form-select"><option>Sin contraste</option><option>Con contraste</option></select></div><div class="col-md-2"><input name="exams[${index}][precio]" type="number" step="0.01" class="form-control" placeholder="Precio" required></div><div class="col-md-2"><select name="exams[${index}][estado]" class="form-select"><option>Pendiente</option><option>Realizado</option><option>Informado</option><option>Anulado</option></select></div><div class="col-md-1 d-grid"><button type="button" class="btn btn-outline-danger remove-exam-row">×</button></div></div>`);
        initTom(rows.querySelector('.exam-row:last-child .js-exam-select'));
    });
    rows.addEventListener('click', (event) => {
        if (! event.target.classList.contains('remove-exam-row') || rows.querySelectorAll('.exam-row').length === 1) return;
        event.target.closest('.exam-row').remove();
        reindexRows();
    });
});
</script>
@endpush
