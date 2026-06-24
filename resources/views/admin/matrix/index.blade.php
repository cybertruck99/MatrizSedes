@extends('layouts.app')
@section('title', 'Matriz de Seguimiento | SEDES')
@section('page_title', 'Matriz de Seguimiento')
@section('page_subtitle', $history ? 'Historial completo de tareas designadas' : 'Vista principal limitada a 15 registros recientes')
@section('content')
@php
    $historyParams = array_filter([
        'filter' => $filter,
        'historial' => 1,
        'sort' => $sort,
        'search' => request('search'),
    ], fn ($value) => filled($value));
@endphp
<div class="card">
    <div class="toolbar">
        <div class="actions">
            <form class="matrix-sort-form" method="GET" action="{{ $filter ? route('admin.matrix.index', $filter) : route('admin.matrix.index') }}">
                @if($history)<input type="hidden" name="historial" value="1">@endif
                @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                <label class="sort-control">
                    <span>Ordenar Por:</span>
                    <select class="form-select" name="sort" onchange="this.form.submit()">
                        <option value="recent" @selected($sort === 'recent')>Reciente</option>
                        <option value="technician" @selected($sort === 'technician')>Nombre de Técnico</option>
                        <option value="task" @selected($sort === 'task')>Nombre de Tarea</option>
                        <option value="pending" @selected($sort === 'pending')>Por pendientes</option>
                    </select>
                </label>
            </form>
            <a class="btn btn-gold" href="{{ route('admin.matrix.index', $historyParams) }}">Ver Historial</a>
        </div>
        <form class="searchbar" method="GET" data-live-search>
            @if($history)<input type="hidden" name="historial" value="1">@endif
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input class="form-control" style="max-width:260px" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar en matriz">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>

    <p class="readonly-note">Presione cualquier espacio no interactivo de una fila para abrir el perfil de tarea. El punto verde indica un primer envío y el amarillo una modificación.</p>
    <div class="table-wrap matrix-table-wrap">
        <table class="table matrix-table">
            <thead>
            <tr>
                <th>Técnico Designado</th>
                <th>Tarea Asignada</th>
                <th>Plazo en Días</th>
                <th>Estado</th>
                <th>Fecha de Inicio</th>
                <th>Fecha de Vencimiento</th>
                <th>Cumplimiento</th>
                <th>Observaciones</th>
            </tr>
            </thead>
            <tbody>
            @forelse($records as $record)
                @php $formId = 'matrix-form-'.$record->id; @endphp
                <tr class="{{ $record->state_class }} task-row-link" data-row-href="{{ route('admin.tasks.show', $record) }}" tabindex="0" aria-label="Ver perfil de tarea: {{ $record->assigned_task }}">
                    <td>
                        <span class="task-title-with-dot">
                            @if($record->state === 'pendiente' && $record->file_review_status)
                                <span
                                    class="submission-dot submission-dot-{{ $record->file_review_status }}"
                                    title="{{ $record->file_review_label }}"
                                    aria-label="{{ $record->file_review_label }}"
                                ></span>
                            @endif
                            <span class="task-title-text">{{ $record->technician->name ?? 'Sin asignar' }}</span>
                        </span>
                    </td>
                    <td>{{ $record->assigned_task }}</td>
                    <td>{{ $record->business_days_deadline }}</td>
                    <td><span class="badge badge-{{ $record->state_badge_class }}">{{ $record->state_label }}</span></td>
                    <td>{{ $record->start_date->format('d/m/Y') }}</td>
                    <td>{{ optional($record->due_date)->format('d/m/Y') }}</td>
                    <td class="matrix-control-cell">
                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.matrix.updateCompliance', $record) }}">
                            @csrf
                            @method('PATCH')
                            <select class="form-select" name="state">
                                @foreach(['pendiente' => 'Pendiente', 'cumplido' => 'Sí cumplió', 'no cumplido' => 'No cumplió', 'retraso' => 'Retraso'] as $value => $label)
                                    <option value="{{ $value }}" @selected($record->state === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="matrix-date-inline">Última modificación:<br>{{ optional($record->compliance_date)->format('d/m/Y') ?? '---' }}</span>
                        </form>
                    </td>
                    <td class="matrix-observation-cell">
                        <textarea class="matrix-observation" form="{{ $formId }}" name="final_observations" data-auto-format="sentence" placeholder="Observaciones finales">{{ $record->final_observations }}</textarea>
                        <div class="matrix-update-row">
                            <button class="btn btn-sm btn-primary matrix-update-btn" form="{{ $formId }}" type="submit">Actualizar</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">No existen registros para mostrar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($history)
    <div class="pagination pagination-outside">{{ $records->onEachSide(1)->links('layouts.pagination') }}</div>
@endif
@endsection
