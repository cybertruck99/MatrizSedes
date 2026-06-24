@extends('layouts.app')
@section('title', 'Matriz Técnica | Matriz SEDES')
@section('page_title', 'Matriz de Seguimiento')
@section('page_subtitle', 'Vista de consulta para técnico')
@section('content')
<div class="card">
    <form class="toolbar" method="GET" data-live-search>
        <div class="searchbar">
            <select class="form-select" name="estado" style="max-width:240px">
                <option value="">Todos los estados</option>
                @foreach(['pendiente'=>'Pendiente','cumplido'=>'Cumplido','no cumplido'=>'No cumplido','retraso'=>'Retraso'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('estado') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input class="form-control" style="max-width:280px" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar técnico o tarea">
            <button class="btn btn-secondary" type="submit">Filtrar</button>
        </div>
    </form>
    <div class="table-wrap"><table class="table"><thead><tr><th>Técnico</th><th>Tarea</th><th>Estado de Revisión</th><th>Inicio</th><th>Vencimiento</th><th>Cumplimiento</th><th>Observaciones</th></tr></thead><tbody>
    @forelse($records as $record)
        <tr class="{{ $record->state_class }}"><td>{{ $record->technician->name ?? 'Sin asignar' }}</td><td>{{ $record->assigned_task }}</td><td><span class="badge badge-{{ $record->state_badge_class }}">{{ $record->state_label }}</span></td><td>{{ $record->start_date->format('d/m/Y') }}</td><td>{{ optional($record->due_date)->format('d/m/Y') }}</td><td>{{ $record->state_label }}</td><td>{{ $record->final_observations ?? '---' }}</td></tr>
    @empty
        <tr><td colspan="7">No hay registros.</td></tr>
    @endforelse
    </tbody></table></div>
</div>
<div class="pagination pagination-outside">{{ $records->onEachSide(1)->links('layouts.pagination') }}</div>
@endsection
