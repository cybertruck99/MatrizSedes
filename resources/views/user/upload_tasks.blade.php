@extends('layouts.app')
@section('title', 'Subir Tareas | Matriz SEDES')
@section('page_title', 'Subir Tareas')
@section('page_subtitle', $showAll ? 'Administración de archivos de todas sus tareas' : 'Tareas pendientes de entrega')
@section('content')
@php
    $routes = request()->routeIs('admin.*')
        ? [
            'index' => 'admin.my-tasks.upload.index',
            'show' => 'admin.my-tasks.show',
        ]
        : [
            'index' => request()->routeIs('tecnico.*') ? 'tecnico.tasks.upload.index' : 'user.tasks.upload.index',
            'show' => request()->routeIs('tecnico.*') ? 'tecnico.tasks.show' : 'user.tasks.show',
        ];
@endphp
<div class="card">
    <div class="toolbar">
        <div class="actions">
            <a class="btn {{ $showAll ? 'btn-secondary' : 'btn-primary' }}" href="{{ route($routes['index']) }}">Pendientes de subir</a>
            <a class="btn {{ $showAll ? 'btn-primary' : 'btn-secondary' }}" href="{{ route($routes['index'], ['todas' => 1]) }}">Ver todas las tareas</a>
        </div>
        <form class="searchbar" method="GET" data-live-search>
            @if($showAll)<input type="hidden" name="todas" value="1">@endif
            <input class="form-control" style="max-width:280px" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar tarea">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>

    <div class="table-wrap">
        <table class="table upload-table">
            <thead>
                <tr>
                    <th>Tarea</th>
                    <th>Estado de Entrega</th>
                    <th>Estado de Revisión</th>
                    <th>Vencimiento</th>
                    <th>Archivos subidos</th>
                    <th>Subir</th>
                </tr>
            </thead>
            <tbody>
            @forelse($tasks as $task)
                @php $taskFileCount = $task->taskFiles->count(); @endphp
                <tr class="{{ $task->state_class }} task-row-link" data-row-href="{{ route($routes['show'], $task) }}" tabindex="0" aria-label="Ver perfil de tarea: {{ $task->assigned_task }}">
                    <td>
                        <span class="task-title-with-dot">
                            @if($task->is_new_assignment)
                                <span class="submission-dot submission-dot-new assignment-dot" title="Nueva tarea asignada"></span>
                            @endif
                            <span class="task-title-text upload-task-name">{{ $task->assigned_task }}</span>
                        </span>
                    </td>
                    <td><span class="badge badge-{{ $task->delivery_status_class }}">{{ $task->delivery_status_label }}</span></td>
                    <td><span class="badge badge-{{ $task->state_badge_class }}">{{ $task->state_label }}</span></td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') ?? 'Sin fecha' }}</td>
                    <td class="upload-files-cell">
                        @if($taskFileCount > 1)
                            <span>{{ $taskFileCount }} archivos</span>
                        @elseif($taskFileCount === 1)
                            @php $file = $task->taskFiles->first(); @endphp
                            <div class="upload-single-file">
                                <strong title="{{ $file->display_name }}">{{ $file->display_name }}</strong>
                                <div class="actions">
                                    <a class="btn btn-sm btn-secondary" href="{{ route('task-files.show', $file) }}" target="_blank">Ver</a>
                                    <a class="btn btn-sm btn-light" href="{{ route('task-files.download', $file) }}">Descargar</a>
                                </div>
                            </div>
                        @elseif($task->uploaded_file_path)
                            <div class="upload-single-file">
                                <strong title="{{ basename($task->uploaded_file_path) }}">{{ basename($task->uploaded_file_path) }}</strong>
                                <div class="actions">
                                    <a class="btn btn-sm btn-secondary" href="{{ route('task-files.legacy.show', $task) }}" target="_blank">Ver</a>
                                    <a class="btn btn-sm btn-light" href="{{ route('task-files.legacy.download', $task) }}">Descargar</a>
                                </div>
                            </div>
                        @else
                            <span class="muted">Sin archivo</span>
                        @endif
                    </td>
                    <td>
                        <span class="muted">Abrir fila</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ $showAll ? 'No existen tareas para mostrar.' : 'Aún no hay tareas pendientes de carga; revise Ver todas las tareas para administrar archivos anteriores.' }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination pagination-outside">{{ $tasks->onEachSide(1)->links('layouts.pagination') }}</div>
@endsection
