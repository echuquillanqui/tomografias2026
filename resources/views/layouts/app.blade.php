<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Portal Tomografía') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- Estilos del Tema: Clínica de Tomografía Avanzada -->
    <style>
        :root {
            --clinic-dark-blue: #0b2545;  /* Azul oscuro profundo (confianza y seriedad) */
            --clinic-cyan: #139a8c;       /* Cyan/Turquesa clínico (tecnología médica) */
            --clinic-light-blue: #8da9c4; /* Azul de acento suave */
            --clinic-bg: #f4f7f6;         /* Fondo blanco clínico/limpio */
        }
        
        body {
            background-color: var(--clinic-bg);
            font-family: 'Nunito', sans-serif;
        }

        .navbar-clinic {
            background-color: #ffffff;
            border-bottom: 3px solid var(--clinic-cyan);
        }

        .navbar-clinic .navbar-brand {
            font-weight: 700;
            color: var(--clinic-dark-blue) !important;
        }

        .navbar-clinic .nav-link {
            color: var(--clinic-dark-blue) !important;
            transition: color 0.2s ease;
        }

        .navbar-clinic .nav-link:hover {
            color: var(--clinic-cyan) !important;
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- Navbar limpia de estilo hospitalario/clínico moderno -->
        <nav class="navbar navbar-expand-md navbar-clinic shadow-sm py-3">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    <!-- Icono simulado de escaneo/médico -->
                    <span class="me-2" style="color: var(--clinic-cyan); font-size: 1.4rem;">⦾</span>
                    {{ config('app.name', 'Clínica de Tomografía') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto"></ul>

                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link fw-semibold" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link fw-semibold" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle fw-semibold" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-5">
            @yield('content')
        </main>
    </div>
</body>
</html>