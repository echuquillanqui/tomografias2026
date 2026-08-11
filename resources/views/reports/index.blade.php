@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <div class="clinic-eyebrow mb-2">Informes</div>
                <h1 class="display-6 fw-bold">Atenciones por informar</h1>
                <p class="mb-0 opacity-75">Listado de atenciones generadas desde las órdenes para completar y descargar reportes.</p>
            </div>
        </div>
    </section>

    <form class="card clinic-card p-3 mb-4" method="GET" action="{{ route('reports.index') }}">
        <div class="input-group">
            <input name="search" class="form-control" value="{{ $search }}" placeholder="Buscar por orden, DNI o paciente">
            <button class="btn btn-clinic-primary">Buscar</button>
        </div>
    </form>

    <div class="card clinic-card">
        <div class="card-body p-0">
            <table class="table table-clinic mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Paciente</th>
                        <th>Convenio</th>
                        <th>Fecha</th>
                        <th>Exámenes</th>
                        <th>Médico firmante</th>
                        <th>Estado</th>
                        <th>Archivos subidos</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="fw-bold">{{ $order->codigo_orden ?? 'Orden #'.$order->id }}</td>
                            <td>{{ $order->patient->nombres }} {{ $order->patient->apellidos }}<br><small class="text-muted">{{ $order->patient->dni }}</small></td>
                            <td>{{ $order->agreement->nombre_institucion }}</td>
                            <td>{{ $order->fecha_orden->format('d/m/Y H:i') }}</td>
                            <td>{{ $order->order_exams_count }}</td>
                            <td>{{ $order->report?->medicoFirmante?->nombre_completo ?? $order->medicoInforme?->nombre_completo ?? '—' }}</td>
                            <td><span class="badge badge-role">{{ $order->estado }}</span></td>
                            <td>
                                @forelse($order->report?->attachments ?? [] as $attachment)
                                    <a class="btn btn-sm btn-outline-success mb-1" href="{{ route('reports.attachments.view', [$order, $attachment]) }}" target="_blank" title="Ver {{ $attachment->original_name }}">
                                        {{ $attachment->original_name }}
                                    </a>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('reports.edit', $order) }}">Rellenar</a>
                                <button type="button" class="btn btn-sm {{ $order->report?->attachments->isNotEmpty() ? 'btn-success' : 'btn-outline-secondary' }}" data-bs-toggle="modal" data-bs-target="#reportFilesModal{{ $order->id }}">PDF</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-5">Sin atenciones generadas por órdenes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $orders->links() }}</div>
    </div>

    @foreach($orders as $order)
        @php($attachments = $order->report?->attachments ?? collect())
        <div class="modal fade report-files-modal" id="reportFilesModal{{ $order->id }}" tabindex="-1" aria-labelledby="reportFilesModalLabel{{ $order->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <div class="min-w-0">
                            <h5 class="modal-title fw-bold" id="reportFilesModalLabel{{ $order->id }}">Archivos del informe</h5>
                            <div class="small text-muted text-truncate">{{ $order->codigo_orden ?? 'Orden #'.$order->id }} · {{ $order->patient->nombres }} {{ $order->patient->apellidos }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body p-0">
                        @if($attachments->isNotEmpty())
                            <div class="report-files-layout">
                                <aside class="report-files-list p-3" aria-label="Archivos adjuntos">
                                    <div class="small fw-bold text-uppercase text-muted mb-2">Adjuntos ({{ $attachments->count() }})</div>
                                    <div class="list-group list-group-flush gap-2">
                                        @foreach($attachments as $attachment)
                                            <button type="button" class="list-group-item list-group-item-action rounded border js-report-file {{ $loop->first ? 'active' : '' }}"
                                                data-preview-url="{{ route('reports.attachments.view', [$order, $attachment]) }}"
                                                data-preview-name="{{ $attachment->original_name }}"
                                                data-preview-type="{{ str_starts_with($attachment->mime_type, 'image/') ? 'image' : 'pdf' }}">
                                                <span class="d-block fw-semibold text-break">{{ $attachment->original_name }}</span>
                                                <small class="report-file-meta">{{ str_starts_with($attachment->mime_type, 'image/') ? 'Imagen' : 'Documento PDF' }} · {{ number_format($attachment->stored_size / 1024, 1) }} KB</small>
                                            </button>
                                        @endforeach
                                    </div>
                                </aside>
                                <section class="report-file-preview bg-light" aria-live="polite">
                                    @php($firstAttachment = $attachments->first())
                                    <div class="report-file-preview-heading">
                                        <strong class="js-preview-title text-truncate">{{ $firstAttachment->original_name }}</strong>
                                        <a class="btn btn-sm btn-outline-primary js-preview-open" href="{{ route('reports.attachments.view', [$order, $firstAttachment]) }}" target="_blank" rel="noopener">Abrir en otra pestaña</a>
                                    </div>
                                    <div class="report-file-preview-content">
                                        <iframe class="report-file-preview-frame {{ str_starts_with($firstAttachment->mime_type, 'image/') ? 'd-none' : '' }}" src="{{ str_starts_with($firstAttachment->mime_type, 'image/') ? '' : route('reports.attachments.view', [$order, $firstAttachment]) }}" title="Vista previa de {{ $firstAttachment->original_name }}"></iframe>
                                        <img class="report-file-preview-image {{ str_starts_with($firstAttachment->mime_type, 'image/') ? '' : 'd-none' }}" src="{{ str_starts_with($firstAttachment->mime_type, 'image/') ? route('reports.attachments.view', [$order, $firstAttachment]) : '' }}" alt="Vista previa de {{ $firstAttachment->original_name }}">
                                    </div>
                                </section>
                            </div>
                        @else
                            <div class="text-center py-5 px-3">
                                <div class="fw-bold mb-1">No hay archivos adjuntos</div>
                                <p class="text-muted mb-3">Agrega un PDF o una imagen desde la edición del informe.</p>
                                <a class="btn btn-clinic-primary" href="{{ route('reports.edit', $order) }}">Rellenar informe</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<style>
    .report-files-layout { display: grid; grid-template-columns: minmax(220px, 28%) minmax(0, 1fr); height: min(75vh, 760px); }
    .report-files-list { border-right: 1px solid #dee2e6; overflow-y: auto; }
    .report-files-list .list-group-item { color: #334155; }
    .report-files-list .list-group-item.active { background: #e8f7f4; border-color: #14b8a6 !important; color: #0f766e; }
    .report-file-meta { color: #64748b; }
    .report-files-list .active .report-file-meta { color: #0f766e; }
    .report-file-preview { display: flex; flex-direction: column; min-width: 0; }
    .report-file-preview-heading { align-items: center; background: #fff; border-bottom: 1px solid #dee2e6; display: flex; gap: 1rem; justify-content: space-between; padding: .75rem 1rem; }
    .report-file-preview-content { align-items: center; display: flex; flex: 1; justify-content: center; min-height: 0; overflow: hidden; padding: 1rem; }
    .report-file-preview-frame { background: #fff; border: 0; height: 100%; width: 100%; }
    .report-file-preview-image { height: auto; max-height: 100%; max-width: 100%; object-fit: contain; width: auto; }
    @media (max-width: 767.98px) {
        .report-files-layout { grid-template-columns: 1fr; grid-template-rows: auto minmax(420px, 1fr); height: auto; }
        .report-files-list { border-bottom: 1px solid #dee2e6; border-right: 0; max-height: 180px; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.report-files-modal').forEach((modal) => {
            const frame = modal.querySelector('.report-file-preview-frame');
            const image = modal.querySelector('.report-file-preview-image');
            const title = modal.querySelector('.js-preview-title');
            const openLink = modal.querySelector('.js-preview-open');

            modal.querySelectorAll('.js-report-file').forEach((button) => {
                button.addEventListener('click', () => {
                    modal.querySelectorAll('.js-report-file').forEach((item) => item.classList.remove('active'));
                    button.classList.add('active');
                    title.textContent = button.dataset.previewName;
                    openLink.href = button.dataset.previewUrl;

                    if (button.dataset.previewType === 'image') {
                        frame.classList.add('d-none');
                        frame.removeAttribute('src');
                        image.src = button.dataset.previewUrl;
                        image.alt = `Vista previa de ${button.dataset.previewName}`;
                        image.classList.remove('d-none');
                    } else {
                        image.classList.add('d-none');
                        image.removeAttribute('src');
                        frame.src = button.dataset.previewUrl;
                        frame.title = `Vista previa de ${button.dataset.previewName}`;
                        frame.classList.remove('d-none');
                    }
                });
            });
        });
    });
</script>
@endpush
@endsection
