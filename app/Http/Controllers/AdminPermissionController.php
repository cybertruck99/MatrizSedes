<?php

namespace App\Http\Controllers;

use App\Models\AdminPermissionRequest;
use App\Models\TemporaryAdminPermission;
use App\Models\User;
use App\Support\AdminPasswordVerifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPermissionController extends Controller
{
    public function __construct(
        private readonly AdminPasswordVerifier $adminPasswordVerifier
    ) {
    }

    public function request(Request $request)
    {
        $user = User::query()->whereKey(session('auth_user_id'))->firstOrFail();
        abort_unless($user->role === 'tecnico', 403);

        if ($user->adminPermissionRequests()->pending()->exists()) {
            return back()->with('success', 'Su solicitud ya fue enviada. Espere la respuesta de un administrador.');
        }

        $user->adminPermissionRequests()->create([
            'status' => AdminPermissionRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        return back()->with('success', 'Solicitud enviada correctamente. Espere la respuesta de un administrador.');
    }

    public function cancel(Request $request)
    {
        $user = User::query()->whereKey(session('auth_user_id'))->firstOrFail();
        abort_unless($user->role === 'tecnico', 403);

        $pendingRequest = $user->adminPermissionRequests()->pending()->first();

        if ($pendingRequest) {
            $pendingRequest->update([
                'status' => AdminPermissionRequest::STATUS_CANCELED,
                'resolved_at' => now(),
            ]);
        }

        return back()->with('success', 'Solicitud de permisos cancelada correctamente.');
    }

    public function deny(AdminPermissionRequest $adminPermissionRequest)
    {
        abort_unless($adminPermissionRequest->status === AdminPermissionRequest::STATUS_PENDING, 404);

        $adminPermissionRequest->update([
            'status' => AdminPermissionRequest::STATUS_DENIED,
            'resolved_by' => session('auth_user_id'),
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Solicitud de permisos denegada correctamente.');
    }

    public function grant(Request $request, User $user)
    {
        abort_unless($user->role === 'tecnico' && ! $user->trashed(), 404);

        $data = $request->validate([
            'starts_on' => ['required', 'date', 'after_or_equal:today'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'permission_request_id' => ['nullable', 'integer', 'exists:admin_permission_requests,id'],
        ], [
            'starts_on.required' => 'Seleccione la fecha de inicio.',
            'starts_on.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',
            'ends_on.required' => 'Seleccione la fecha de finalización.',
            'ends_on.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ]);

        $this->adminPasswordVerifier->verify($request);

        $startsAt = Carbon::parse($data['starts_on'])->startOfDay();
        $endsAt = Carbon::parse($data['ends_on'])->endOfDay();
        $requestId = $data['permission_request_id'] ?? null;

        if ($requestId && ! AdminPermissionRequest::query()->whereKey($requestId)->where('user_id', $user->id)->pending()->exists()) {
            throw ValidationException::withMessages([
                'permission_request_id' => 'La solicitud seleccionada no corresponde a este técnico.',
            ]);
        }

        DB::transaction(function () use ($user, $startsAt, $endsAt, $requestId) {
            $user->temporaryAdminPermissions()
                ->whereNull('revoked_at')
                ->where('ends_at', '>=', now())
                ->update(['revoked_at' => now()]);

            TemporaryAdminPermission::create([
                'user_id' => $user->id,
                'granted_by' => session('auth_user_id'),
                'admin_permission_request_id' => $requestId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $pendingRequests = $user->adminPermissionRequests()->pending();

            if ($requestId) {
                $pendingRequests->whereKey($requestId);
            }

            $pendingRequests->update([
                'status' => AdminPermissionRequest::STATUS_APPROVED,
                'resolved_by' => session('auth_user_id'),
                'resolved_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Permisos de administrador concedidos correctamente.');
    }

    public function revoke(Request $request, User $user)
    {
        abort_unless($user->role === 'tecnico' && ! $user->trashed(), 404);

        $activePermission = $user->temporaryAdminPermissions()
            ->active()
            ->latest('ends_at')
            ->first();

        if (! $activePermission) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('error', 'Este técnico no tiene permisos de administrador vigentes.');
        }

        $this->adminPasswordVerifier->verify($request);

        $activePermission->update(['revoked_at' => now()]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Permisos de administrador cancelados correctamente.');
    }
}
