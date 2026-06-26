@extends('layouts.app')
@section('title', 'Editar Registro | Matriz SEDES')
@section('page_title', 'Editar Registro')
@section('page_subtitle', 'Actualización de tarea asignada')
@section('content')
@error('admin_password')<div class="alert alert-error">{{ $message }}</div>@enderror
<div class="card form-card">
    <form method="POST" action="{{ route('admin.records.update', $record) }}">
        @csrf @method('PUT')
        @include('admin.records.form', ['record' => $record])
        <div class="actions" style="margin-top:18px">
            <button class="btn btn-primary" type="submit">Guardar Cambios</button>
            <a class="btn btn-secondary" href="{{ route('admin.records.index') }}">Volver</a>
            <button class="btn btn-danger" type="button" data-confirm-open="delete-task-modal">Eliminar tarea</button>
        </div>
    </form>
</div>

<div class="confirmation-modal" id="delete-task-modal" data-confirm-modal data-auto-open="{{ $errors->has('admin_password') ? '1' : '0' }}" hidden>
    <div class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-task-title">
        <button class="confirmation-close" type="button" data-confirm-close aria-label="Cerrar">&times;</button>
        <h3 id="delete-task-title">Confirmar eliminación de tarea</h3>
        <p>Esta acción eliminará la tarea <strong>{{ $record->assigned_task }}</strong> y sus archivos asociados.</p>
        <form method="POST" action="{{ route('admin.records.destroy', $record) }}">
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
