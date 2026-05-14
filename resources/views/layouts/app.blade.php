@php
    $sessionUser = session('auth_user', []);
    $role = $sessionUser['role'] ?? '';
    $initial = mb_substr($sessionUser['name'] ?? 'S', 0, 1);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Matriz SEDES')</title>
    <link rel="stylesheet" href="{{ asset('css/sedes.css') }}">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <img src="{{ asset('assets/img/LOGO_circulo_SEDES.png') }}" alt="SEDES">
            <div>
                <h2>SEDES Potosí</h2>
                <span>Matriz de Seguimiento</span>
            </div>
        </div>

        <nav class="nav">
            @if($role === 'admin')
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Inicio</a>
                <a class="{{ request()->routeIs('admin.records.*') ? 'active' : '' }}" href="{{ route('admin.records.index') }}">Crear Registro</a>
                <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Gestionar Usuarios</a>
                <a class="{{ request()->routeIs('admin.matrix.*') ? 'active' : '' }}" href="{{ route('admin.matrix.index') }}">Ver Matriz de Seguimiento</a>
                <a href="{{ route('admin.matrix.index', 'cumplidos') }}">Cumplidos</a>
                <a href="{{ route('admin.matrix.index', 'pendientes') }}">Pendientes</a>
                <a href="{{ route('admin.matrix.index', 'no-cumplidos') }}">No Cumplidos</a>
                <a class="{{ request()->routeIs('admin.special-days.*') ? 'active' : '' }}" href="{{ route('admin.special-days.index') }}">Gestionar Días Especiales</a>
            @elseif($role === 'tecnico')
                <a class="{{ request()->routeIs('tecnico.dashboard') ? 'active' : '' }}" href="{{ route('tecnico.dashboard') }}">Inicio</a>
                <a class="{{ request()->routeIs('tecnico.tasks*') ? 'active' : '' }}" href="{{ route('tecnico.tasks') }}">Mis Tareas</a>
                <a class="{{ request()->routeIs('tecnico.matrix') ? 'active' : '' }}" href="{{ route('tecnico.matrix') }}">Matriz de Seguimiento</a>
                <a class="{{ request()->routeIs('tecnico.users') ? 'active' : '' }}" href="{{ route('tecnico.users') }}">Ver Usuarios</a>
                <a class="{{ request()->routeIs('tecnico.profile') ? 'active' : '' }}" href="{{ route('tecnico.profile') }}">Mi Perfil</a>
            @else
                <a class="{{ request()->routeIs('user.dashboard') ? 'active' : '' }}" href="{{ route('user.dashboard') }}">Página Principal</a>
                <a class="{{ request()->routeIs('user.tasks*') ? 'active' : '' }}" href="{{ route('user.tasks') }}">Ver Mis Tareas</a>
                <a class="{{ request()->routeIs('user.profile') ? 'active' : '' }}" href="{{ route('user.profile') }}">Mi Perfil</a>
            @endif
            <form class="logout-form" action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="logout-btn" type="submit">Cerrar Sesión</button>
            </form>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="page-title">
                <h1>@yield('page_title', 'Sistema de Seguimiento')</h1>
                <p>@yield('page_subtitle', 'Área de Proyectos y Planificación')</p>
            </div>
            <div class="user-pill">
                <div class="avatar">{{ $initial }}</div>
                <div>
                    <strong>{{ $sessionUser['name'] ?? 'Usuario' }}</strong><br>
                    <span class="muted">{{ $sessionUser['role_label'] ?? 'Rol' }}</span>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">Revise los datos marcados antes de continuar.</div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
