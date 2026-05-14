@extends('layouts.app')
@section('title', 'Panel Usuario | Matriz SEDES')
@section('page_title', 'Página Principal')
@section('page_subtitle', 'Resumen personal de tareas asignadas')
@section('content')
<section class="hero">
    <h2>Mis tareas de seguimiento</h2>
    <p>Revise sus pendientes, tareas cumplidas y registros observados. Desde el detalle de cada tarea puede subir su archivo de respaldo.</p>
</section>
<div class="grid grid-4">
    <a class="card stat" href="{{ route(request()->routeIs('tecnico.*') ? 'tecnico.tasks' : 'user.tasks', ['estado' => 'pendiente']) }}"><small>Pendientes</small><strong>{{ $stats['pendientes'] }}</strong></a>
    <a class="card stat" href="{{ route(request()->routeIs('tecnico.*') ? 'tecnico.tasks' : 'user.tasks', ['estado' => 'cumplido']) }}"><small>Cumplidos</small><strong>{{ $stats['cumplidos'] }}</strong></a>
    <a class="card stat" href="{{ route(request()->routeIs('tecnico.*') ? 'tecnico.tasks' : 'user.tasks', ['estado' => 'no cumplido']) }}"><small>No cumplidos</small><strong>{{ $stats['no_cumplidos'] }}</strong></a>
    <a class="card stat" href="{{ route(request()->routeIs('tecnico.*') ? 'tecnico.tasks' : 'user.tasks', ['estado' => 'retraso']) }}"><small>Retrasos</small><strong>{{ $stats['retrasos'] }}</strong></a>
</div>
<div class="card" style="margin-top:18px">
    <h3>Mis últimas tareas</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarea</th><th>Estado</th><th>Inicio</th><th>Vencimiento</th><th>Archivo</th></tr></thead>
            <tbody>
            @forelse($recentTasks as $task)
                <tr class="{{ $task->state_class }}">
                    <td><a class="link" href="{{ route(request()->routeIs('tecnico.*') ? 'tecnico.tasks.show' : 'user.tasks.show', $task) }}">{{ $task->assigned_task }}</a></td>
                    <td>{{ $task->state_label }}</td>
                    <td>{{ $task->start_date->format('d/m/Y') }}</td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') }}</td>
                    <td>{{ $task->uploaded_file_path ? 'Subido' : 'Pendiente' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Aún no tiene tareas asignadas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
