@extends('layouts.app')
@section('title', 'Configurar Administrador Base | Matriz SEDES')
@section('page_title', 'Configurar Administrador Base')
@section('page_subtitle', 'Primer acceso obligatorio del sistema')
@section('content')
<div class="card form-card">
    <h2 style="margin-top:0">Actualice las credenciales iniciales</h2>
    <p class="readonly-note">
        Esta cuenta es el administrador base de primer ingreso. Para continuar debe definir un nuevo usuario y una nueva contraseña.
        Después de guardar, la cuenta pasará a comportarse como una cuenta administradora normal.
    </p>

    <form method="POST" action="{{ route('admin.base-admin.update') }}">
        @csrf
        @method('PATCH')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="base_username">Nuevo usuario</label>
                <input class="form-control" id="base_username" name="username" value="{{ old('username') }}" placeholder="Ingrese un usuario distinto a adminbase1" required autofocus>
                @error('username')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="base_password">Nueva contraseña</label>
                <input class="form-control" id="base_password" type="password" name="password" required autocomplete="new-password">
                <span class="readonly-note">Mínimo 6 caracteres, usando letras y números.</span>
                @error('password')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="base_password_confirmation">Confirmar contraseña</label>
                <input class="form-control" id="base_password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                @error('password_confirmation')<div class="error-text">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="actions" style="margin-top:18px">
            <button class="btn btn-primary" type="submit">Guardar y continuar</button>
        </div>
    </form>
</div>
@endsection
