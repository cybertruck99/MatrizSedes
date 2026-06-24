@extends('layouts.app')
@section('title', 'Panel Tecnico | Matriz SEDES')
@section('page_title', 'Panel Tecnico')
@section('page_subtitle', 'Vista operativa con consulta de matriz y usuarios')
@section('page_actions')
    <a class="top-stat-chip" href="{{ route('tecnico.tasks', ['estado' => 'pendiente']) }}">
        <span>Pendientes</span>
        <strong>{{ $stats['pendientes'] }}</strong>
    </a>
    <span class="top-stat-chip top-stat-chip-muted">
        <span>Usuarios en linea</span>
        <strong data-online-users data-online-url="{{ route('tecnico.users.online') }}">{{ $stats['usuarios_en_linea'] }}</strong>
    </span>
@endsection
@section('content')
<div class="card">
    <h3>Mis tareas asignadas</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarea</th><th>Estado</th><th>Vencimiento</th></tr></thead>
            <tbody>
            @forelse($assigned as $task)
                <tr class="{{ $task->state_class }} task-row-link" data-row-href="{{ route('tecnico.tasks.show', $task) }}" tabindex="0" aria-label="Ver perfil de tarea: {{ $task->assigned_task }}">
                    <td>
                        <span class="task-title-with-dot">
                            @if($task->is_new_assignment)
                                <span class="submission-dot submission-dot-new assignment-dot" title="Nueva tarea asignada"></span>
                            @endif
                            <span class="task-title-text">{{ $task->assigned_task }}</span>
                        </span>
                    </td>
                    <td>{{ $task->state_label }}</td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No tiene tareas asignadas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
