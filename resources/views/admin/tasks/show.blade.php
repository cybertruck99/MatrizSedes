@extends('layouts.app')
@section('title', 'Revisión de Tarea | Matriz SEDES')
@section('page_title', 'Perfil de Tarea')
@section('page_subtitle', 'Vista de revisión para administración')
@section('content')
@php
    $dueTime = $task->due_date ? $task->due_date->copy()->endOfDay()->toIso8601String() : null;
    $submittedTime = $task->submitted_at?->toIso8601String() ?? $task->taskFiles->first()?->created_at?->toIso8601String();
    $reportRequestKey = (string) \Illuminate\Support\Str::uuid();
    $canUploadOwnTask = (int) $task->technician_id === (int) session('auth_user_id');
@endphp
<div class="task-profile-header">
    <div>
        <a class="btn btn-secondary" href="{{ route('admin.matrix.index') }}">Volver a la Matriz</a>
        @if($canUploadOwnTask)
            <a class="btn btn-primary" href="{{ route('admin.my-tasks.show', $task) }}">Subir Tarea</a>
        @endif
    </div>
    <div class="actions">
        @if($task->technician)
            <a class="btn btn-primary" href="{{ route('admin.users.show', $task->technician) }}">Ver Perfil</a>
        @endif
        <button class="btn btn-gold" type="button" data-confirm-open="task-report-modal">Generar Reporte de Tarea</button>
        <button class="btn btn-danger" type="button" data-confirm-open="delete-task-modal">Eliminar tarea</button>
    </div>
</div>
@error('admin_password')<div class="alert alert-error">{{ $message }}</div>@enderror
@error('report_observations')<div class="alert alert-error">{{ $message }}</div>@enderror

<div class="grid grid-2">
    <div class="card">
        <h2 style="margin-top:0">{{ $task->assigned_task }}</h2>
        <div class="status-summary">
            <div>
                <span class="status-label">Estado de Entrega</span>
                <span class="badge badge-{{ $task->delivery_status_class }}">{{ $task->delivery_status_label }}</span>
            </div>
            <div>
                <span class="status-label">Estado de Revisión</span>
                <span class="badge badge-{{ $task->state_badge_class }}">{{ $task->state_label }}</span>
            </div>
        </div>
        <p><strong>Responsable:</strong> {{ $task->technician->name ?? 'Sin asignar' }}</p>
        <p><strong>Fecha de inicio:</strong> {{ $task->start_date->format('d/m/Y') }}</p>
        <p><strong>Fecha de vencimiento:</strong> {{ optional($task->due_date)->format('d/m/Y') ?? 'Sin fecha' }}</p>
        <p>
            <strong>Tiempo Restante:</strong>
            <span
                class="remaining-time"
                data-countdown
                data-due="{{ $dueTime }}"
                data-delivery-state="{{ $task->has_uploaded_files ? 'submitted' : 'not-submitted' }}"
                data-submitted-at="{{ $submittedTime }}"
            >Calculando...</span>
        </p>
        <p><strong>Observación inicial:</strong><br>{{ $task->initial_observation ?? 'Sin observación inicial.' }}</p>
    </div>

    <div class="card">
        <h3 style="margin-top:0">Resumen de archivos</h3>
        <p><strong>Archivos enviados:</strong> {{ $task->uploaded_files_summary }}</p>
        <p><strong>Enviado por:</strong> {{ $task->submitter->name ?? 'Sin información' }}</p>
        <p><strong>Fecha de revisión de archivos:</strong> {{ $task->files_reviewed_at_label }}</p>
        <p class="muted">La bandeja reúne todos los documentos e imágenes enviados para esta tarea.</p>
    </div>
</div>

<div class="card" style="margin-top:18px">
    <div class="toolbar">
        <div>
            <h3 style="margin:0">Bandeja de archivos</h3>
            <p class="muted" style="margin-bottom:0">Abra cada archivo en el navegador o descárguelo conservando su nombre original.</p>
        </div>
    </div>

    @if($task->taskFiles->isNotEmpty())
        <div class="admin-file-tray">
            @foreach($task->taskFiles as $file)
                <article class="admin-file-card">
                    <div class="file-preview">
                        @if($file->is_image)
                            <img src="{{ route('task-files.show', $file) }}" alt="{{ $file->display_name }}">
                        @else
                            <div class="file-extension">{{ strtoupper(pathinfo($file->display_name, PATHINFO_EXTENSION) ?: 'FILE') }}</div>
                        @endif
                    </div>
                    <div class="file-card-body">
                        <strong title="{{ $file->display_name }}">{{ $file->display_name }}</strong>
                        <span class="muted">{{ $file->uploader->name ?? 'Usuario' }} · {{ $file->created_at->format('d/m/Y H:i') }}</span>
                        <span class="muted">{{ $file->size_bytes ? number_format($file->size_bytes / 1048576, 2).' MB' : 'Tamaño no disponible' }}</span>
                    </div>
                    <div class="actions">
                        <a class="btn btn-sm btn-secondary" href="{{ route('task-files.show', $file) }}" target="_blank">Ver</a>
                        <a class="btn btn-sm btn-primary" href="{{ route('task-files.download', $file) }}">Descargar</a>
                    </div>
                </article>
            @endforeach
        </div>
    @elseif($task->uploaded_file_path)
        <div class="admin-file-tray">
            <article class="admin-file-card">
                <div class="file-preview">
                    <div class="file-extension">{{ strtoupper(pathinfo($task->uploaded_file_path, PATHINFO_EXTENSION) ?: 'FILE') }}</div>
                </div>
                <div class="file-card-body">
                    <strong>{{ basename($task->uploaded_file_path) }}</strong>
                    <span class="muted">Archivo perteneciente al sistema anterior.</span>
                </div>
                <div class="actions">
                    <a class="btn btn-sm btn-secondary" href="{{ route('task-files.legacy.show', $task) }}" target="_blank">Ver</a>
                    <a class="btn btn-sm btn-primary" href="{{ route('task-files.legacy.download', $task) }}">Descargar</a>
                </div>
            </article>
        </div>
    @else
        <div class="empty-state">El usuario todavía no envió archivos para esta tarea.</div>
    @endif
</div>

<div class="card review-card" style="margin-top:18px">
    <h3 style="margin-top:0">Cumplimiento y Observaciones</h3>
    <form method="POST" action="{{ route('admin.matrix.updateCompliance', $task) }}">
        @csrf
        @method('PATCH')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="state">Cumplimiento</label>
                <select class="form-select" id="state" name="state" required>
                    @foreach(['pendiente' => 'Pendiente', 'cumplido' => 'Sí cumplió', 'no cumplido' => 'No cumplió', 'retraso' => 'Retraso'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('state', $task->state) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <span class="matrix-date-inline">Última modificación: {{ optional($task->compliance_date)->format('d/m/Y') ?? '---' }}</span>
            </div>
            <div class="form-group">
                <label class="form-label" for="final_observations">Observaciones</label>
                <textarea id="final_observations" name="final_observations" data-auto-format="sentence" maxlength="3000" placeholder="Añada las observaciones de la revisión">{{ old('final_observations', $task->final_observations) }}</textarea>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Actualizar</button>
    </form>
</div>

<div class="confirmation-modal" id="task-report-modal" data-confirm-modal data-auto-open="{{ $errors->has('report_observations') ? '1' : '0' }}" hidden>
    <div class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="task-report-title">
        <button class="confirmation-close" type="button" data-confirm-close aria-label="Cerrar">×</button>
        <h3 id="task-report-title">Generar reporte de tarea</h3>
        <p>
            Introduzca observaciones solo si desea que aparezcan en el reporte. Si deja este campo vacío,
            el sistema usará las observaciones finales de la tarea; si tampoco existen, omitirá ese apartado.
        </p>
        <form method="POST" action="{{ route('admin.tasks.report', $task) }}" target="_blank" data-close-on-submit>
            @csrf
            <input type="hidden" name="report_request_key" value="{{ $reportRequestKey }}" data-report-request-key>
            <div class="form-group">
                <label class="form-label" for="report_observations">Observaciones para el reporte</label>
                <textarea id="report_observations" name="report_observations" data-auto-format="paragraph" maxlength="3000" placeholder="Añada observaciones para este reporte, si corresponde.">{{ old('report_observations') }}</textarea>
                <span class="readonly-note">El texto escrito aquí tendrá prioridad sobre las observaciones finales guardadas.</span>
            </div>
            <div class="actions confirmation-actions">
                <button class="btn btn-secondary" type="button" data-confirm-close>Cancelar</button>
                <button class="btn btn-gold" type="submit">Generar</button>
            </div>
        </form>
    </div>
</div>

<div class="confirmation-modal" id="delete-task-modal" data-confirm-modal data-auto-open="{{ $errors->has('admin_password') ? '1' : '0' }}" hidden>
    <div class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-task-title">
        <button class="confirmation-close" type="button" data-confirm-close aria-label="Cerrar">×</button>
        <h3 id="delete-task-title">Confirmar eliminación de tarea</h3>
        <p>Esta acción eliminará la tarea <strong>{{ $task->assigned_task }}</strong> y sus archivos asociados.</p>
        <form method="POST" action="{{ route('admin.records.destroy', $task) }}">
            @csrf
            @method('DELETE')
            <div class="form-group">
                <label class="form-label" for="delete-task-password">Ingrese su contraseña de administrador</label>
                <input class="form-control" id="delete-task-password" type="password" name="admin_password" required autocomplete="current-password" data-confirm-password>
            </div>
            @error('admin_password')<div class="error-text">{{ $message }}</div>@enderror
            <div class="actions confirmation-actions">
                <button class="btn btn-secondary" type="button" data-confirm-close>Cancelar</button>
                <button class="btn btn-danger" type="submit">Eliminar tarea</button>
            </div>
        </form>
    </div>
</div>
@endsection
