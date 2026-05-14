@extends('layouts.app')
@section('title', 'Panel Técnico | Matriz SEDES')
@section('page_title', 'Panel Técnico')
@section('page_subtitle', 'Vista operativa con consulta de matriz y usuarios')
@section('content')
<section class="hero">
    <h2>Control técnico de seguimiento</h2>
    <p>El rol técnico puede consultar la matriz, revisar usuarios y gestionar sus propias tareas sin acceso a creación o eliminación administrativa.</p>
</section>
<div class="grid grid-4">
    <div class="card stat"><small>Total tareas</small><strong>{{ $stats['todas'] }}</strong></div>
    <div class="card stat"><small>Pendientes</small><strong>{{ $stats['pendientes'] }}</strong></div>
    <div class="card stat"><small>Cumplidas</small><strong>{{ $stats['cumplidos'] }}</strong></div>
    <div class="card stat"><small>Retrasos</small><strong>{{ $stats['retrasos'] }}</strong></div>
</div>
<div class="card" style="margin-top:18px">
    <h3>Mis tareas asignadas</h3>
    <div class="table-wrap"><table class="table"><thead><tr><th>Tarea</th><th>Estado</th><th>Vencimiento</th></tr></thead><tbody>
    @forelse($assigned as $task)
        <tr class="{{ $task->state_class }}"><td><a class="link" href="{{ route('tecnico.tasks.show', $task) }}">{{ $task->assigned_task }}</a></td><td>{{ $task->state_label }}</td><td>{{ optional($task->due_date)->format('d/m/Y') }}</td></tr>
    @empty
        <tr><td colspan="3">No tiene tareas asignadas.</td></tr>
    @endforelse
    </tbody></table></div>
</div>
@endsection
