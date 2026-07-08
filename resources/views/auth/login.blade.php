@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <!-- Ampliamos a col-xl-10 para dar suficiente espacio al diseño 50/50 -->
        <div class="col-xl-10">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="min-height: 550px;">
                <div class="row g-0" style="min-height: 550px;">
                    
                    <!-- LADO IZQUIERDO: IMAGEN MÉDICA / TECNOLÓGICA (50%) -->
                    <!-- Nota: He colocado un degradado clínico azul/cyan de fondo por si aún no cargas la imagen -->
                    <div class="col-md-6 d-none d-md-flex align-items-center position-relative text-white p-5" 
                         style="background: linear-gradient(135deg, rgba(11, 37, 69, 0.95), rgba(19, 154, 140, 0.85)), 
                                     url('{{ asset('images/tomografo-login.jpg') }}') center/cover no-repeat;">
                        
                        <div class="position-relative z-index-2 w-100">
                            <!-- Tag decorativo superior -->
                            <span class="badge rounded-pill px-3 py-2 mb-3" style="background-color: rgba(255,255,255,0.15); backdrop-filter: blur(5px);">
                                Portal de Diagnóstico
                            </span>
                            <h2 class="fw-bold display-6 lh-sm mb-3">Imágenes de Alta Definición y Precisión Clínica</h2>
                            <p class="opacity-75 lead fs-6">Accede de manera segura al sistema de visualización de tomografías, gestión de pacientes y reportes radiológicos.</p>
                            
                            <!-- Elemento visual técnico simulando un escaneo -->
                            <div class="mt-5 pt-4 border-top border-secondary border-opacity-20 d-flex align-items-center">
                                <div class="spinner-grow spinner-grow-sm text-info me-3" role="status"></div>
                                <small class="text-uppercase tracking-wider opacity-50" style="font-size: 0.75rem; letter-spacing: 1px;">
                                    Sistema DICOM Activo v3.0
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- LADO DERECHO: FORMULARIO DE LOGIN PROFESIONAL (50%) -->
                    <div class="col-md-6 bg-white d-flex align-items-center p-4 p-lg-5">
                        <div class="w-100">
                            <!-- Cabecera del formulario -->
                            <div class="mb-4">
                                <h3 class="fw-bold" style="color: var(--clinic-dark-blue);">
                                    {{ __('Iniciar Sesión') }}
                                </h3>
                                <p class="text-muted small">Ingresa tus credenciales médicas autorizadas</p>
                            </div>

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <!-- Input: Correo Electrónico o usuario -->
                                <div class="mb-3">
                                    <label for="login" class="form-label small fw-semibold" style="color: var(--clinic-dark-blue);">
                                        {{ __('Correo electrónico o usuario') }}
                                    </label>
                                    <div class="input-group">
                                        <input id="login" type="text" class="form-control bg-light border-0 py-2 px-3 @error('login') is-invalid @enderror" name="login" value="{{ old('login') }}" required autocomplete="username" autofocus placeholder="usuario o nombre@clinica.com">
                                        @error('login')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Input: Contraseña -->
                                <div class="mb-3">
                                    <label for="password" class="form-label small fw-semibold" style="color: var(--clinic-dark-blue);">
                                        {{ __('Password') }}
                                    </label>
                                    <input id="password" type="password" class="form-control bg-light border-0 py-2 px-3 @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Opciones extra (Recordarme y Olvidé mi clave) -->
                                <div class="mb-4 d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label small text-secondary" for="remember">
                                            {{ __('Remember Me') }}
                                        </label>
                                    </div>

                                    @if (Route::has('password.request'))
                                        <a class="text-decoration-none small fw-semibold" href="{{ route('password.request') }}" style="color: var(--clinic-cyan);">
                                            {{ __('Forgot Your Password?') }}
                                        </a>
                                    @endif
                                </div>

                                <!-- Botón de acción con estilo corporativo -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn text-white fw-bold py-2 shadow-sm" style="background-color: var(--clinic-cyan); border-radius: 6px;">
                                        Entrar al Sistema →
                                    </button>
                                </div>
                            </form>

                            <!-- Pie de página informativo -->
                            <div class="mt-5 text-center">
                                <p class="text-muted" style="font-size: 0.75rem;">
                                    Uso exclusivo para personal médico y administrativo. 
                                    <br>© {{ date('Y') }} {{ config('app.name') }}.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection