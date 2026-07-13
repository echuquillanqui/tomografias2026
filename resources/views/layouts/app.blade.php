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
    @stack('styles')

</head>
<body>
    <div id="app">
        @include('layouts.navigation')

        <main class="py-5">
            @yield('content')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>document.addEventListener('DOMContentLoaded',()=>{@if(session('success'))Swal.fire({icon:'success',title:'Listo',text:@json(session('success')),timer:2200,showConfirmButton:false});@endif @if(session('error'))Swal.fire({icon:'error',title:'Atención',text:@json(session('error'))});@endif @if($errors->any())Swal.fire({icon:'error',title:'Revisa el formulario',text:'Hay campos pendientes o inválidos.'});@endif});</script>
    @stack('scripts')
</body>
</html>