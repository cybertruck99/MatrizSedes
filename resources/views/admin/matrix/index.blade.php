@extends('layouts.app')
@section('title', 'Matriz de Seguimiento | SEDES')
@section('page_title', 'Matriz de Seguimiento')
@section('page_subtitle', $history ? 'Historial completo de tareas designadas' : 'Vista principal limitada a 15 registros recientes')
@section('content')
<div class="card">
    <div class="toolbar">
        <div class="actions">
            <a class="btn btn-secondary" href="{{ route('admin.matrix.index') }}">Todos</a>
            <a class="btn btn-secondary" href="{{ route('admin.matrix.index', 'cumplidos') }}">Cumplidos</a>
            <a class="btn btn-secondary" href="{{ route('admin.matrix.index', 'pendientes') }}">Pendientes</a>
            <a class="btn btn-secondary" href="{{ route('admin.matrix.index', 'no-cumplidos') }}">No Cumplidos</a>
            <a class="btn btn-gold" href="{{ route('admin.matrix.index', ['historial' => 1]) }}">Ver Historial</a>
        </div>
        <form class="searchbar" method="GET">
            @if($history)<input type="hidden" name="historial" value="1">@endif
            <input class="form-control" style="max-width:260px" name="search" value="{{ request('search') }}" placeholder="Buscar en matriz">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>

    <p class="readonly-note">Al presionar el técnico designado se abre el perfil del usuario responsable.</p>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Técnico Designado</th><th>Tarea Designada</th><th>Plazo en Días</th><th>Estado</th><th>Fecha de Inicio</th><th>Fecha de Vencimiento</th><th>Cumplimiento</th><th>Fecha de Cumplimiento</th><th>Observaciones</th>
            </tr>
            </thead>
            <tbody>
            @forelse($records as $record)
                <tr class="{{ $record->state_class }}">
                    <td>
                        @if($record->technician)
                            <a class="link" href="{{ route('admin.users.show', $record->technician) }}">{{ $record->technician->name }}</a>
                        @else
                            Sin asignar
                        @endif
                    </td>
                    <td><a class="link" href="{{ route('admin.records.edit', $record) }}">{{ $record->assigned_task }}</a></td>
                    <td>{{ $record->business_days_deadline }}</td>
                    <td><span class="badge badge-{{ $record->state === 'cumplido' ? 'success' : ($record->state === 'no cumplido' ? 'danger' : ($record->state === 'retraso' ? 'warning' : 'neutral')) }}">{{ $record->state_label }}</span></td>
                    <td>{{ $record->start_date->format('d/m/Y') }}</td>
                    <td>{{ optional($record->due_date)->format('d/m/Y') }}</td>
                    <td colspan="3">
                        <form class="matrix-cell-form" method="POST" action="{{ route('admin.matrix.updateCompliance', $record) }}">
                            @csrf @method('PATCH')
                            <select class="form-select" name="compliance">
                                <option value="">Sin definir</option>
                                @foreach(['SI CUMPLIÓ','NO CUMPLIÓ','RETRASO'] as $option)
                                    <option value="{{ $option }}" @selected($record->compliance === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            <div><strong>Fecha:</strong> {{ optional($record->compliance_date)->format('d/m/Y') ?? '---' }}</div>
                            <textarea name="final_observations" placeholder="Observaciones finales">{{ $record->final_observations }}</textarea>
                            <button class="btn btn-sm btn-primary" type="submit">Actualizar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">No existen registros para mostrar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($history)
        <div class="pagination">{{ $records->links() }}</div>
    @endif
</div>
@endsection
