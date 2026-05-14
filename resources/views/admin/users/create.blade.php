@extends('layouts.app')
@section('title', 'Nuevo Usuario | Matriz SEDES')
@section('page_title', 'Crear Nuevo Usuario')
@section('page_subtitle', 'Registro institucional de acceso al sistema')
@section('content')
<div class="card form-card">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group"><label class="form-label">Nombre completo</label><input class="form-control" name="name" value="{{ old('name') }}" required></div>
            <div class="form-group"><label class="form-label">CI</label><input class="form-control" name="ci" value="{{ old('ci') }}"></div>
            <div class="form-group"><label class="form-label">Cargo</label><input class="form-control" name="cargo" value="{{ old('cargo') }}"></div>
            <div class="form-group"><label class="form-label">Fecha de ingreso</label><input class="form-control" type="date" name="admission_date" value="{{ old('admission_date', now()->toDateString()) }}"></div>
            <div class="form-group"><label class="form-label">Usuario ID único</label><input class="form-control" name="username" value="{{ old('username') }}" placeholder="Ej. TEC002" required></div>
            <div class="form-group"><label class="form-label">Contraseña</label><input class="form-control" type="password" name="password" required></div>
            <div class="form-group"><label class="form-label">Rol</label><select class="form-select" name="role" required><option value="user">User</option><option value="tecnico">Técnico</option><option value="admin">Admin</option></select></div>
            <div class="form-group"><label class="form-label">Área perteneciente</label><input class="form-control" name="area" value="{{ old('area', 'Área de Proyectos y Planificación') }}"></div>
            <div class="form-group"><label class="form-label">Teléfono</label><input class="form-control" name="phone" value="{{ old('phone') }}"></div>
            <div class="form-group"><label class="form-label">Correo</label><input class="form-control" type="email" name="email" value="{{ old('email') }}"></div>
        </div>
        <div class="actions" style="margin-top:18px">
            <button class="btn btn-primary" type="submit">Crear Usuario</button>
            <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Volver</a>
        </div>
    </form>
</div>
@endsection
