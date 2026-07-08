@php
    $isEdit = filled($user);
@endphp

<div class="modal fade user-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content border-0 shadow" method="POST" action="{{ $action }}">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif
            <div class="modal-header text-white" style="background-color: var(--clinic-dark-blue);">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $user->username ?? '') }}" required>
                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" name="nombre_completo" class="form-control @error('nombre_completo') is-invalid @enderror" value="{{ old('nombre_completo', $user->nombre_completo ?? '') }}" required>
                        @error('nombre_completo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rol</label>
                        <select name="rol" class="form-select @error('rol') is-invalid @enderror" data-role-select required>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('rol', $user->rol ?? 'Recepción') === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                        @error('rol') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contraseña {{ $isEdit ? '(dejar en blanco para no cambiar)' : '' }}</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ $isEdit ? '' : 'required' }}>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control" {{ $isEdit ? '' : 'required' }}>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="activo" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="activo{{ $modalId }}" name="activo" value="1" @checked(old('activo', $user->activo ?? true))>
                            <label class="form-check-label" for="activo{{ $modalId }}">Usuario activo</label>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2 p-3 rounded d-none" style="background-color: var(--clinic-bg);" data-medical-fields>
                    <div class="col-12">
                        <h6 class="fw-bold mb-0" style="color: var(--clinic-dark-blue);">Datos del médico</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo de médico</label>
                        <select name="tipo_medico" class="form-select @error('tipo_medico') is-invalid @enderror">
                            <option value="">Seleccione...</option>
                            @foreach ($tiposMedico as $tipo)
                                <option value="{{ $tipo }}" @selected(old('tipo_medico', $user->tipo_medico ?? '') === $tipo)>{{ $tipo }}</option>
                            @endforeach
                        </select>
                        @error('tipo_medico') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Comisión (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="comision_porcentaje" class="form-control @error('comision_porcentaje') is-invalid @enderror" value="{{ old('comision_porcentaje', $user->comision_porcentaje ?? '') }}">
                        @error('comision_porcentaje') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CMP</label>
                        <input type="text" name="cmp" class="form-control @error('cmp') is-invalid @enderror" value="{{ old('cmp', $user->cmp ?? '') }}">
                        @error('cmp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">RNE</label>
                        <input type="text" name="rne" class="form-control @error('rne') is-invalid @enderror" value="{{ old('rne', $user->rne ?? '') }}">
                        @error('rne') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn text-white" style="background-color: var(--clinic-cyan);">Guardar</button>
            </div>
        </form>
    </div>
</div>
