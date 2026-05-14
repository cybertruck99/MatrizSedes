@extends('layouts.app')
@section('title', 'Mi Perfil | Matriz SEDES')
@section('page_title', 'Mi Perfil')
@section('page_subtitle', 'Datos del usuario autenticado')
@section('content')
<div class="card form-card">
    <div class="profile-head">
        <div class="profile-photo">{{ mb_substr($user->name,0,1) }}</div>
        <div>
            <h2 style="margin:0">{{ $user->name }}</h2>
            <p class="muted">{{ $user->role_label }} | {{ $user->cargo ?? 'Cargo no definido' }}</p>
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
@endsection
