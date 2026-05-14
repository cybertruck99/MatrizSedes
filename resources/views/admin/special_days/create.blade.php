@extends('layouts.app')
@section('title', 'Nuevo Día Especial | Matriz SEDES')
@section('page_title', 'Crear Día Especial')
@section('page_subtitle', 'Registro de fecha no hábil')
@section('content')
<div class="card form-card">
    <form method="POST" action="{{ route('admin.special-days.store') }}">
        @csrf
        @include('admin.special_days.form', ['day' => null])
        <div class="actions" style="margin-top:18px"><button class="btn btn-primary" type="submit">Crear</button><a class="btn btn-secondary" href="{{ route('admin.special-days.index') }}">Volver</a></div>
    </form>
</div>
@endsection
