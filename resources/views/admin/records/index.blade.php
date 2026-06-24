@extends('layouts.app')
@section('title', 'Registros | Matriz SEDES')
@section('page_title', 'Crear Registro')
@section('page_subtitle', 'CRUD de tareas designadas por fecha de creación')
@section('content')
<div class="card">
    <div class="toolbar">
        <a class="btn btn-primary" href="{{ route('admin.records.create') }}">Crear Nuevo Registro</a>
        <form class="searchbar" method="GET" data-live-search>
            <input class="form-control" style="max-width:280px" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar tarea o técnico">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fecha creación</th><th>Fecha inicio</th><th>Técnico</th><th>Tarea</th><th>Plazo</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse($records as $record)
                <tr class="{{ $record->state_class }} task-row-link" data-row-href="{{ route('admin.tasks.show', $record) }}" tabindex="0" aria-label="Ver perfil de tarea: {{ $record->assigned_task }}">
                    <td>{{ $record->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $record->start_date->format('d/m/Y') }}</td>
                    <td>{{ $record->technician->name ?? 'Sin asignar' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($record->assigned_task, 80) }}</td>
                    <td>{{ $record->business_days_deadline }} días hábiles</td>
                    <td>{{ $record->state_label }}</td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-sm btn-secondary" href="{{ route('admin.records.edit', $record) }}">Editar</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No existen registros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination pagination-outside">{{ $records->onEachSide(1)->links('layouts.pagination') }}</div>
@endsection
