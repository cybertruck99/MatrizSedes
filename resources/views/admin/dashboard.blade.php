@extends('layouts.app')
@section('title', 'Panel Admin | Matriz SEDES')
@section('page_title', 'Panel de Administracion')
@section('page_subtitle', 'Control general de registros, usuarios, matriz y días especiales')
@section('page_actions')
    <a class="top-stat-chip" href="{{ route('admin.my-tasks.index', ['estado' => 'pendiente']) }}">
        <span>Pendientes</span>
        <strong>{{ $stats['pendientes'] }}</strong>
    </a>
    <span class="top-stat-chip top-stat-chip-muted">
        <span>Usuarios en linea</span>
        <strong data-online-users data-online-url="{{ route('admin.users.online') }}">{{ $stats['usuarios_en_linea'] }}</strong>
    </span>
@endsection
@section('content')
@if($adminPermissionRequests->isNotEmpty())
    <div class="card" style="margin-bottom:18px">
        <div class="toolbar">
            <h3 style="margin:0">Solicitudes de permisos de Admin</h3>
            <span class="muted">{{ $adminPermissionRequests->count() }} solicitud(es) pendiente(s)</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Técnico solicitante</th>
                    <th>Cargo</th>
                    <th>Fecha de solicitud</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                @foreach($adminPermissionRequests as $permissionRequest)
                    <tr>
                        <td>{{ $permissionRequest->user->name ?? 'Usuario no disponible' }}</td>
                        <td>{{ $permissionRequest->user->cargo ?? 'Cargo no definido' }}</td>
                        <td>{{ $permissionRequest->requested_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="actions">
                                <form method="POST" action="{{ route('admin.admin-permissions.deny', $permissionRequest) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-danger" type="submit">Denegar Permisos</button>
                                </form>
                                @if($permissionRequest->user)
                                    <a
                                        class="btn btn-sm btn-primary"
                                        href="{{ route('admin.users.show', ['user' => $permissionRequest->user, 'grant_admin' => 1, 'permission_request' => $permissionRequest->id]) }}"
                                    >Conceder permisos</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="card">
    <div class="toolbar">
        <h3 style="margin:0">Ultimos registros</h3>
        <a class="btn btn-secondary" href="{{ route('admin.matrix.index', ['historial' => 1]) }}">Ver historial</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Técnico designado</th><th>Tarea</th><th>Estado</th><th>Vencimiento</th></tr></thead>
            <tbody>
            @forelse($recentTasks as $task)
                <tr class="{{ $task->state_class }} task-row-link" data-row-href="{{ route('admin.tasks.show', $task) }}" tabindex="0" aria-label="Ver perfil de tarea: {{ $task->assigned_task }}">
                    <td>{{ $task->technician->name ?? 'Sin asignar' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($task->assigned_task, 90) }}</td>
                    <td><span class="badge badge-{{ $task->state_badge_class }}">{{ $task->state_label }}</span></td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Aún no existen registros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
