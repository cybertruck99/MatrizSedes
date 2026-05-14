@extends('layouts.app')
@section('title', 'Mis Tareas | Matriz SEDES')
@section('page_title', 'Ver Mis Tareas')
@section('page_subtitle', 'Listado completo de tareas asignadas')
@section('content')
@php $prefix = request()->routeIs('tecnico.*') ? 'tecnico' : 'user'; @endphp
<div class="card">
    <div class="toolbar">
        <div class="actions">
            <a class="btn btn-secondary" href="{{ route($prefix.'.tasks') }}">Todas</a>
            <a class="btn btn-secondary" href="{{ route($prefix.'.tasks', ['estado' => 'pendiente']) }}">Pendientes</a>
            <a class="btn btn-secondary" href="{{ route($prefix.'.tasks', ['estado' => 'cumplido']) }}">Cumplidas</a>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarea asignada</th><th>Estado</th><th>Plazo</th><th>Inicio</th><th>Vencimiento</th><th>Cumplimiento</th><th>Archivo</th></tr></thead>
            <tbody>
            @forelse($tasks as $task)
                <tr class="{{ $task->state_class }}">
                    <td><a class="link" href="{{ route($prefix.'.tasks.show', $task) }}">{{ $task->assigned_task }}</a></td>
                    <td>{{ $task->state_label }}</td>
                    <td>{{ $task->business_days_deadline }} días</td>
                    <td>{{ $task->start_date->format('d/m/Y') }}</td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') }}</td>
                    <td>{{ $task->compliance ?? 'Sin calificar' }}</td>
                    <td>@if($task->uploaded_file_path)<a class="file-link" target="_blank" href="{{ asset('storage/'.$task->uploaded_file_path) }}">Ver archivo</a>@else<span class="muted">Sin archivo</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7">No existen tareas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $tasks->links() }}</div>
</div>
@endsection
