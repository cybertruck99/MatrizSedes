@extends('layouts.app')
@section('title', 'Mis Tareas | Matriz SEDES')
@section('page_title', 'Ver Mis Tareas')
@section('page_subtitle', 'Listado completo de tareas asignadas')
@section('content')
@php
    $routes = request()->routeIs('admin.*')
        ? [
            'index' => 'admin.my-tasks.index',
            'show' => 'admin.my-tasks.show',
            'uploadIndex' => 'admin.my-tasks.upload.index',
        ]
        : [
            'index' => request()->routeIs('tecnico.*') ? 'tecnico.tasks' : 'user.tasks',
            'show' => request()->routeIs('tecnico.*') ? 'tecnico.tasks.show' : 'user.tasks.show',
            'uploadIndex' => request()->routeIs('tecnico.*') ? 'tecnico.tasks.upload.index' : 'user.tasks.upload.index',
        ];
@endphp
<div class="card">
    <div class="toolbar">
        <div class="actions">
            <a class="btn btn-secondary" href="{{ route($routes['index']) }}">Todas</a>
            <a class="btn btn-secondary" href="{{ route($routes['index'], ['estado' => 'pendiente']) }}">Pendientes</a>
            <a class="btn btn-secondary" href="{{ route($routes['index'], ['estado' => 'cumplido']) }}">Cumplidas</a>
            <a class="btn btn-primary" href="{{ route($routes['uploadIndex']) }}">Subir Tareas</a>
        </div>
        <form class="searchbar" method="GET" data-live-search>
            @if(request('estado'))<input type="hidden" name="estado" value="{{ request('estado') }}">@endif
            <input class="form-control" style="max-width:280px" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar tarea">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarea asignada</th><th>Estado de Entrega</th><th>Estado de Revisión</th><th>Plazo</th><th>Inicio</th><th>Vencimiento</th><th>Archivos</th></tr></thead>
            <tbody>
            @forelse($tasks as $task)
                <tr class="{{ $task->state_class }} task-row-link" data-row-href="{{ route($routes['show'], $task) }}" tabindex="0" aria-label="Ver perfil de tarea: {{ $task->assigned_task }}">
                    <td>
                        <span class="task-title-with-dot">
                            @if($task->is_new_assignment)
                                <span class="submission-dot submission-dot-new assignment-dot" title="Nueva tarea asignada"></span>
                            @endif
                            <span class="task-title-text">{{ $task->assigned_task }}</span>
                        </span>
                    </td>
                    <td><span class="badge badge-{{ $task->delivery_status_class }}">{{ $task->delivery_status_label }}</span></td>
                    <td><span class="badge badge-{{ $task->state_badge_class }}">{{ $task->state_label }}</span></td>
                    <td>{{ $task->business_days_deadline }} días</td>
                    <td>{{ $task->start_date->format('d/m/Y') }}</td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') }}</td>
                    <td>
                        @if($task->has_uploaded_files)
                            <span>Con archivos</span>
                        @else
                            <span class="muted">Sin archivo</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No existen tareas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination pagination-outside">{{ $tasks->onEachSide(1)->links('layouts.pagination') }}</div>
@endsection
