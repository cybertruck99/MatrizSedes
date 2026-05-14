@extends('layouts.app')
@section('title', 'Usuarios | Técnico')
@section('page_title', 'Ver Usuarios')
@section('page_subtitle', 'Consulta de personal registrado')
@section('content')
<div class="card">
    <form class="toolbar" method="GET">
        <input class="form-control" style="max-width:280px" name="search" value="{{ request('search') }}" placeholder="Buscar usuario">
        <button class="btn btn-secondary" type="submit">Buscar</button>
    </form>
    <div class="table-wrap"><table class="table"><thead><tr><th>Nombre</th><th>Rol</th><th>Área</th><th>Cargo</th><th>Ingreso</th></tr></thead><tbody>
    @forelse($users as $user)
        <tr><td>{{ $user->name }}</td><td>{{ $user->role_label }}</td><td>{{ $user->area ?? 'No definido' }}</td><td>{{ $user->cargo ?? 'No definido' }}</td><td>{{ optional($user->admission_date)->format('d/m/Y') ?? 'No definido' }}</td></tr>
    @empty
        <tr><td colspan="5">No hay usuarios.</td></tr>
    @endforelse
    </tbody></table></div>
    <div class="pagination">{{ $users->links() }}</div>
</div>
@endsection
