@extends('layouts.app')
@section('title', 'Panel Admin | Matriz SEDES')
@section('page_title', 'Panel de Administración')
@section('page_subtitle', 'Control general de registros, usuarios, matriz y días especiales')
@section('content')
<section class="hero">
    <h2>Seguimiento institucional de tareas</h2>
    <p>Vista base del sistema para administrar responsables, crear registros, revisar cumplimiento y controlar días no hábiles del calendario laboral.</p>
</section>

<div class="grid grid-4">
    <div class="card stat"><small>Total registros</small><strong>{{ $stats['registros'] }}</strong></div>
    <div class="card stat"><small>Pendientes</small><strong>{{ $stats['pendientes'] }}</strong></div>
    <div class="card stat"><small>Cumplidos</small><strong>{{ $stats['cumplidos'] }}</strong></div>
    <div class="card stat"><small>Usuarios activos</small><strong>{{ $stats['usuarios'] }}</strong></div>
</div>

<div class="grid grid-3" style="margin-top:18px">
    <a class="card" href="{{ route('admin.records.index') }}"><h3>Crear Registro</h3><p class="muted">Alta, edición y eliminación de tareas asignadas.</p></a>
    <a class="card" href="{{ route('admin.users.index') }}"><h3>Gestionar Usuarios</h3><p class="muted">Administración de roles, cargos y perfiles.</p></a>
    <a class="card" href="{{ route('admin.matrix.index') }}"><h3>Matriz de Seguimiento</h3><p class="muted">Evaluación del cumplimiento final de cada tarea.</p></a>
</div>

<div class="card" style="margin-top:18px">
    <div class="toolbar">
        <h3 style="margin:0">Últimos registros</h3>
        <a class="btn btn-secondary" href="{{ route('admin.matrix.index', ['historial' => 1]) }}">Ver historial</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Técnico designado</th><th>Tarea</th><th>Estado</th><th>Vencimiento</th></tr></thead>
            <tbody>
            @forelse($recentTasks as $task)
                <tr class="{{ $task->state_class }}">
                    <td>{{ $task->technician->name ?? 'Sin asignar' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($task->assigned_task, 90) }}</td>
                    <td><span class="badge badge-{{ $task->state === 'cumplido' ? 'success' : ($task->state === 'no cumplido' ? 'danger' : ($task->state === 'retraso' ? 'warning' : 'neutral')) }}">{{ $task->state_label }}</span></td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Aún no existen registros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
