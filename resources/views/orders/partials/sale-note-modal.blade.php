<div class="modal fade" id="saleNoteModal" tabindex="-1" aria-labelledby="saleNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="saleNoteModalLabel">Nota de venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <iframe id="saleNotePreview" title="Vista previa de la nota de venta" class="w-100 border-0" style="height: 72vh"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-clinic-primary" id="printSaleNote">Imprimir</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('saleNoteModal');
    const preview = document.getElementById('saleNotePreview');

    modal.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        preview.src = trigger.getAttribute('data-sale-note-url');
        document.getElementById('saleNoteModalLabel').textContent = `Nota de venta ${trigger.getAttribute('data-sale-note-number')}`;
    });
    modal.addEventListener('hidden.bs.modal', () => preview.removeAttribute('src'));
    document.getElementById('printSaleNote').addEventListener('click', () => preview.contentWindow.print());
});
</script>
@endpush
