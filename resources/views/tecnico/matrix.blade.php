@extends('layouts.app')
@section('title', 'Matriz Técnica | Matriz SEDES')
@section('page_title', 'Matriz de Seguimiento')
@section('page_subtitle', 'Vista de consulta para técnico')
@section('content')
<div class="card">
    <form class="toolbar" method="GET">
        <select class="form-select" name="estado" style="max-width:240px">
            <option value="">Todos los estados</option>
            @foreach(['pendiente'=>'Pendiente','cumplido'=>'Cumplido','no cumplido'=>'No cumplido','retraso'=>'Retraso'] as $value => $label)
                <option value="{{ $value }}" @selected(request('estado') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit">Filtrar</button>
    </form>
    <div class="table-wrap"><table class="table"><thead><tr><th>Técnico</th><th>Tarea</th><th>Estado</th><th>Inicio</th><th>Vencimiento</th><th>Cumplimiento</th><th>Observaciones</th></tr></thead><tbody>
    @forelse($records as $record)
        <tr class="{{ $record->state_class }}"><td>{{ $record->technician->name ?? 'Sin asignar' }}</td><td>{{ $record->assigned_task }}</td><td>{{ $record->state_label }}</td><td>{{ $record->start_date->format('d/m/Y') }}</td><td>{{ optional($record->due_date)->format('d/m/Y') }}</td><td>{{ $record->compliance ?? 'Sin definir' }}</td><td>{{ $record->final_observations ?? '---' }}</td></tr>
    @empty
        <tr><td colspan="7">No hay registros.</td></tr>
    @endforelse
    </tbody></table></div>
    <div class="pagination">{{ $records->links() }}</div>
</div>
@endsection
