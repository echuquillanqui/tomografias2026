<div class="modal fade user-modal" id="{{ $id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ $action }}" class="modal-content">
            @csrf
            @if($method === 'PUT')
                @method('PUT')
            @endif
            <div class="modal-header text-white">
                <h5 class="modal-title">Examen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-7">
                    <label class="form-label">Nombre</label>
                    <input name="nombre_examen" class="form-control" required value="{{ old('nombre_examen', $e?->nombre_examen) }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Tipo contraste</label>
                    <select name="tipo_contraste" class="form-select">
                        @foreach($contrastes as $c)
                            <option @selected(old('tipo_contraste', $e?->tipo_contraste) === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <div class="clinic-section-box p-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                            <div>
                                <strong>Reactivos estimados</strong>
                                <div class="small text-clinic-muted">Selecciona un reactivo existente o escribe uno nuevo aquí; se creará desde este examen.</div>
                            </div>
                            <span class="badge badge-role align-self-md-start">Origen: Exámenes</span>
                        </div>
                        @for($i = 0; $i < 5; $i++)
                            @php($current = $e?->reagents[$i] ?? null)
                            <div class="row g-2 mt-1 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small mb-1">Reactivo existente</label>
                                    <select name="reagents[{{ $i }}][reagent_id]" class="form-select">
                                        <option value="">Seleccionar reactivo</option>
                                        @foreach($reagents as $r)
                                            <option value="{{ $r->id }}" @selected($current?->id === $r->id)>{{ $r->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">O nuevo reactivo</label>
                                    <input name="reagents[{{ $i }}][nombre]" class="form-control" placeholder="Nombre del reactivo" value="{{ old("reagents.$i.nombre") }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Cantidad</label>
                                    <input name="reagents[{{ $i }}][cantidad_estimada]" class="form-control" type="number" step="0.01" placeholder="Cantidad" value="{{ old("reagents.$i.cantidad_estimada", $current?->pivot->cantidad_estimada) }}">
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
                <div class="col-12 form-check form-switch ms-2">
                    <input class="form-check-input" name="activo" value="1" type="checkbox" @checked(old('activo', $e?->activo ?? true))>
                    <label class="form-check-label">Activo</label>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-clinic-primary">Guardar</button></div>
        </form>
    </div>
</div>
