@extends('layouts.app')
@section('title', 'Nuevo Registro | Matriz SEDES')
@section('page_title', 'Crear Nuevo Registro')
@section('page_subtitle', 'Asignación inicial de tarea y cálculo de plazo hábil')
@section('content')
<div class="card form-card">
    <form method="POST" action="{{ route('admin.records.store') }}">
        @csrf
        @include('admin.records.form', ['record' => null])
        <div class="actions" style="margin-top:18px">
            <button class="btn btn-primary" type="submit">Crear</button>
            <a class="btn btn-secondary" href="{{ route('admin.records.index') }}">Volver</a>
        </div>
    </form>
</div>
@endsection
