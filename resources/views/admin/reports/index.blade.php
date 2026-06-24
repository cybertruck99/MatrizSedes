@extends('layouts.app')
@section('title', 'Reportes | Matriz SEDES')
@section('page_title', 'Reportes')
@section('page_subtitle', 'Vista previa y generación de reportes de seguimiento')
@section('content')
@php
    $dateStart = $from->format('Y-m-d');
    $dateEnd = $to->format('Y-m-d');
    $filterKind = str_starts_with($filterMode, 'date_') ? 'dates' : 'week';
@endphp

<div class="card">
    <div class="toolbar report-toolbar">
        <div class="actions">
            <form class="matrix-sort-form" method="GET" action="{{ route('admin.reports.index') }}">
                @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                @if($filterMode)<input type="hidden" name="filter_mode" value="{{ $filterMode }}">@endif
                <input type="hidden" name="start_date" value="{{ $dateStart }}">
                <input type="hidden" name="end_date" value="{{ $dateEnd }}">
                <label class="sort-control">
                    <span>Ordenar Por:</span>
                    <select class="form-select" name="sort" onchange="this.form.submit()">
                        <option value="date" @selected($sort === 'date')>Fecha</option>
                        <option value="task" @selected($sort === 'task')>Nombre de Tarea</option>
                        <option value="technician" @selected($sort === 'technician')>Nombre de Técnico</option>
                    </select>
                </label>
            </form>

            <details class="report-menu">
                <summary class="btn btn-gold">Generar Reporte Semanal</summary>
                <div class="report-menu-panel report-menu-wide">
                    <button
                        class="btn btn-secondary"
                        type="button"
                        data-confirm-open="report-observations-modal"
                        data-report-modal-open
                        data-report-action="{{ route('admin.reports.weekly') }}"
                        data-report-mode="created"
                        data-report-title="Generar reporte semanal"
                        data-report-submit-label="Generar Creados Esta Semana"
                    >Generar Creados Esta Semana</button>
                    <button
                        class="btn btn-primary"
                        type="button"
                        data-confirm-open="report-observations-modal"
                        data-report-modal-open
                        data-report-action="{{ route('admin.reports.weekly') }}"
                        data-report-mode="submitted"
                        data-report-title="Generar reporte semanal"
                        data-report-submit-label="Generar Entregados Esta Semana"
                    >Generar Entregados Esta Semana</button>
                </div>
            </details>

            <details class="report-menu">
                <summary class="btn btn-secondary">Generar Reporte por Fechas</summary>
                <div class="report-menu-panel report-date-form" id="date-report-panel">
                    <label>
                        <span>Desde</span>
                        <input class="form-control" type="date" name="start_date" value="{{ $dateStart }}" required>
                    </label>
                    <label>
                        <span>Hasta</span>
                        <input class="form-control" type="date" name="end_date" value="{{ $dateEnd }}" required>
                    </label>
                    <label class="span-2">
                        <span>Tipo</span>
                        <select class="form-select" name="mode" required>
                            <option value="created">Tareas Creadas Durante las Fechas</option>
                            <option value="submitted">Tareas Entregadas Durante las Fechas</option>
                        </select>
                    </label>
                    <button
                        class="btn btn-primary span-2"
                        type="button"
                        data-confirm-open="report-observations-modal"
                        data-report-modal-open
                        data-report-source="date-report-panel"
                        data-report-action="{{ route('admin.reports.dates') }}"
                        data-report-title="Generar reporte por fechas"
                        data-report-submit-label="Generar"
                    >Generar</button>
                </div>
            </details>
        </div>

        <form class="searchbar" method="GET">
            <input type="hidden" name="sort" value="{{ $sort }}">
            @if($filterMode)<input type="hidden" name="filter_mode" value="{{ $filterMode }}">@endif
            <input type="hidden" name="start_date" value="{{ $dateStart }}">
            <input type="hidden" name="end_date" value="{{ $dateEnd }}">
            <input class="form-control" style="max-width:260px" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar tarea o técnico">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>

    <div class="report-filter-box" data-report-filter>
        <label class="sort-control report-filter-control">
            <span>Filtrar:</span>
            <select class="form-select" data-report-filter-kind>
                <option value="week" @selected($filterKind === 'week')>Por Semana</option>
                <option value="dates" @selected($filterKind === 'dates')>Por Fechas</option>
            </select>
        </label>

        <div class="report-filter-panel" data-report-filter-panel="week" {{ $filterKind !== 'week' ? 'hidden' : '' }}>
            <a class="btn {{ $filterMode === 'weekly_created' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('admin.reports.index', array_filter(['sort' => $sort, 'search' => request('search'), 'filter_mode' => 'weekly_created'])) }}">Creados Esta Semana</a>
            <a class="btn {{ $filterMode === 'weekly_submitted' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('admin.reports.index', array_filter(['sort' => $sort, 'search' => request('search'), 'filter_mode' => 'weekly_submitted'])) }}">Entregados Esta Semana</a>
        </div>

        <form class="report-filter-panel report-filter-dates" data-report-filter-panel="dates" method="GET" action="{{ route('admin.reports.index') }}" {{ $filterKind !== 'dates' ? 'hidden' : '' }}>
            <input type="hidden" name="sort" value="{{ $sort }}">
            @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
            <label>
                <span>Desde</span>
                <input class="form-control" type="date" name="start_date" value="{{ $dateStart }}" required>
            </label>
            <label>
                <span>Hasta</span>
                <input class="form-control" type="date" name="end_date" value="{{ $dateEnd }}" required>
            </label>
            <label>
                <span>Tipo</span>
                <select class="form-select" name="filter_mode" required>
                    <option value="date_created" @selected($filterMode === 'date_created')>Tareas Creadas Durante las Fechas</option>
                    <option value="date_submitted" @selected($filterMode === 'date_submitted')>Tareas Entregadas Durante las Fechas</option>
                </select>
            </label>
            <button class="btn btn-primary" type="submit">Aplicar filtro</button>
        </form>
    </div>

    @if($filterMode)
        <p class="readonly-note">Vista previa filtrada del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}.</p>
    @else
        <p class="readonly-note">Presione cualquier espacio no interactivo de una fila para abrir el perfil de tarea.</p>
    @endif

    <div class="table-wrap matrix-table-wrap">
        <table class="table report-table">
            <thead>
            <tr>
                <th>TÉCNICO DESIGNADO</th>
                <th>CARGO</th>
                <th>TAREA ASIGNADA</th>
                <th>FECHA INICIO</th>
                <th>FECHA VENC.</th>
                <th>ESTADO</th>
            </tr>
            </thead>
            <tbody>
            @forelse($records as $record)
                <tr class="{{ $record->state_class }} task-row-link" data-row-href="{{ route('admin.tasks.show', $record) }}" tabindex="0" aria-label="Ver perfil de tarea: {{ $record->assigned_task }}">
                    <td>{{ $record->technician->name ?? 'Sin asignar' }}</td>
                    <td>{{ $record->technician->cargo ?? 'Cargo no definido' }}</td>
                    <td>{{ $record->assigned_task }}</td>
                    <td>{{ $record->start_date->format('d/m/Y') }}</td>
                    <td>{{ optional($record->due_date)->format('d/m/Y') ?? 'Sin fecha' }}</td>
                    <td><span class="badge badge-{{ $record->state_badge_class }}">{{ $record->state_label }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6">No existen tareas para mostrar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

</div>
<div class="pagination pagination-outside">{{ $records->onEachSide(1)->links('layouts.pagination') }}</div>

<div class="confirmation-modal" id="report-observations-modal" data-confirm-modal data-auto-open="{{ $errors->has('report_observations') ? '1' : '0' }}" hidden>
    <div class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="report-observations-title">
        <button class="confirmation-close" type="button" data-confirm-close aria-label="Cerrar">×</button>
        <h3 id="report-observations-title" data-report-modal-title>Generar reporte</h3>
        <p>
            Introduzca observaciones solo si desea que aparezcan en el reporte. Si deja este campo vacío,
            el apartado de observaciones se omitirá.
        </p>
        <form method="POST" action="{{ route('admin.reports.weekly') }}" target="_blank" data-close-on-submit data-report-observations-form>
            @csrf
            <input type="hidden" name="mode" value="created" data-report-field="mode">
            <input type="hidden" name="start_date" value="{{ $dateStart }}" data-report-field="start_date">
            <input type="hidden" name="end_date" value="{{ $dateEnd }}" data-report-field="end_date">
            <input type="hidden" name="report_request_key" value="{{ \Illuminate\Support\Str::uuid() }}" data-report-request-key>
            <div class="form-group">
                <label class="form-label" for="report_page_observations">Observaciones para el reporte</label>
                <textarea id="report_page_observations" name="report_observations" data-auto-format="paragraph" maxlength="3000" placeholder="Añada observaciones para este reporte, si corresponde.">{{ old('report_observations') }}</textarea>
            </div>
            <div class="actions confirmation-actions">
                <button class="btn btn-secondary" type="button" data-confirm-close>Cancelar</button>
                <button class="btn btn-gold" type="submit" data-report-modal-submit>Generar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-report-modal-open]').forEach((button) => {
        if (button.dataset.reportModalBound) return;
        button.dataset.reportModalBound = '1';

        button.addEventListener('click', (event) => {
            const modal = document.getElementById(button.dataset.confirmOpen || 'report-observations-modal');
            const form = modal?.querySelector('[data-report-observations-form]');
            if (!form) return;

            const source = button.dataset.reportSource
                ? document.getElementById(button.dataset.reportSource)
                : null;
            const fields = source ? Array.from(source.querySelectorAll('input, select, textarea')) : [];
            const invalid = fields.find((field) => !field.checkValidity());
            if (invalid) {
                invalid.reportValidity();
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }

            const mode = button.dataset.reportMode || source?.querySelector('[name="mode"]')?.value || 'created';
            const startDate = source?.querySelector('[name="start_date"]')?.value || '{{ $dateStart }}';
            const endDate = source?.querySelector('[name="end_date"]')?.value || '{{ $dateEnd }}';

            form.action = button.dataset.reportAction || form.action;
            form.querySelector('[data-report-field="mode"]').value = mode;
            form.querySelector('[data-report-field="start_date"]').value = startDate;
            form.querySelector('[data-report-field="end_date"]').value = endDate;

            const key = form.querySelector('[data-report-request-key]');
            if (key) {
                key.value = window.crypto?.randomUUID
                    ? window.crypto.randomUUID()
                    : `${Date.now()}-${Math.random().toString(16).slice(2)}`;
            }

            modal.querySelector('[data-report-modal-title]').textContent = button.dataset.reportTitle || 'Generar reporte';
            modal.querySelector('[data-report-modal-submit]').textContent = button.dataset.reportSubmitLabel || 'Generar';

            window.setTimeout(() => {
                const details = button.closest('details');
                if (details) details.open = false;
            }, 120);
        });
    });

    document.querySelectorAll('[data-report-filter]').forEach((box) => {
        const select = box.querySelector('[data-report-filter-kind]');
        const panels = box.querySelectorAll('[data-report-filter-panel]');
        const sync = () => {
            panels.forEach((panel) => {
                panel.hidden = panel.dataset.reportFilterPanel !== select.value;
            });
        };
        select?.addEventListener('change', sync);
        sync();
    });
});
</script>
@endsection
