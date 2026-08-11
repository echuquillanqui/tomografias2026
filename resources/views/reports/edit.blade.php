@extends('layouts.app')

@section('content')
@php
    $report = $order->report;
    $patientName = $order->patient->nombres.' '.$order->patient->apellidos;
    $examNames = $order->orderExams->pluck('exam.nombre_examen')->filter()->implode(', ');
    $contrast = $order->orderExams->contains('tipo_contraste', 'Con contraste') ? 'Con contraste endovenoso' : 'Sin contraste';
@endphp
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <div class="clinic-eyebrow mb-2">Plantilla editable de informe</div>
                <h1 class="display-6 fw-bold">{{ $patientName }}</h1>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <p class="mb-0 opacity-75">{{ $order->codigo_orden ?? 'Orden #'.$order->id }} · {{ $order->fecha_orden->format('d/m/Y H:i') }} · {{ $examNames ?: 'Estudio por definir' }}</p>
                    <span class="badge report-status-badge">{{ $order->estado }}</span>
                </div>
                <div class="report-patient-summary">
                    <div class="report-summary-item">
                        <span>DNI / Edad</span>
                        <strong>{{ $order->patient->dni }} · {{ $order->patient->edad ? $order->patient->edad.' años' : 'Edad no registrada' }}</strong>
                    </div>
                    <div class="report-summary-item">
                        <span>Estudio</span>
                        <strong>{{ $examNames ?: 'Sin examen registrado' }}</strong>
                    </div>
                    <div class="report-summary-item">
                        <span>Contraste</span>
                        <strong>{{ $contrast }}</strong>
                    </div>
                    <div class="report-summary-item">
                        <span>Médico solicitante</span>
                        <strong>{{ $order->medicoSolicitante?->nombre ?? '—' }}</strong>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-start">
                <a class="btn btn-light" href="{{ route('reports.pdf', $order) }}" target="_blank">Ver PDF</a>
                <a class="btn btn-outline-light" href="{{ route('reports.index') }}">Volver</a>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('reports.update', $order) }}" class="report-editor" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-12">
                <div class="card clinic-card p-4 mb-4">
                    <div class="row g-3 justify-content-center">
                        <div class="col-lg-7">
                            <label class="form-label small fw-bold">Médico informante</label>
                            <select name="medico_firmante_id" class="form-select form-select-lg @error('medico_firmante_id') is-invalid @enderror">
                                <option value="">Sin médico</option>
                                @foreach($medicosInformantes as $medico)
                                    <option value="{{ $medico->id }}" @selected(old('medico_firmante_id', $report->medico_firmante_id ?? $order->medico_informe_id) == $medico->id)>
                                        {{ $medico->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('medico_firmante_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="card clinic-card p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Archivos e imágenes del informe</h5>
                            <p class="text-muted mb-0">Adjunta todos los PDF o imágenes que necesites. Las imágenes se reducen y convierten automáticamente a un formato optimizado.</p>
                        </div>
                        <span class="badge rounded-pill text-bg-light">PDF, JPG, PNG o WebP · 20 MB c/u</span>
                    </div>
                    <label class="form-label small fw-bold" for="reportAttachments">Seleccionar archivos</label>
                    <input id="reportAttachments" type="file" name="adjuntos[]" class="form-control @error('adjuntos.*') is-invalid @enderror" accept="application/pdf,image/jpeg,image/png,image/webp" multiple>
                    @error('adjuntos.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Puedes volver a seleccionar más archivos cada vez que guardes el informe; no existe un límite de cantidad.</div>

                    @if($report->attachments->isNotEmpty())
                        <div class="report-attachment-list mt-4">
                            @foreach($report->attachments as $attachment)
                                <div class="report-attachment-item">
                                    <div>
                                        <strong class="d-block">{{ $attachment->original_name }}</strong>
                                        <small class="text-muted">{{ str_starts_with($attachment->mime_type, 'image/') ? 'Imagen optimizada' : 'Documento PDF' }} · {{ number_format($attachment->stored_size / 1024, 1) }} KB</small>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary js-view-attachment" data-bs-toggle="modal" data-bs-target="#viewAttachmentModal" data-preview-url="{{ route('reports.attachments.view', [$order, $attachment]) }}" data-preview-name="{{ $attachment->original_name }}" data-preview-type="{{ str_starts_with($attachment->mime_type, 'image/') ? 'image' : 'pdf' }}">Ver</button>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('reports.attachments.download', [$order, $attachment]) }}">Descargar</a>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAttachment{{ $attachment->id }}">Editar</button>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" form="delete-attachment-{{ $attachment->id }}" onclick="return confirm('¿Eliminar este archivo?')">Eliminar</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="{{ route('reports.index') }}">Cancelar</a>
                    <button class="btn btn-clinic-primary px-4">Guardar informe</button>
                </div>
            </div>
        </div>
    </form>
    @foreach($report->attachments as $attachment)
        <div class="modal fade" id="editAttachment{{ $attachment->id }}" tabindex="-1" aria-labelledby="editAttachmentLabel{{ $attachment->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('reports.attachments.update', [$order, $attachment]) }}" enctype="multipart/form-data" class="modal-content border-0 shadow">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="editAttachmentLabel{{ $attachment->id }}">Editar archivo adjunto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="attachmentName{{ $attachment->id }}">Nombre del archivo</label>
                            <input id="attachmentName{{ $attachment->id }}" name="nombre" class="form-control" value="{{ $attachment->original_name }}" maxlength="255" required>
                        </div>
                        <div>
                            <label class="form-label fw-bold" for="attachmentFile{{ $attachment->id }}">Reemplazar archivo <span class="text-muted fw-normal">(opcional)</span></label>
                            <input id="attachmentFile{{ $attachment->id }}" type="file" name="archivo" class="form-control" accept="application/pdf,image/jpeg,image/png,image/webp">
                            <div class="form-text">Si no seleccionas otro archivo, solo se actualizará el nombre.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-clinic-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
        <form id="delete-attachment-{{ $attachment->id }}" method="POST" action="{{ route('reports.attachments.destroy', [$order, $attachment]) }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    <div class="modal fade" id="viewAttachmentModal" tabindex="-1" aria-labelledby="viewAttachmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-truncate" id="viewAttachmentModalLabel">Vista previa del archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div id="attachmentPreview" class="modal-body attachment-preview bg-light">
                    <iframe id="attachmentPreviewFrame" class="attachment-preview-frame d-none" title="Vista previa del documento PDF"></iframe>
                    <img id="attachmentPreviewImage" class="attachment-preview-image d-none" alt="">
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .report-editor .clinic-card { border: 1px solid rgba(15, 42, 68, .08); }
    .report-status-badge { background: rgba(236, 253, 245, .94); color: #047857; font-weight: 800; letter-spacing: .01em; }
    .report-patient-summary { display: grid; gap: .75rem; grid-template-columns: repeat(4, minmax(0, 1fr)); max-width: 100%; }
    .report-summary-item { background: rgba(255, 255, 255, .12); border: 1px solid rgba(255, 255, 255, .2); border-radius: 16px; padding: .8rem 1rem; backdrop-filter: blur(8px); }
    .report-summary-item span { color: rgba(255, 255, 255, .68); display: block; font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .report-summary-item strong { color: #fff; display: block; line-height: 1.35; margin-top: .2rem; }
    @media (max-width: 1199.98px) { .report-patient-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575.98px) { .report-patient-summary { grid-template-columns: 1fr; } }
    .report-section-grid { display: grid; gap: 1rem; }
    .report-field { background: #f8fafc; border: 1px solid #e5edf5; border-radius: 20px; padding: 1rem; }
    .report-content-box { background: linear-gradient(#ffffff, #fbfdff); border: 1px solid #cbd5e1; border-radius: 14px; box-shadow: inset 0 1px 2px rgba(15, 23, 42, .04); font-size: 1rem; line-height: 1.65; padding: 1rem; }
    .report-content-box:focus { border-color: #14b8a6; box-shadow: 0 0 0 .25rem rgba(20, 184, 166, .12); }
    .original-report-preview { background: #0f172a; border-radius: 14px; color: #e2e8f0; max-height: 260px; overflow: auto; padding: 1rem; white-space: pre-wrap; }
    .report-attachment-list { display: grid; gap: .75rem; }
    .report-attachment-item { align-items: center; background: #f8fafc; border: 1px solid #e5edf5; border-radius: 14px; display: flex; gap: 1rem; justify-content: space-between; padding: .85rem 1rem; }
    .attachment-preview { align-items: center; display: flex; height: min(78vh, 850px); justify-content: center; overflow: hidden; padding: 1rem; }
    .attachment-preview-frame { aspect-ratio: 210 / 297; background: #fff; border: 0; box-shadow: 0 8px 24px rgba(15, 23, 42, .14); height: 100%; max-width: 100%; }
    .attachment-preview-image { display: block; height: auto; max-height: 100%; max-width: 100%; object-fit: contain; width: auto; }
    @media (max-width: 575.98px) { .report-attachment-item { align-items: stretch; flex-direction: column; } }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('viewAttachmentModal');
        const frame = document.getElementById('attachmentPreviewFrame');
        const image = document.getElementById('attachmentPreviewImage');
        const title = document.getElementById('viewAttachmentModalLabel');

        document.querySelectorAll('.js-view-attachment').forEach((button) => {
            button.addEventListener('click', () => {
                title.textContent = button.dataset.previewName;

                if (button.dataset.previewType === 'image') {
                    frame.classList.add('d-none');
                    frame.removeAttribute('src');
                    image.src = button.dataset.previewUrl;
                    image.alt = `Vista previa de ${button.dataset.previewName}`;
                    image.classList.remove('d-none');
                    return;
                }

                image.classList.add('d-none');
                image.removeAttribute('src');
                image.removeAttribute('alt');
                frame.src = button.dataset.previewUrl;
                frame.classList.remove('d-none');
            });
        });

        modal.addEventListener('hidden.bs.modal', () => {
            frame.classList.add('d-none');
            frame.removeAttribute('src');
            image.classList.add('d-none');
            image.removeAttribute('src');
            image.removeAttribute('alt');
            title.textContent = 'Vista previa del archivo';
        });
    });
</script>
@endpush
@endsection
