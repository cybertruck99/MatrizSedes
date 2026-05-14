@extends('layouts.app')
@section('title', 'Registros | Matriz SEDES')
@section('page_title', 'Crear Registro')
@section('page_subtitle', 'CRUD de tareas designadas por fecha de creación')
@section('content')
<div class="card">
    <div class="toolbar">
        <a class="btn btn-primary" href="{{ route('admin.records.create') }}">Crear Nuevo Registro</a>
        <form class="searchbar" method="GET">
            <input class="form-control" style="max-width:280px" name="search" value="{{ request('search') }}" placeholder="Buscar tarea o técnico">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fecha creación</th><th>Fecha inicio</th><th>Técnico</th><th>Tarea</th><th>Plazo</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse($records as $record)
                <tr class="{{ $record->state_class }}">
                    <td>{{ $record->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $record->start_date->format('d/m/Y') }}</td>
                    <td>{{ $record->technician->name ?? 'Sin asignar' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($record->assigned_task, 80) }}</td>
                    <td>{{ $record->business_days_deadline }} días hábiles</td>
                    <td>{{ $record->state_label }}</td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-sm btn-secondary" href="{{ route('admin.records.edit', $record) }}">Editar</a>
                            <form method="POST" action="{{ route('admin.records.destroy', $record) }}" onsubmit="return confirm('¿Eliminar este registro?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No existen registros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $records->links() }}</div>
</div>
@endsection
