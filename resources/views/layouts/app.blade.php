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

        @auth
            @if(($layoutShouldShowFixedExpenseModal ?? false) && ($layoutPendingFixedExpenses ?? collect())->isNotEmpty())
                <div class="modal fade" id="monthlyFixedExpensesModal" tabindex="-1" aria-labelledby="monthlyFixedExpensesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-warning-subtle border-0">
                                <div>
                                    <div class="text-uppercase small fw-bold text-warning-emphasis">Fin de mes</div>
                                    <h5 class="modal-title fw-bold" id="monthlyFixedExpensesModalLabel">Ejecutar gastos fijos mensuales</h5>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3">Estos gastos fijos aún no se registran en el cuadre mensual de {{ $layoutFixedExpensePeriod }}. Al ejecutarlos se crearán como egresos con fecha de fin de mes.</p>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead><tr><th>Gasto fijo</th><th class="text-end">Monto</th></tr></thead>
                                        <tbody>
                                            @foreach($layoutPendingFixedExpenses as $fixedExpense)
                                                <tr><td class="fw-semibold">{{ $fixedExpense->descripcion }}</td><td class="text-end text-danger fw-bold">S/ {{ number_format($fixedExpense->monto, 2) }}</td></tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <a href="{{ route('cash-closings.index', ['tab' => 'fijos', 'period' => 'month']) }}" class="btn btn-outline-secondary">Revisar configuración</a>
                                <form method="POST" action="{{ route('cash-closings.fixed-expenses.execute') }}" class="m-0">
                                    @csrf
                                    <input type="hidden" name="period_date" value="{{ now()->toDateString() }}">
                                    <button class="btn btn-warning fw-bold">Ejecutar y llevar al cuadre mensual</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>document.addEventListener('DOMContentLoaded',()=>{@if(session('success'))Swal.fire({icon:'success',title:'Listo',text:@json(session('success')),timer:2200,showConfirmButton:false});@endif @if(session('error'))Swal.fire({icon:'error',title:'Atención',text:@json(session('error'))});@endif @if($errors->any())Swal.fire({icon:'error',title:'Revisa el formulario',text:'Hay campos pendientes o inválidos.'});@endif});</script>

    @auth
        @if(($layoutShouldShowFixedExpenseModal ?? false) && ($layoutPendingFixedExpenses ?? collect())->isNotEmpty())
            <script>document.addEventListener('DOMContentLoaded',()=>{new bootstrap.Modal(document.getElementById('monthlyFixedExpensesModal')).show();});</script>
        @endif
    @endauth
    @stack('scripts')
</body>
</html>