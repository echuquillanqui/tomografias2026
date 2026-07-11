@extends('layouts.app')

@section('content')
@php
    $report = $order->report;
    $patientName = $order->patient->nombres.' '.$order->patient->apellidos;
    $examNames = $order->orderExams->pluck('exam.nombre_examen')->filter()->implode(', ');
    $contrast = $order->orderExams->contains('tipo_contraste', 'Con contraste') ? 'Con contraste endovenoso' : 'Sin contraste';
    $contenido = $report->contenido;
    $defaultTechnique = 'Se realizó tomografía computarizada de '.($examNames ?: '[región anatómica]').' mediante adquisición helicoidal/multicorte, con reconstrucciones multiplanares.';
    $defaultFindings = "Se evalúan las estructuras anatómicas incluidas en el campo de estudio.\n\n[Describir informe tomográfico según el estudio.]";
    $defaultImpression = "1. [Conclusión principal del estudio.]\n2. [Hallazgo secundario relevante, si existe.]";
    $defaultRecommendations = '';
@endphp
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <div class="clinic-eyebrow mb-2">Plantilla editable de informe</div>
                <h1 class="display-6 fw-bold">{{ $patientName }}</h1>
                <p class="mb-0 opacity-75">{{ $order->codigo_orden ?? 'Orden #'.$order->id }} · {{ $order->fecha_orden->format('d/m/Y') }} · {{ $examNames ?: 'Estudio por definir' }}</p>
            </div>
            <div class="d-flex gap-2 align-items-start">
                <a class="btn btn-light" href="{{ route('reports.pdf', $order) }}" target="_blank">Ver PDF</a>
                <a class="btn btn-outline-light" href="{{ route('reports.index') }}">Volver</a>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('reports.update', $order) }}" class="report-editor">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card clinic-card p-4 report-guide-card sticky-xl-top">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold mb-0">Guía de la orden</h5>
                        <span class="badge badge-role">{{ $order->estado }}</span>
                    </div>
                    <div class="guide-item"><span>Paciente</span><strong>{{ $patientName }}</strong></div>
                    <div class="guide-item"><span>DNI / Edad</span><strong>{{ $order->patient->dni }} · {{ $order->patient->edad ? $order->patient->edad.' años' : 'Edad no registrada' }}</strong></div>
                    <div class="guide-item"><span>Estudio</span><strong>{{ $examNames ?: 'Sin examen registrado' }}</strong></div>
                    <div class="guide-item"><span>Contraste</span><strong>{{ $contrast }}</strong></div>
                    <div class="guide-item"><span>Médico solicitante</span><strong>{{ $order->medicoSolicitante?->nombre_completo ?? '—' }}</strong></div>
                    <p class="text-muted small mb-0 mt-3">Estos datos ya están cargados para que sirvan como referencia mientras redactas. El informe se completa en los campos de la derecha.</p>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card clinic-card p-4 mb-4">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <label class="form-label small fw-bold">Título del informe</label>
                            <input name="titulo" class="form-control form-control-lg @error('titulo') is-invalid @enderror" value="{{ old('titulo', $report->titulo) }}" placeholder="Ej. TOMOGRAFÍA DE CRÁNEO SIN CONTRASTE" required>
                            @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label small fw-bold">Médico que firmará</label>
                            <select name="medico_firmante_id" class="form-select form-select-lg @error('medico_firmante_id') is-invalid @enderror">
                                <option value="">Sin médico / firma en blanco</option>
                                @foreach($medicosInformantes as $medico)
                                    <option value="{{ $medico->id }}" @selected(old('medico_firmante_id', $report->medico_firmante_id ?? $order->medico_informe_id) == $medico->id)>
                                        {{ $medico->nombre_completo }}{{ $medico->firma_path ? ' · con firma' : ' · sin firma' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('medico_firmante_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="card clinic-card p-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Campos a completar</h5>
                            <p class="text-muted mb-0">Redacta solo la información final. Los campos vacíos no aparecerán en el PDF.</p>
                        </div>
                        <span class="badge rounded-pill text-bg-light">Plantilla médica</span>
                    </div>
                    <div class="report-section-grid">
                        <div class="report-field">
                            <label class="form-label small fw-bold">Técnica</label>
                            <textarea name="tecnica" rows="4" class="form-control report-content-box @error('tecnica') is-invalid @enderror" required>{{ old('tecnica', $report->tecnica ?? $defaultTechnique) }}</textarea>
                            @error('tecnica') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="report-field">
                            <label class="form-label small fw-bold">Informe</label>
                            <textarea name="informe" rows="8" class="form-control report-content-box @error('informe') is-invalid @enderror" required>{{ old('informe', $report->informe ?? $defaultFindings) }}</textarea>
                            @error('informe') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="report-field">
                            <label class="form-label small fw-bold">Impresión diagnóstica</label>
                            <textarea name="impresion" rows="5" class="form-control report-content-box @error('impresion') is-invalid @enderror" required>{{ old('impresion', $report->impresion ?? $defaultImpression) }}</textarea>
                            @error('impresion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="report-field">
                            <label class="form-label small fw-bold">Recomendaciones / notas</label>
                            <textarea name="recomendaciones" rows="3" class="form-control report-content-box @error('recomendaciones') is-invalid @enderror">{{ old('recomendaciones', $report->recomendaciones ?? $defaultRecommendations) }}</textarea>
                            @error('recomendaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <details class="mt-3">
                        <summary class="text-muted small">Ver contenido original precargado</summary>
                        <pre class="original-report-preview mt-2 mb-0">{{ $contenido }}</pre>
                    </details>
                    <div class="form-text">El PDF mostrará los datos completados con formato de informe médico, amplio y listo para firma; la impresión diagnóstica siempre tendrá una sección visible.</div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="{{ route('reports.index') }}">Cancelar</a>
                    <button class="btn btn-clinic-primary px-4">Guardar informe</button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<style>
    .report-editor .clinic-card { border: 1px solid rgba(15, 42, 68, .08); }
    .report-guide-card { top: 1rem; }
    .guide-item { background: #f8fafc; border: 1px solid #e5edf5; border-radius: 14px; padding: .85rem 1rem; margin-bottom: .75rem; }
    .guide-item span { color: #64748b; display: block; font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .guide-item strong { color: #0f172a; display: block; margin-top: .2rem; }
    .report-section-grid { display: grid; gap: 1rem; }
    .report-field { background: #f8fafc; border: 1px solid #e5edf5; border-radius: 20px; padding: 1rem; }
    .report-content-box { background: linear-gradient(#ffffff, #fbfdff); border: 1px solid #cbd5e1; border-radius: 14px; box-shadow: inset 0 1px 2px rgba(15, 23, 42, .04); font-size: 1rem; line-height: 1.65; padding: 1rem; }
    .report-content-box:focus { border-color: #14b8a6; box-shadow: 0 0 0 .25rem rgba(20, 184, 166, .12); }
    .original-report-preview { background: #0f172a; border-radius: 14px; color: #e2e8f0; max-height: 260px; overflow: auto; padding: 1rem; white-space: pre-wrap; }
</style>
@endpush
@endsection
