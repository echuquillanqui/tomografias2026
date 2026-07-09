        <nav class="navbar navbar-expand-md navbar-clinic shadow-sm py-3">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    <span class="brand-mark">⦾</span>
                    {{ config('app.name', 'Clínica de Tomografía') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link fw-semibold {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Usuarios</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fw-semibold {{ request()->routeIs('patients.*') ? 'active' : '' }}" href="{{ route('patients.index') }}">Pacientes</a>
                            </li>
                            <li class="nav-item"><a class="nav-link fw-semibold {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">Órdenes</a></li>
                            <li class="nav-item"><a class="nav-link fw-semibold {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Informes</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle fw-semibold {{ request()->routeIs('agreements.*') || request()->routeIs('exams.*') || request()->routeIs('reagents.*') || request()->routeIs('agreement-prices.*') || request()->routeIs('stock-movements.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Catálogos</a>
                                <div class="dropdown-menu shadow border-0">
                                    <a class="dropdown-item" href="{{ route('agreements.index') }}">Convenios</a>
                                    <a class="dropdown-item" href="{{ route('exams.index') }}">Exámenes</a>
                                    <a class="dropdown-item" href="{{ route('agreement-prices.index') }}">Precios pactados</a>
                                    <a class="dropdown-item" href="{{ route('reagents.index') }}">Reactivos</a>
                                    <a class="dropdown-item" href="{{ route('stock-movements.index') }}">Movimientos stock</a>
                                </div>
                            </li>
                        @endauth
                    </ul>

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
                                    {{ Auth::user()->username }}
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
