<div class="modal fade user-modal" id="{{ $id }}" tabindex="-1">
    @php
        $defaultRows = $e ? $e->reagents->map(fn ($reagent) => [
            'reagent_id' => (string) $reagent->id,
            'nombre' => '',
            'cantidad_estimada' => $reagent->pivot->cantidad_estimada,
            'tipo_contraste' => $reagent->pivot->tipo_contraste ?? 'Ambos',
        ])->values()->all() : collect($globalReagents)->flatten(1)->values()->all();
        $savedRows = collect(old('reagents', $defaultRows));
        $rowsByContrast = collect($contrastes)->mapWithKeys(fn ($contrast) => [
            $contrast => $savedRows->where('tipo_contraste', $contrast)->values()->all(),
        ]);
    @endphp
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" action="{{ $action }}" class="modal-content"
              x-data="examConsumables(@js($rowsByContrast), @js(old('tipo_contraste', $e?->tipo_contraste ?? 'Ambos')))">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif
            <input type="hidden" name="tipo_contraste" :value="examContrast">
            <input type="hidden" name="reagents_payload" :value="payload()">
            <div class="modal-header text-white">
                <div>
                    <h5 class="modal-title">{{ $e ? 'Editar tomografía' : 'Registrar tomografía' }}</h5>
                    <div class="small opacity-75">Una sola tomografía puede tener insumos distintos según el uso de contraste.</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Nombre de la tomografía</label>
                        <input name="nombre_examen" class="form-control" required value="{{ old('nombre_examen', $e?->nombre_examen) }}" placeholder="Ej. TEM Abdomen">
                        <div class="form-text">Registra el nombre una sola vez; no agregues “con contraste” al nombre.</div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">¿Cómo se puede realizar?</label>
                        <select class="form-select" x-model="examContrast" required>
                            @foreach($contrastes as $contrast)
                                <option value="{{ $contrast }}">{{ $contrast }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Elige “Ambos” para separar las dos configuraciones sin duplicar la tomografía.</div>
                    </div>
                </div>

                <div class="alert alert-info border-0 d-flex gap-2 align-items-start" x-show="examContrast === 'Ambos'">
                    <strong>Dos configuraciones, un examen:</strong>
                    <span>los insumos de cada bloque se cargarán únicamente cuando la orden use esa modalidad.</span>
                </div>

                @if(! $e)
                    <div class="alert alert-success border-0 py-2">
                        Los insumos globales ya fueron precargados. Puedes ajustarlos únicamente para esta tomografía antes de guardar.
                    </div>
                @endif

                <div class="alert alert-warning border-0 d-flex gap-2 align-items-start" x-show="examContrast !== 'Ambos'" x-cloak>
                    <strong>Configuración protegida:</strong>
                    <span>los insumos guardados para la otra modalidad se conservarán aunque ahora no esté habilitada.</span>
                </div>

                @foreach(['Sin contraste', 'Con contraste', 'Ambos'] as $contrast)
                    @php
                        $isShared = $contrast === 'Ambos';
                        $tone = $contrast === 'Con contraste' ? 'primary' : ($isShared ? 'secondary' : 'success');
                    @endphp
                    <fieldset class="clinic-section-box p-3 mb-3"
                              x-show="examContrast === 'Ambos' || examContrast === '{{ $contrast }}' || '{{ $contrast }}' === 'Ambos'"
                              :disabled="examContrast !== 'Ambos' && examContrast !== '{{ $contrast }}' && '{{ $contrast }}' !== 'Ambos'"
                              x-cloak>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                            <div>
                                <h6 class="mb-1 fw-bold text-{{ $tone }}">
                                    {{ $isShared ? 'Insumos comunes' : 'Tomografía '.$contrast }}
                                </h6>
                                <div class="small text-clinic-muted">
                                    {{ $isShared ? 'Se usarán en cualquiera de las dos modalidades.' : 'Se usarán solamente cuando la orden sea '.strtolower($contrast).'.' }}
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-{{ $tone }}" @click="addRow('{{ $contrast }}')">+ Agregar insumo</button>
                        </div>

                        <div class="table-responsive" x-show="rows['{{ $contrast }}'].length">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Insumo existente</th><th>O crear uno nuevo</th><th style="width: 150px">Cantidad</th><th style="width: 55px"></th></tr></thead>
                                <tbody>
                                    <template x-for="row in rows['{{ $contrast }}']" :key="row.key">
                                        <tr>
                                            <td>
                                                <select class="form-select" :name="field(row, 'reagent_id')" x-model="row.reagent_id">
                                                    <option value="">Seleccionar insumo</option>
                                                    @foreach($reagents as $reagent)
                                                        <option value="{{ $reagent->id }}">{{ $reagent->nombre }} ({{ $reagent->unidad }})</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input class="form-control" :name="field(row, 'nombre')" x-model="row.nombre" placeholder="Nombre del nuevo insumo" :disabled="row.reagent_id !== ''"></td>
                                            <td>
                                                <input class="form-control" :name="field(row, 'cantidad_estimada')" x-model="row.cantidad_estimada" type="number" min="0.01" step="0.01" placeholder="0.00">
                                                <input type="hidden" :name="field(row, 'tipo_contraste')" value="{{ $contrast }}">
                                            </td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger" @click="removeRow('{{ $contrast }}', row.key)" aria-label="Quitar insumo">×</button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center small text-clinic-muted py-2" x-show="!rows['{{ $contrast }}'].length">Aún no hay insumos en esta modalidad.</div>
                    </fieldset>
                @endforeach

                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" name="activo" value="1" type="checkbox" id="active-{{ $id }}" @checked(old('activo', $e?->activo ?? true))>
                    <label class="form-check-label" for="active-{{ $id }}">Tomografía activa</label>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-clinic-primary">Guardar tomografía e insumos</button></div>
        </form>
    </div>
</div>
