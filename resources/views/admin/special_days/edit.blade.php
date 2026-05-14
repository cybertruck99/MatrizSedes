@extends('layouts.app')
@section('title', 'Editar Día Especial | Matriz SEDES')
@section('page_title', 'Editar Día Especial')
@section('page_subtitle', 'Actualización de fecha no hábil')
@section('content')
<div class="card form-card">
    <form method="POST" action="{{ route('admin.special-days.update', $specialDay) }}">
        @csrf @method('PUT')
        @include('admin.special_days.form', ['day' => $specialDay])
        <div class="actions" style="margin-top:18px"><button class="btn btn-primary" type="submit">Guardar</button><a class="btn btn-secondary" href="{{ route('admin.special-days.index') }}">Volver</a></div>
    </form>
</div>
@endsection
