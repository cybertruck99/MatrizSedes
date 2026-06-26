@extends('layouts.app')
@section('title', 'Historial Usuarios | Matriz SEDES')
@section('page_title', 'Historial de Usuarios')
@section('page_subtitle', 'Usuarios activos y retirados del sistema')
@section('content')
<div class="card">
    <div class="toolbar">
        <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Volver a usuarios activos</a>
        <form class="searchbar" method="GET" data-live-search>
            <input class="form-control" style="max-width:280px" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar historial">
            <button class="btn btn-secondary" type="submit">Buscar</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Nombre completo</th>
                <th>Área perteneciente</th>
                <th>Cargo</th>
                <th>Tarea reciente</th>
                <th>Creado</th>
                <th>Estado</th>
                <th>Retirado</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr class="task-row-link" data-row-href="{{ route('admin.users.show', $user) }}" tabindex="0" aria-label="Ver perfil de usuario: {{ $user->name }}">
                    <td><strong>{{ $user->name }}</strong><br><span class="muted">CI: {{ $user->ci ?? 'S/D' }}</span></td>
                    <td>{{ $user->area ?? 'No definido' }}</td>
                    <td>{{ $user->cargo ?? 'No definido' }}</td>
                    <td>{{ $user->recent_task_status_label }}</td>
                    <td>{{ $user->created_at->format('d/m/Y') }}</td>
                    <td>{{ $user->active && !$user->trashed() ? 'Activo' : 'Retirado' }}</td>
                    <td>{{ $user->deleted_at?->format('d/m/Y H:i') ?? '---' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No hay historial.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination pagination-outside">{{ $users->onEachSide(1)->links('layouts.pagination') }}</div>
@endsection
