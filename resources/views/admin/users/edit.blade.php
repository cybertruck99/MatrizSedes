@extends('layouts.app')
@section('title', 'Editar Usuario | Matriz SEDES')
@section('page_title', 'Editar Usuario')
@section('page_subtitle', 'Actualización de datos institucionales, cargo, área y rol')
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
    $storedAreaIsListed = in_array($user->area, $areas, true);
    $selectedArea = old('area', $storedAreaIsListed ? $user->area : 'OTRA AREA');
    $otherArea = old('area_other', $storedAreaIsListed ? '' : $user->area);
@endphp
<div class="card form-card">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group"><label class="form-label">Nombre completo</label><input class="form-control" name="name" data-auto-format="title" value="{{ old('name', $user->name) }}" required></div>
            <div class="form-group"><label class="form-label">CI</label><input class="form-control" name="ci" data-auto-format="title" value="{{ old('ci', $user->ci) }}"></div>
            <div class="form-group"><label class="form-label">Cargo a Ocupar</label><input class="form-control" name="cargo" data-auto-format="title" value="{{ old('cargo', $user->cargo) }}"></div>
            <div class="form-group"><label class="form-label">Fecha de ingreso</label><input class="form-control" type="date" name="admission_date" value="{{ old('admission_date', optional($user->admission_date)->format('Y-m-d')) }}"></div>
            <div class="form-group"><label class="form-label">Usuario ID único</label><input class="form-control" name="username" value="{{ old('username', $user->username) }}" required></div>
            <div class="form-group"><label class="form-label">Contraseña</label><input class="form-control" type="password" name="password" placeholder="Dejar vacío para conservar la actual"><span class="readonly-note">Si cambia la contraseña, use mínimo 6 caracteres con letras y números.</span></div>
            <div class="form-group">
                <label class="form-label">Rol Predeterminado</label>
                <select class="form-select" name="role" required>
                    <option value="user" @selected(old('role', $user->role) === 'user')>Usuario</option>
                    <option value="tecnico" @selected(old('role', $user->role) === 'tecnico')>Técnico</option>
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrador</option>
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
                    value="{{ $otherArea }}"
                    placeholder="Escriba el nombre del área"
                    @if($selectedArea !== 'OTRA AREA') hidden @endif
                >
                @error('area_other')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group"><label class="form-label">Teléfono</label><input class="form-control" name="phone" data-auto-format="title" value="{{ old('phone', $user->phone) }}"></div>
            <div class="form-group"><label class="form-label">Correo</label><input class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}"></div>
        </div>
        <div class="actions" style="margin-top:18px">
            <button class="btn btn-primary" type="submit">Actualizar Usuario</button>
            <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Volver</a>
        </div>
    </form>
</div>
@endsection
