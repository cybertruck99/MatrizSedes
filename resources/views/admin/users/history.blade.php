@extends('layouts.app')
@section('title', 'Historial Usuarios | Matriz SEDES')
@section('page_title', 'Historial de Usuarios')
@section('page_subtitle', 'Usuarios activos y retirados del sistema')
@section('content')
<div class="card">
    <div class="toolbar">
        <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Volver a usuarios activos</a>
        <form class="searchbar" method="GET">
            <input class="form-control" style="max-width:280px" name="search" value="{{ request('search') }}" placeholder="Buscar historial">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Creado</th><th>Estado</th><th>Retirado</th></tr></thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td><td>{{ $user->username }}</td><td>{{ $user->role_label }}</td><td>{{ $user->created_at->format('d/m/Y') }}</td>
                    <td>{{ $user->active && !$user->trashed() ? 'Activo' : 'Retirado' }}</td>
                    <td>{{ $user->deleted_at?->format('d/m/Y H:i') ?? '---' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No hay historial.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $users->links() }}</div>
</div>
@endsection
