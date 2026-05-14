@extends('layouts.app')
@section('title', 'Perfil Usuario | Matriz SEDES')
@section('page_title', 'Perfil de Usuario')
@section('page_subtitle', 'Datos personales y tareas asignadas')
@section('content')
<div class="grid grid-2">
    <div class="card">
        <div class="profile-head">
            <div class="profile-photo">{{ mb_substr($user->name,0,1) }}</div>
            <div>
                <h2 style="margin:0">{{ $user->name }}</h2>
                <p class="muted">{{ $user->role_label }} | {{ $user->cargo ?? 'Cargo no definido' }}</p>
            </div>
        </div>
        <p><strong>CI:</strong> {{ $user->ci ?? 'No definido' }}</p>
        <p><strong>Usuario:</strong> {{ $user->username }}</p>
        <p><strong>Área:</strong> {{ $user->area ?? 'No definido' }}</p>
        <p><strong>Fecha de ingreso:</strong> {{ optional($user->admission_date)->format('d/m/Y') ?? 'No definido' }}</p>
        <p><strong>Teléfono:</strong> {{ $user->phone ?? 'No definido' }}</p>
        <p><strong>Correo:</strong> {{ $user->email ?? 'No definido' }}</p>
    </div>
    <div class="grid grid-2">
        <div class="card stat"><small>Pendientes</small><strong>{{ $counts['pendientes'] }}</strong></div>
        <div class="card stat"><small>Cumplidas</small><strong>{{ $counts['cumplidos'] }}</strong></div>
        <div class="card stat"><small>No cumplidas</small><strong>{{ $counts['no_cumplidos'] }}</strong></div>
        <div class="card stat"><small>Retrasos</small><strong>{{ $counts['retrasos'] }}</strong></div>
    </div>
</div>
<div class="card" style="margin-top:18px">
    <h3>Tareas asignadas y subidas</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarea</th><th>Estado</th><th>Inicio</th><th>Vencimiento</th><th>Archivo</th></tr></thead>
            <tbody>
            @forelse($tasks as $task)
                <tr class="{{ $task->state_class }}">
                    <td>{{ $task->assigned_task }}</td>
                    <td>{{ $task->state_label }}</td>
                    <td>{{ $task->start_date->format('d/m/Y') }}</td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') }}</td>
                    <td>@if($task->uploaded_file_path)<a class="file-link" href="{{ asset('storage/'.$task->uploaded_file_path) }}" target="_blank">Ver archivo</a>@else<span class="muted">Sin archivo</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="5">Este usuario aún no tiene tareas asignadas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $tasks->links() }}</div>
</div>
@endsection
