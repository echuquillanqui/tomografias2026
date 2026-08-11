@php
    $searchId = $id ?? 'catalog-search';
    $remainingQuery = request()->except(['search', 'page']);
@endphp

<div class="card-header clinic-toolbar p-3">
    <form method="GET" action="{{ url()->current() }}" class="catalog-reactive-search" data-catalog-search>
        @foreach($remainingQuery as $key => $value)
            @if(is_scalar($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <label class="form-label fw-semibold mb-2" for="{{ $searchId }}">Buscar en el catálogo</label>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <div class="clinic-search flex-grow-1">
                <span class="clinic-search-icon" aria-hidden="true">⌕</span>
                <input id="{{ $searchId }}" class="form-control" type="search" name="search"
                    value="{{ $search }}" placeholder="{{ $placeholder ?? 'Escribe para filtrar los registros...' }}"
                    autocomplete="off" data-catalog-search-input>
            </div>
            <button class="btn btn-outline-primary" type="submit">Buscar</button>
            <a class="btn btn-outline-secondary {{ $search === '' ? 'd-none' : '' }}"
                href="{{ url()->current() }}{{ count($remainingQuery) ? '?'.http_build_query($remainingQuery) : '' }}">
                Limpiar
            </a>
        </div>
        <small class="text-muted d-block mt-2" data-catalog-search-status aria-live="polite">
            El listado se filtra automáticamente mientras escribes.
        </small>
    </form>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-catalog-search]').forEach((form) => {
                    const input = form.querySelector('[data-catalog-search-input]');
                    const status = form.querySelector('[data-catalog-search-status]');
                    let timer;

                    input.addEventListener('input', () => {
                        window.clearTimeout(timer);
                        status.textContent = 'Preparando filtro...';
                        timer = window.setTimeout(() => {
                            status.textContent = 'Actualizando resultados...';
                            form.requestSubmit();
                        }, 450);
                    });

                    form.addEventListener('submit', () => {
                        status.textContent = 'Actualizando resultados...';
                    });
                });
            });
        </script>
    @endpush
@endonce
