@extends('layouts.app')
@section('title', 'Nuevo Usuario | Matriz SEDES')
@section('page_title', 'Crear Nuevo Usuario')
@section('page_subtitle', 'Registro institucional de acceso al sistema')
@section('content')
@php
    $areas = [
        'AREA PLANIFICACION',
        'AREA PROYECTOS',
        'AREA SDIS-VE',
        'AREA CAPACITACION Y ACREDITACION PROFESIONAL',
        'AREA COMUNICACION SOCIAL',
        'AREA SISTEMAS',
    ];
    $selectedArea = old('area');
@endphp
<div class="card form-card">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group"><label class="form-label">Nombre completo</label><input class="form-control" name="name" data-auto-format="title" value="{{ old('name') }}" required></div>
            <div class="form-group"><label class="form-label">CI</label><input class="form-control" name="ci" data-auto-format="title" value="{{ old('ci') }}"></div>
            <div class="form-group"><label class="form-label">Cargo a Ocupar</label><input class="form-control" name="cargo" data-auto-format="title" value="{{ old('cargo') }}"></div>
            <div class="form-group"><label class="form-label">Fecha de ingreso</label><input class="form-control" type="date" name="admission_date" value="{{ old('admission_date', now()->toDateString()) }}"></div>
            <div class="form-group"><label class="form-label">Usuario ID único</label><input class="form-control" name="username" value="{{ old('username') }}" placeholder="Ej. TEC002" required></div>
            <div class="form-group"><label class="form-label">Contraseña</label><input class="form-control" type="password" name="password" required><span class="readonly-note">Mínimo 6 caracteres, usando letras y números.</span></div>
            <div class="form-group">
                <label class="form-label">Rol Predeterminado</label>
                <select class="form-select" name="role" required>
                    <option value="user" @selected(old('role') === 'user')>Usuario</option>
                    <option value="tecnico" @selected(old('role') === 'tecnico')>Tecnico</option>
                    <option value="admin" @selected(old('role') === 'admin')>Administrador</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Área perteneciente</label>
                <select class="form-select" name="area" required data-area-select>
                    <option value="">Seleccione un área</option>
                    @foreach($areas as $area)
                        <option value="{{ $area }}" @selected($selectedArea === $area)>{{ $area }}</option>
                    @endforeach
                    <option value="OTRA AREA" @selected($selectedArea === 'OTRA AREA')>OTRA AREA</option>
                </select>
                <input
                    class="form-control"
                    name="area_other"
                    data-area-other-input
                    data-force-uppercase
                    data-auto-format="title"
                    value="{{ old('area_other') }}"
                    placeholder="Escriba el nombre del área"
                    @if($selectedArea !== 'OTRA AREA') hidden @endif
                >
                @error('area_other')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group"><label class="form-label">Teléfono</label><input class="form-control" name="phone" data-auto-format="title" value="{{ old('phone') }}"></div>
            <div class="form-group"><label class="form-label">Correo</label><input class="form-control" type="email" name="email" value="{{ old('email') }}"></div>
        </div>
        <div class="actions" style="margin-top:18px">
            <button class="btn btn-primary" type="submit">Crear Usuario</button>
            <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Volver</a>
        </div>
    </form>
</div>
@endsection
