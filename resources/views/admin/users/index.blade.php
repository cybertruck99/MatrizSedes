@extends('layouts.app')
@section('title', 'Usuarios | Matriz SEDES')
@section('page_title', 'Gestionar Usuarios')
@section('page_subtitle', 'Administración de usuarios activos, roles y perfiles')
@section('content')
<div class="card">
    <div class="toolbar">
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('admin.users.create') }}">Crear Nuevo Usuario</a>
            <a class="btn btn-light" href="{{ route('admin.users.history') }}">Historial de usuarios</a>
        </div>
        <form class="searchbar" method="GET">
            <input class="form-control" style="max-width:280px" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, CI o usuario">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Nombre completo</th><th>Rol</th><th>Área perteneciente</th><th>Cargo</th><th>Fecha ingreso</th><th>Tareas recientes</th><th></th></tr></thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td><a class="link" href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a><br><span class="muted">{{ $user->username }} | CI: {{ $user->ci ?? 'S/D' }}</span></td>
                    <td>{{ $user->role_label }}</td>
                    <td>{{ $user->area ?? 'No definido' }}</td>
                    <td>{{ $user->cargo ?? 'No definido' }}</td>
                    <td>{{ optional($user->admission_date)->format('d/m/Y') ?? 'No definido' }}</td>
                    <td>{{ $user->recent_tasks_count ? $user->recent_tasks_count.' recientes' : 'Aún no subió' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('¿Retirar este usuario de la lista activa?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No existen usuarios activos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $users->links() }}</div>
</div>
@endsection
