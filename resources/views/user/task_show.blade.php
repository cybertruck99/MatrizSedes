@extends('layouts.app')
@section('title', 'Detalle de Tarea | Matriz SEDES')
@section('page_title', 'Perfil de Tarea')
@section('page_subtitle', 'Detalle de la tarea asignada y carga de archivos')
@section('content')
@php
    $routes = request()->routeIs('admin.*')
        ? [
            'upload' => 'admin.my-tasks.upload',
            'prepare' => 'admin.my-tasks.upload.prepare',
            'clear' => 'admin.my-tasks.upload.prepared.clear',
            'fileDestroy' => 'admin.my-tasks.files.destroy',
            'legacyDestroy' => 'admin.my-tasks.files.legacy-destroy',
        ]
        : [
            'upload' => request()->routeIs('tecnico.*') ? 'tecnico.tasks.upload' : 'user.tasks.upload',
            'prepare' => request()->routeIs('tecnico.*') ? 'tecnico.tasks.upload.prepare' : 'user.tasks.upload.prepare',
            'clear' => request()->routeIs('tecnico.*') ? 'tecnico.tasks.upload.prepared.clear' : 'user.tasks.upload.prepared.clear',
            'fileDestroy' => request()->routeIs('tecnico.*') ? 'tecnico.tasks.files.destroy' : 'user.tasks.files.destroy',
            'legacyDestroy' => request()->routeIs('tecnico.*') ? 'tecnico.tasks.files.legacy-destroy' : 'user.tasks.files.legacy-destroy',
        ];
    $dueTime = $task->due_date ? $task->due_date->copy()->endOfDay()->toIso8601String() : null;
    $submittedTime = $task->submitted_at?->toIso8601String() ?? $task->taskFiles->first()?->created_at?->toIso8601String();
@endphp
<div class="grid grid-2">
    <div class="card">
        <h2 style="margin-top:0">{{ $task->assigned_task }}</h2>
        <p>
            <strong>Estado de Entrega:</strong>
            <span class="badge badge-{{ $task->delivery_status_class }}">{{ $task->delivery_status_label }}</span>
        </p>
        <p>
            <strong>Estado de Revisión:</strong>
            <span class="badge badge-{{ $task->state_badge_class }}">{{ $task->state_label }}</span>
        </p>
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
        <p><strong>Observación final:</strong><br>{{ $task->final_observations ?? 'Sin observación final.' }}</p>
    </div>
    <div class="card">
        <h3>Subir archivos de tarea</h3>
        <form
            method="POST"
            action="{{ route($routes['upload'], $task) }}"
            enctype="multipart/form-data"
            data-file-limit="30"
            data-max-total-size="52428800"
            data-prepare-url="{{ route($routes['prepare'], $task) }}"
            data-clear-url="{{ route($routes['clear'], $task) }}"
        >
            @csrf
            <p class="upload-limits">Tamaño Máximo de Archivos: 50 MB. Máximo total: 30 archivos.</p>
            <div class="dropzone task-upload-zone" data-file-drop-zone>
                <p><strong>Bandeja de archivos para revisión</strong></p>
                <p class="muted">Seleccione o arrastre aquí archivos TXT, PDF, Word, Excel, PowerPoint, imágenes, videos, ZIP y RAR.</p>
                <div class="actions upload-actions">
                    <label class="btn btn-secondary" for="documentFiles">Seleccionar documentos</label>
                    <button class="btn btn-gold" type="button" data-camera-button>Cámara</button>
                </div>
                <input class="visually-hidden" id="documentFiles" type="file" multiple data-file-source accept=".pdf,.txt,.csv,.doc,.docx,.xls,.xlsx,.xlsm,.xlsb,.ods,.ppt,.pptx,.pptm,.pps,.ppsx,.odp,.jpg,.jpeg,.png,.webp,.gif,.bmp,.heic,.heif,.mp4,.mov,.avi,.mkv,.webm,.m4v,.3gp,.zip,.rar">
                <input class="visually-hidden" type="file" data-file-source data-camera-input accept="image/*" capture="environment">
                <div data-prepared-files>
                    @foreach($preparedUploads as $token => $preparedFile)
                        <input
                            type="hidden"
                            name="prepared_files[]"
                            value="{{ $token }}"
                            data-prepared-token
                            data-file-name="{{ $preparedFile['name'] }}"
                            data-file-size="{{ $preparedFile['size_bytes'] }}"
                        >
                    @endforeach
                </div>
                <div class="upload-progress" data-upload-progress hidden>
                    <div class="upload-progress-head">
                        <span data-upload-message>Subiendo archivo...</span>
                        <strong data-upload-percent>0%</strong>
                    </div>
                    <div class="upload-progress-track">
                        <div class="upload-progress-bar" data-upload-bar></div>
                    </div>
                </div>
                <div class="selected-files" data-selected-files>
                    @if($preparedUploads === [])
                        Aún no hay archivos preparados.
                    @else
                        <ul class="prepared-file-list">
                            @foreach($preparedUploads as $preparedFile)
                                <li><span>{{ $preparedFile['name'] }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @error('archivos')<div class="error-text">{{ $message }}</div>@enderror
                @error('archivos.*')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary" style="margin-top:16px" type="submit" @disabled($preparedUploads === [])>Subir</button>
            <button class="btn btn-secondary" style="margin-top:16px" type="button" data-clear-files>Limpiar archivos seleccionados</button>
        </form>
    </div>
</div>

<div class="card task-files-card" id="archivos-tarea">
    <h3 style="margin-top:0">Archivos de la tarea</h3>
    @if($task->taskFiles->isNotEmpty())
        <div class="task-files-tray">
            @foreach($task->taskFiles as $file)
                <div class="task-file-entry">
                    <div class="task-file-info">
                        <strong class="task-file-name">{{ $file->display_name }}</strong>
                        <span class="muted">Subido: {{ $file->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="actions task-file-actions">
                        <a class="btn btn-sm btn-secondary" href="{{ route('task-files.show', $file) }}" target="_blank">Ver</a>
                        <form method="POST" action="{{ route($routes['fileDestroy'], [$task, $file]) }}" onsubmit="return confirm('¿Eliminar este archivo?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($task->uploaded_file_path)
        <div class="task-files-tray">
            <div class="task-file-entry">
                <div class="task-file-info">
                    <strong class="task-file-name">{{ basename($task->uploaded_file_path) }}</strong>
                    <span class="muted">Archivo perteneciente al sistema anterior.</span>
                </div>
                <div class="actions task-file-actions">
                    <a class="btn btn-sm btn-secondary" href="{{ route('task-files.legacy.show', $task) }}" target="_blank">Ver</a>
                    <form method="POST" action="{{ route($routes['legacyDestroy'], $task) }}" onsubmit="return confirm('¿Eliminar este archivo?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="task-files-empty">Aún no se subió tarea.</div>
    @endif
</div>
@endsection
