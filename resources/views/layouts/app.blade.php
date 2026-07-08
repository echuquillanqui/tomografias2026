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

        .navbar-clinic .nav-link:hover,
        .navbar-clinic .nav-link.active {
            color: var(--clinic-cyan) !important;
        }
    </style>
</head>
<body>
    <div id="app">
        @include('layouts.navigation')

        <main class="py-5">
            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>
</html>