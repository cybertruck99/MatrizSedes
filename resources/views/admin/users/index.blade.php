@extends('layouts.app')
@section('title', 'Usuarios | Matriz SEDES')
@section('page_title', 'Gestionar Usuarios')
@section('page_subtitle', 'Administracion de usuarios activos, roles y perfiles')
@section('content')
<div class="card">
    <div class="toolbar">
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('admin.users.create') }}">Crear Nuevo Usuario</a>
            <a class="btn btn-light" href="{{ route('admin.users.history') }}">Historial de usuarios</a>
        </div>
        <form class="searchbar" method="GET" data-live-search>
            <input class="form-control" style="max-width:280px" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, CI o usuario">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Nombre completo</th>
                <th>Area perteneciente</th>
                <th>Cargo</th>
                <th>Fecha ingreso</th>
                <th>Tarea reciente</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr class="task-row-link" data-row-href="{{ route('admin.users.show', $user) }}" tabindex="0" aria-label="Ver perfil de usuario: {{ $user->name }}">
                    <td><strong>{{ $user->name }}</strong><br><span class="muted">CI: {{ $user->ci ?? 'S/D' }}</span></td>
                    <td>{{ $user->area ?? 'No definido' }}</td>
                    <td>{{ $user->cargo ?? 'No definido' }}</td>
                    <td>{{ optional($user->admission_date)->format('d/m/Y') ?? 'No definido' }}</td>
                    <td>{{ $user->recent_task_status_label }}</td>
                    <td>
                        <a class="btn btn-sm btn-secondary" href="{{ route('admin.users.edit', $user) }}">Editar</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No existen usuarios activos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination pagination-outside">{{ $users->onEachSide(1)->links('layouts.pagination') }}</div>
@endsection
