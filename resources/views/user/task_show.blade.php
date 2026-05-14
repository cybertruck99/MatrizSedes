@extends('layouts.app')
@section('title', 'Detalle de Tarea | Matriz SEDES')
@section('page_title', 'Perfil de Tarea')
@section('page_subtitle', 'Detalle de la tarea asignada y carga de archivo')
@section('content')
@php $prefix = request()->routeIs('tecnico.*') ? 'tecnico' : 'user'; @endphp
<div class="grid grid-2">
    <div class="card">
        <h2 style="margin-top:0">{{ $task->assigned_task }}</h2>
        <p><strong>Estado:</strong> {{ $task->state_label }}</p>
        <p><strong>Fecha de inicio:</strong> {{ $task->start_date->format('d/m/Y') }}</p>
        <p><strong>Fecha de vencimiento:</strong> {{ optional($task->due_date)->format('d/m/Y') }}</p>
        <p><strong>Plazo:</strong> {{ $task->business_days_deadline }} días hábiles</p>
        <p><strong>Observación inicial:</strong><br>{{ $task->initial_observation ?? 'Sin observación inicial.' }}</p>
        <p><strong>Observación final:</strong><br>{{ $task->final_observations ?? 'Sin observación final.' }}</p>
        @if($task->uploaded_file_path)
            <p><strong>Archivo subido:</strong> <a class="file-link" href="{{ asset('storage/'.$task->uploaded_file_path) }}" target="_blank">Abrir archivo</a></p>
        @endif
    </div>
    <div class="card">
        <h3>Subir archivo de tarea</h3>
        <form method="POST" action="{{ route($prefix.'.tasks.upload', $task) }}" enctype="multipart/form-data">
            @csrf
            <div class="dropzone">
                <p><strong>Arrastre aquí o seleccione un archivo</strong></p>
                <p class="muted">Formatos admitidos: PDF, Office, imágenes, ZIP o RAR. Máximo 10 MB.</p>
                <input class="form-control" type="file" name="archivo" required>
            </div>
            <button class="btn btn-primary" style="margin-top:16px" type="submit">Subir Archivo</button>
            <a class="btn btn-secondary" style="margin-top:16px" href="{{ route($prefix.'.tasks') }}">Volver</a>
        </form>
    </div>
</div>
@endsection
