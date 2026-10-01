@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="clinic-eyebrow mb-2">Cuenta personal</div>
        <h1 class="display-6 fw-bold mb-2">Mi perfil</h1>
        <p class="mb-0 opacity-75">Actualiza tus datos de acceso y protege tu cuenta.</p>
    </section>

    <div class="row g-4">
        <div class="col-lg-7">
            <form class="card clinic-card p-4 h-100" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')
                <h4 class="fw-bold mb-1">Información personal</h4>
                <p class="text-muted mb-4">Tu rol es <strong>{{ $user->rol }}</strong> y solo puede cambiarlo un administrador.</p>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold" for="nombre_completo">Nombre completo</label>
                        <input id="nombre_completo" name="nombre_completo" class="form-control @error('nombre_completo') is-invalid @enderror" value="{{ old('nombre_completo', $user->nombre_completo) }}" required>
                        @error('nombre_completo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold" for="username">Usuario</label>
                        <input id="username" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $user->username) }}" required>
                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold" for="email">Correo electrónico</label>
                        <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="text-end mt-4"><button class="btn btn-clinic-primary px-4">Guardar perfil</button></div>
            </form>
        </div>

        <div class="col-lg-5">
            <form class="card clinic-card p-4 h-100" method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                @method('PUT')
                <h4 class="fw-bold mb-1">Cambiar contraseña</h4>
                <p class="text-muted mb-4">Usa al menos 8 caracteres.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold" for="current_password">Contraseña actual</label>
                    <input id="current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" for="password">Nueva contraseña</label>
                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" for="password_confirmation">Confirmar contraseña</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                </div>
                <div class="text-end mt-4"><button class="btn btn-outline-primary px-4">Actualizar contraseña</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
