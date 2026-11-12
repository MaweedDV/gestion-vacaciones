<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0048b0">
    <title>@yield('title', 'Panel') | Gestión de Vacaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/gestion.css') }}?v=11" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    @stack('styles')
</head>
<body class="{{ auth()->check() ? 'app-body' : 'login-body' }}">
@auth
    @php
        $u = auth()->user();
        $items = [
            ['ruta'=>'dashboard.index','patron'=>'dashboard*','icono'=>'grid-1x2','texto'=>'Resumen','roles'=>['administrador']],
            ['ruta'=>'notificaciones.index','patron'=>'notificaciones*','icono'=>'bell','texto'=>'Notificaciones','roles'=>['administrador','jefatura','aprobador_general','funcionario']],
            ['ruta'=>'mi-panel.index','patron'=>'mi-panel*','icono'=>'person-vcard','texto'=>'Mi panel','roles'=>['funcionario']],
            ['ruta'=>'calendario.index','patron'=>'calendario*','icono'=>'calendar3','texto'=>'Calendario','roles'=>['administrador']],
            ['ruta'=>'vacaciones.index','patron'=>'vacaciones*','icono'=>'calendar2-check','texto'=>'Vacaciones','roles'=>['administrador','jefatura','funcionario','aprobador_general']],
            ['ruta'=>'reemplazos.index','patron'=>'reemplazos*','icono'=>'arrow-left-right','texto'=>'Reemplazos','roles'=>['administrador']],
            ['ruta'=>'funcionarios.index','patron'=>'funcionarios*','icono'=>'people','texto'=>'Funcionarios','roles'=>['administrador']],
            ['ruta'=>'departamentos.index','patron'=>'departamentos*','icono'=>'diagram-3','texto'=>'Departamentos','roles'=>['administrador']],
            ['ruta'=>'saldos.index','patron'=>'saldos*','icono'=>'wallet2','texto'=>'Saldos','roles'=>['administrador']],
            ['ruta'=>'feriados.index','patron'=>'feriados*','icono'=>'calendar-event','texto'=>'Feriados','roles'=>['administrador']],
            ['ruta'=>'auditorias.index','patron'=>'auditorias*','icono'=>'clock-history','texto'=>'Historial','roles'=>['administrador']],
            ['ruta'=>'usuarios.index','patron'=>'usuarios*','icono'=>'person-gear','texto'=>'Usuarios y roles','roles'=>['administrador']],
        ];
    @endphp
    <div class="app-shell">
        <aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="menuLateral" aria-labelledby="menuTitulo">
            <div class="offcanvas-header d-lg-none"><h2 class="offcanvas-title fs-5" id="menuTitulo">Navegación</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#menuLateral" aria-label="Cerrar menú"></button></div>
            <div class="offcanvas-body sidebar-content">
                <a class="app-brand" href="{{ route($u->tieneRol('administrador') ? 'dashboard.index' : ($u->tieneRol('funcionario') ? 'mi-panel.index' : 'vacaciones.index')) }}" aria-label="Ir al panel principal"><span class="brand-symbol brand-logo-box"><img src="{{ asset('images/logo-institucion.png') }}" alt="Escudo institucional" class="brand-logo"></span><span><strong>Gestión de personal</strong><small>Vacaciones y reemplazos</small></span></a>
                <div class="menu-group-label">MENÚ PRINCIPAL</div>
                <nav aria-label="Menú principal" class="nav flex-column app-nav">
                @foreach($items as $item)
                        @if(in_array($u->rol, $item['roles'], true))
                        <a href="{{ route($item['ruta']) }}" class="nav-link {{ request()->is($item['patron']) ? 'active' : '' }}" @if(request()->is($item['patron'])) aria-current="page" @endif><i class="bi bi-{{ $item['icono'] }}" aria-hidden="true"></i><span>{{ $item['texto'] }}</span></a>
                        @endif
                    @endforeach
                </nav>
                <div class="sidebar-bottom"><div class="sidebar-user-icon"><i class="bi bi-person-circle"></i></div><div class="sidebar-user-text"><strong>{{ auth()->user()->name }}</strong><small>{{ ['administrador'=>'Administración','jefatura'=>'Jefatura','aprobador_general'=>'Aprobador general','funcionario'=>'Funcionario'][$u->rol] ?? 'Usuario' }}</small></div></div>
            </div>
        </aside>
        <div class="app-main">
            <header class="app-topbar"><div class="d-flex align-items-center gap-3"><button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral" aria-label="Abrir menú"><i class="bi bi-list fs-4"></i></button><div><span class="topbar-eyebrow">Gestión institucional de vacaciones</span><div class="topbar-title">@yield('title', 'Panel de control')</div></div></div><div class="d-flex align-items-center gap-3"><a href="{{ route('notificaciones.index') }}" class="notification-trigger" aria-label="Mis notificaciones"><i class="bi bi-bell"></i>@php($noLeidas = $u->unreadNotifications()->count())@if($noLeidas > 0)<span class="notification-count">{{ $noLeidas > 99 ? '99+' : $noLeidas }}</span>@endif</a><span class="topbar-date d-none d-md-inline"><i class="bi bi-calendar3 me-1"></i>{{ now()->locale('es')->translatedFormat('d M Y') }}</span><form method="POST" action="{{ route('logout') }}" class="m-0">@csrf<button class="btn btn-logout" type="submit"><i class="bi bi-box-arrow-right me-1"></i> Salir</button></form></div></header>
            <main class="app-content">
                @if(session('ok'))<div class="alert alert-success" role="status"><i class="bi bi-check-circle me-2"></i>{{ session('ok') }}</div>@endif
                @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Revisa los siguientes datos:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @yield('content')
            </main>
            <footer class="app-footer">Gestión de Vacaciones · Uso interno institucional</footer>
        </div>
    </div>
@else
    <main class="login-container">
        <div class="login-intro"><span class="login-emblem login-logo-box"><img src="{{ asset('images/logo-institucion.png') }}" alt="Escudo institucional" class="login-logo"></span><h1>Gestión de vacaciones</h1><p>Organización de ausencias y continuidad del servicio.</p></div>
        @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
@endauth
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
