@extends('layouts.app')
@section('title', 'Editar Registro | Matriz SEDES')
@section('page_title', 'Editar Registro')
@section('page_subtitle', 'Actualización de tarea asignada')
@section('content')
<div class="card form-card">
    <form method="POST" action="{{ route('admin.records.update', $record) }}">
        @csrf @method('PUT')
        @include('admin.records.form', ['record' => $record])
        <div class="actions" style="margin-top:18px">
            <button class="btn btn-primary" type="submit">Guardar Cambios</button>
            <a class="btn btn-secondary" href="{{ route('admin.records.index') }}">Volver</a>
        </div>
    </form>
</div>
@endsection
