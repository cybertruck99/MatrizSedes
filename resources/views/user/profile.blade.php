@extends('layouts.app')
@section('title', 'Mi Perfil | Matriz SEDES')
@section('page_title', 'Mi Perfil')
@section('page_subtitle', 'Datos del usuario autenticado')
@section('content')
@php
    $prefix = request()->routeIs('admin.*') ? 'admin' : (request()->routeIs('tecnico.*') ? 'tecnico' : 'user');
@endphp
<div class="card form-card">
    <div class="profile-head profile-head-editable">
        <div class="profile-photo">
            @if($user->profile_photo_url)
                <img src="{{ $user->profile_photo_url }}" alt="Fotografía de {{ $user->name }}">
            @else
                {{ $user->profile_initial }}
            @endif
        </div>
        <div class="profile-identity">
            <h2 style="margin:0">{{ $user->name }}</h2>
            <p class="muted">{{ $user->role_label }} | {{ $user->cargo ?? 'Cargo no definido' }}</p>
            <form class="profile-photo-form" method="POST" action="{{ route($prefix.'.profile.photo.update') }}" enctype="multipart/form-data">
                @csrf
                <label class="btn btn-sm btn-secondary" for="profilePhoto">Seleccionar fotografía</label>
                <input class="visually-hidden" id="profilePhoto" type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required onchange="this.form.submit()">
                <span class="muted">JPG, PNG o WEBP. Máximo 5 MB.</span>
                @error('profile_photo')<div class="error-text">{{ $message }}</div>@enderror
            </form>
            @if($user->profile_photo_path)
                <form class="profile-photo-form" method="POST" action="{{ route($prefix.'.profile.photo.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger" type="submit">Eliminar fotografía</button>
                </form>
            @endif
        </div>
    </div>
    <div class="grid grid-2" style="margin-top:20px">
        <p><strong>CI:</strong><br>{{ $user->ci ?? 'No definido' }}</p>
        <p><strong>Usuario:</strong><br>{{ $user->username }}</p>
        <p><strong>Área:</strong><br>{{ $user->area ?? 'No definido' }}</p>
        <p><strong>Fecha de ingreso:</strong><br>{{ optional($user->admission_date)->format('d/m/Y') ?? 'No definido' }}</p>
        <p><strong>Teléfono:</strong><br>{{ $user->phone ?? 'No definido' }}</p>
        <p><strong>Correo:</strong><br>{{ $user->email ?? 'No definido' }}</p>
    </div>
</div>

@if($user->role === 'tecnico' && request()->routeIs('tecnico.*'))
    <div class="card form-card" style="margin-top:18px">
        <div class="toolbar">
            <div>
                <h3 style="margin:0">Permisos temporales de Admin</h3>
                @if($activeTemporaryAdminPermission)
                    <p class="muted" style="margin:6px 0 0">
                        Tiene permisos de admin desde {{ $activeTemporaryAdminPermission->starts_at->format('d/m/Y') }}
                        hasta {{ $activeTemporaryAdminPermission->ends_at->format('d/m/Y') }}.
                    </p>
                @elseif($pendingAdminPermissionRequest)
                    <p class="muted" style="margin:6px 0 0">Su solicitud fue enviada. Espere la respuesta de un administrador.</p>
                @else
                    <p class="muted" style="margin:6px 0 0">Puede solicitar permisos temporales de administrador para apoyo institucional.</p>
                @endif
            </div>
            <div class="actions">
                @if($pendingAdminPermissionRequest)
                    <button class="btn btn-secondary" type="button" disabled>Solicitar permisos de Admin</button>
                    <form method="POST" action="{{ route('tecnico.admin-permissions.cancel') }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Cancelar solicitud</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('tecnico.admin-permissions.request') }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Solicitar permisos de Admin</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endif
@endsection
