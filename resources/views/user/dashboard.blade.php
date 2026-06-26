@extends('layouts.app')
@section('title', 'Panel Usuario | Matriz SEDES')
@section('page_title', 'Pagina Principal')
@section('page_subtitle', 'Resumen personal de tareas asignadas')
@section('page_actions')
    <a class="top-stat-chip" href="{{ route('user.tasks', ['estado' => 'pendiente']) }}">
        <span>Pendientes</span>
        <strong>{{ $stats['pendientes'] }}</strong>
    </a>
    <span class="top-stat-chip top-stat-chip-muted">
        <span>Usuarios en linea</span>
        <strong data-online-users data-online-url="{{ route('user.users.online') }}">{{ $stats['usuarios_en_linea'] }}</strong>
    </span>
@endsection
@section('content')
@php $taskRoutePrefix = request()->routeIs('tecnico.*') ? 'tecnico' : 'user'; @endphp
<div class="card">
    <h3>Mis últimas tareas</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarea</th><th>Estado de Revisión</th><th>Inicio</th><th>Vencimiento</th><th>Estado de Entrega</th></tr></thead>
            <tbody>
            @forelse($recentTasks as $task)
                <tr class="{{ $task->state_class }} task-row-link" data-row-href="{{ route($taskRoutePrefix.'.tasks.show', $task) }}" tabindex="0" aria-label="Ver perfil de tarea: {{ $task->assigned_task }}">
                    <td>
                        <span class="task-title-with-dot">
                            @if($task->is_new_assignment)
                                <span class="submission-dot submission-dot-new assignment-dot" title="Nueva tarea asignada"></span>
                            @endif
                            <span class="task-title-text">{{ $task->assigned_task }}</span>
                        </span>
                    </td>
                    <td><span class="badge badge-{{ $task->state_badge_class }}">{{ $task->state_label }}</span></td>
                    <td>{{ $task->start_date->format('d/m/Y') }}</td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') }}</td>
                    <td><span class="badge badge-{{ $task->delivery_status_class }}">{{ $task->delivery_status_label }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5">Aún no tiene tareas asignadas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
