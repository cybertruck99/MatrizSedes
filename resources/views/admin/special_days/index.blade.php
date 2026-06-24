@extends('layouts.app')
@section('title', 'Días Especiales | Matriz SEDES')
@section('page_title', 'Gestionar Días Especiales')
@section('page_subtitle', 'CRUD de fechas no hábiles para el cálculo de plazos')
@section('content')
<div class="card">
    <div class="toolbar">
        <a class="btn btn-primary" href="{{ route('admin.special-days.create') }}">Crear Día Especial</a>
        <span class="muted">Los días activos no se suman al plazo de días hábiles.</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Nombre</th><th>Fecha</th><th>Descripción</th><th>Activo</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse($days as $day)
                <tr>
                    <td>{{ $day->name }}</td><td>{{ $day->date->format('d/m/Y') }}</td><td>{{ $day->description ?? '---' }}</td><td>{{ $day->active ? 'Sí' : 'No' }}</td>
                    <td><div class="actions"><a class="btn btn-sm btn-secondary" href="{{ route('admin.special-days.edit', $day) }}">Editar</a><form method="POST" action="{{ route('admin.special-days.destroy', $day) }}" onsubmit="return confirm('¿Eliminar día especial?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" type="submit">Eliminar</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="5">No existen días especiales.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination pagination-outside">{{ $days->onEachSide(1)->links('layouts.pagination') }}</div>
@endsection
