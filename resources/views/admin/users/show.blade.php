@extends('layouts.app')
@section('title', 'Perfil Usuario | Matriz SEDES')
@section('page_title', 'Perfil de Usuario')
@section('page_subtitle', 'Datos personales y tareas asignadas')
@section('content')
@php
    $temporaryPassword = (int) session('temporary_password_user_id') === (int) $user->id
        ? session('temporary_password')
        : null;
    $canOpenPasswordRecovery = $canRequestPasswordRecovery
        && (
            $viewerRole === 'admin'
            || ($viewerRole === 'tecnico' && $hasActiveRecoveryToken && $user->role !== 'admin')
        );
    $passwordDeniedMessage = session('password_denied')
        ?? ($user->role === 'admin'
            ? 'El rol técnico no puede recuperar contraseñas de administradores.'
            : 'Primero se debe obtener el token de recuperación desde el apartado de Olvidó su Contraseña.');
@endphp
<div class="grid grid-2">
    <div class="card">
        <div class="profile-head">
            <div class="profile-photo">
                @if($user->profile_photo_url)
                    <img src="{{ $user->profile_photo_url }}" alt="Fotografia de {{ $user->name }}">
                @else
                    {{ $user->profile_initial }}
                @endif
            </div>
            <div>
                <h2 style="margin:0">{{ $user->name }}</h2>
                <p class="muted">{{ $user->role_label }} | {{ $user->cargo ?? 'Cargo no definido' }}</p>
            </div>
        </div>
        <p><strong>CI:</strong> {{ $user->ci ?? 'No definido' }}</p>
        <p><strong>Usuario:</strong> {{ $user->username }}</p>
        <p><strong>Área:</strong> {{ $user->area ?? 'No definido' }}</p>
        <p><strong>Fecha de ingreso:</strong> {{ optional($user->admission_date)->format('d/m/Y') ?? 'No definido' }}</p>
        <p><strong>Teléfono:</strong> {{ $user->phone ?? 'No definido' }}</p>
        <p><strong>Correo:</strong> {{ $user->email ?? 'No definido' }}</p>
        <div class="password-recovery-row">
            <strong>Contraseña:</strong>
            @if($temporaryPassword)
                <span class="temporary-password">{{ $temporaryPassword }}</span>
            @else
                <span class="password-mask">********</span>
            @endif
            @if($canRequestPasswordRecovery)
                <button
                    class="btn btn-sm btn-secondary"
                    type="button"
                    data-confirm-open="{{ $canOpenPasswordRecovery ? 'recover-password-modal' : 'password-denied-modal' }}"
                >Mostrar Contraseña</button>
            @endif
        </div>
        <div class="actions" style="margin-top:18px">
            @if(request()->routeIs('admin.*') && ! $user->trashed())
                <a class="btn btn-secondary" href="{{ route('admin.users.edit', $user) }}">Editar usuario</a>
                @if($canGrantTemporaryAdminPermission)
                    <button class="btn btn-gold" type="button" data-confirm-open="grant-admin-permission-modal">Conceder permisos</button>
                @endif
                @if((int) $user->id !== (int) session('auth_user_id'))
                    <button class="btn btn-danger" type="button" data-confirm-open="delete-user-modal">Eliminar usuario</button>
                @endif
            @elseif($user->trashed())
                <span class="badge badge-neutral">Usuario retirado</span>
            @endif
        </div>
        @error('admin_password')<div class="error-text" style="margin-top:10px">{{ $message }}</div>@enderror
        @error('current_password')<div class="error-text" style="margin-top:10px">{{ $message }}</div>@enderror
        @if($canGrantTemporaryAdminPermission && $activeTemporaryAdminPermission)
            <p class="muted" style="margin-top:10px">
                Permiso admin vigente desde {{ $activeTemporaryAdminPermission->starts_at->format('d/m/Y') }}
                hasta {{ $activeTemporaryAdminPermission->ends_at->format('d/m/Y') }}.
            </p>
        @elseif($canGrantTemporaryAdminPermission && $pendingAdminPermissionRequest)
            <p class="muted" style="margin-top:10px">Este técnico tiene una solicitud de permisos pendiente.</p>
        @endif
    </div>
    <div class="grid grid-2">
        <div class="card stat"><small>Pendientes</small><strong>{{ $counts['pendientes'] }}</strong></div>
        <div class="card stat"><small>Cumplidas</small><strong>{{ $counts['cumplidos'] }}</strong></div>
        <div class="card stat"><small>No cumplidas</small><strong>{{ $counts['no_cumplidos'] }}</strong></div>
        <div class="card stat"><small>Retrasos</small><strong>{{ $counts['retrasos'] }}</strong></div>
    </div>
</div>
<div class="card" style="margin-top:18px">
    <h3>Tareas asignadas y subidas</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarea</th><th>Estado de Revisión</th><th>Inicio</th><th>Vencimiento</th><th>Archivo</th></tr></thead>
            <tbody>
            @forelse($tasks as $task)
                <tr
                    class="{{ $task->state_class }} {{ request()->routeIs('admin.*') ? 'task-row-link' : '' }}"
                    @if(request()->routeIs('admin.*')) data-row-href="{{ route('admin.tasks.show', $task) }}" tabindex="0" aria-label="Ver perfil de tarea: {{ $task->assigned_task }}" @endif
                >
                    <td>{{ $task->assigned_task }}</td>
                    <td>{{ $task->state_label }}</td>
                    <td>{{ $task->start_date->format('d/m/Y') }}</td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') }}</td>
                    <td>
                        @if($task->has_uploaded_files)
                            <span>Con archivos</span>
                        @else
                            <span class="muted">Sin archivo</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Este usuario aún no tiene tareas asignadas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination pagination-outside">{{ $tasks->onEachSide(1)->links('layouts.pagination') }}</div>

@if($canRequestPasswordRecovery && $canOpenPasswordRecovery)
    <div class="confirmation-modal" id="recover-password-modal" data-confirm-modal data-auto-open="{{ $errors->has('current_password') ? '1' : '0' }}" hidden>
        <div class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="recover-password-title">
            <button class="confirmation-close" type="button" data-confirm-close aria-label="Cerrar">&times;</button>
            <h3 id="recover-password-title">Confirmar recuperación de contraseña</h3>
            <p>Se generará una contraseña temporal para <strong>{{ $user->name }}</strong>. Entréguela solo al usuario verificado.</p>
            <form method="POST" action="{{ $passwordRecoveryRoute }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="recover-password-current">Ingrese su contraseña</label>
                    <input class="form-control" id="recover-password-current" type="password" name="current_password" required autocomplete="current-password" data-confirm-password>
                </div>
                @error('current_password')<div class="error-text">{{ $message }}</div>@enderror
                <div class="actions confirmation-actions">
                    <button class="btn btn-secondary" type="button" data-confirm-close>Cancelar</button>
                    <button class="btn btn-primary" type="submit">Mostrar Contraseña</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if($canRequestPasswordRecovery && ! $canOpenPasswordRecovery)
    <div class="confirmation-modal" id="password-denied-modal" data-confirm-modal data-auto-open="{{ session('password_denied') ? '1' : '0' }}" hidden>
        <div class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="password-denied-title">
            <button class="confirmation-close" type="button" data-confirm-close aria-label="Cerrar">&times;</button>
            <h3 id="password-denied-title">Acceso denegado</h3>
            <p>{{ $passwordDeniedMessage }}</p>
            <div class="actions confirmation-actions">
                <button class="btn btn-secondary" type="button" data-confirm-close>Cerrar</button>
            </div>
        </div>
    </div>
@endif

@if($canGrantTemporaryAdminPermission)
    <div class="confirmation-modal" id="grant-admin-permission-modal" data-confirm-modal data-auto-open="{{ ($autoOpenAdminPermissionModal || $errors->has('starts_on') || $errors->has('ends_on')) ? '1' : '0' }}" hidden>
        <div class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="grant-admin-permission-title">
            <button class="confirmation-close" type="button" data-confirm-close aria-label="Cerrar">&times;</button>
            <h3 id="grant-admin-permission-title">Conceder permisos de Admin</h3>
            <p>Configure el periodo en el que <strong>{{ $user->name }}</strong> tendrá acceso temporal a la vista de administrador.</p>
            <input type="hidden" name="permission_request_id" value="{{ old('permission_request_id', $adminPermissionRequestId) }}" data-admin-permission-source="permission_request_id">
            <div class="form-group">
                <label class="form-label" for="grant-starts-on">Fecha de inicio</label>
                <input class="form-control" id="grant-starts-on" type="date" name="starts_on" value="{{ old('starts_on', now()->toDateString()) }}" required data-admin-permission-source="starts_on">
                @error('starts_on')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="grant-ends-on">Fecha final</label>
                <input class="form-control" id="grant-ends-on" type="date" name="ends_on" value="{{ old('ends_on', now()->toDateString()) }}" required data-admin-permission-source="ends_on">
                @error('ends_on')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="actions confirmation-actions">
                <button class="btn btn-secondary" type="button" data-confirm-close>Cancelar</button>
                <button class="btn btn-primary" type="button" data-admin-permission-next>Confirmar</button>
            </div>
        </div>
    </div>

    <div class="confirmation-modal" id="grant-admin-permission-password-modal" data-confirm-modal data-auto-open="{{ $errors->has('admin_password') && old('admin_permission_form') ? '1' : '0' }}" hidden>
        <div class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="grant-admin-permission-password-title">
            <button class="confirmation-close" type="button" data-confirm-close aria-label="Cerrar">&times;</button>
            <h3 id="grant-admin-permission-password-title">Confirmar permisos de Admin</h3>
            <p>Para completar la concesión de permisos, ingrese su contraseña de administrador.</p>
            <form method="POST" action="{{ route('admin.users.admin-permissions.grant', $user) }}" data-admin-permission-form>
                @csrf
                <input type="hidden" name="admin_permission_form" value="1">
                <input type="hidden" name="permission_request_id" value="{{ old('permission_request_id', $adminPermissionRequestId) }}" data-admin-permission-target="permission_request_id">
                <input type="hidden" name="starts_on" value="{{ old('starts_on', now()->toDateString()) }}" data-admin-permission-target="starts_on">
                <input type="hidden" name="ends_on" value="{{ old('ends_on', now()->toDateString()) }}" data-admin-permission-target="ends_on">
                <div class="form-group">
                    <label class="form-label" for="grant-admin-password">Ingrese su contraseña</label>
                    <input class="form-control" id="grant-admin-password" type="password" name="admin_password" required autocomplete="current-password" data-confirm-password>
                </div>
                @error('admin_password')<div class="error-text">{{ $message }}</div>@enderror
                <div class="actions confirmation-actions">
                    <button class="btn btn-secondary" type="button" data-confirm-close>Cancelar</button>
                    <button class="btn btn-primary" type="submit">Conceder permisos</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const firstModal = document.getElementById('grant-admin-permission-modal');
        const secondModal = document.getElementById('grant-admin-permission-password-modal');
        const nextButton = firstModal?.querySelector('[data-admin-permission-next]');
        const sources = firstModal?.querySelectorAll('[data-admin-permission-source]') || [];

        nextButton?.addEventListener('click', () => {
            const invalid = Array.from(sources).find((field) => field.required && !field.checkValidity());
            if (invalid) {
                invalid.reportValidity();
                return;
            }

            sources.forEach((source) => {
                const target = secondModal?.querySelector(`[data-admin-permission-target="${source.name}"]`);
                if (target) target.value = source.value;
            });

            firstModal.hidden = true;
            secondModal.hidden = false;
            document.body.classList.add('confirmation-open');
            secondModal.querySelector('[name="admin_password"]')?.focus();
        });
    });
    </script>
@endif

@if(request()->routeIs('admin.*') && ! $user->trashed() && (int) $user->id !== (int) session('auth_user_id'))
    <div class="confirmation-modal" id="delete-user-modal" data-confirm-modal data-auto-open="{{ $errors->has('admin_password') && ! old('admin_permission_form') ? '1' : '0' }}" hidden>
        <div class="confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-user-title">
            <button class="confirmation-close" type="button" data-confirm-close aria-label="Cerrar">&times;</button>
            <h3 id="delete-user-title">Confirmar eliminación de usuario</h3>
            <p>Se retirará a <strong>{{ $user->name }}</strong> de la lista activa y permanecerá disponible en el historial.</p>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                @csrf
                @method('DELETE')
                <div class="form-group">
                    <label class="form-label" for="delete-user-password">Ingrese su contraseña de administrador</label>
                    <input class="form-control" id="delete-user-password" type="password" name="admin_password" required autocomplete="current-password" data-confirm-password>
                </div>
                @error('admin_password')<div class="error-text">{{ $message }}</div>@enderror
                <div class="actions confirmation-actions">
                    <button class="btn btn-secondary" type="button" data-confirm-close>Cancelar</button>
                    <button class="btn btn-danger" type="submit">Eliminar usuario</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
