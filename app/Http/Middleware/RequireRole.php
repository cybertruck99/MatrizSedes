<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ActiveAccountSessions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $sessionUser = session('auth_user');

        if (!$sessionUser || !isset($sessionUser['role'])) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para continuar.');
        }

        $lastActivity = (int) $request->session()->get('last_activity_at', now()->timestamp);
        if ($lastActivity < now()->subMinutes(30)->timestamp) {
            $this->clearActiveSession($request, (int) $sessionUser['id']);
            $this->forgetAuthSession($request);

            return redirect()->route('login')->with('error', 'Sesión caducada por inactividad. Vuelva a iniciar sesión.');
        }

        $currentUser = User::query()->whereKey($sessionUser['id'])->first();
        if (! $currentUser || ! $currentUser->active) {
            $this->forgetAuthSession($request);

            return redirect()->route('login')->with('error', 'Debe iniciar sesión para continuar.');
        }

        $sessionToken = $request->session()->get('active_session_token');
        if (! $sessionToken) {
            $sessionToken = ActiveAccountSessions::start($currentUser, $request);

            if (! $sessionToken) {
                $this->forgetAuthSession($request);

                return redirect()->route('login')->with('error', 'Esta cuenta ya tiene una sesión activa en otro dispositivo. Cierre sesión en ese dispositivo.');
            }

            $request->session()->put('active_session_token', $sessionToken);
        } elseif (! ActiveAccountSessions::ensure($currentUser, $request, (string) $sessionToken)) {
            $this->forgetAuthSession($request);

            return redirect()->route('login')->with('error', 'Esta cuenta ya tiene una sesión activa en otro dispositivo. Cierre sesión en ese dispositivo.');
        }

        if (
            $currentUser->is_base_admin
            && ! $currentUser->base_setup_completed_at
            && ! $request->routeIs('admin.base-admin.setup')
            && ! $request->routeIs('admin.base-admin.update')
        ) {
            return redirect()->route('admin.base-admin.setup');
        }

        $effectiveRole = $this->effectiveRoleFor($currentUser);
        $this->syncSessionUser($request, $currentUser, $effectiveRole);

        if (!in_array($effectiveRole, $roles, true)) {
            return redirect()
                ->route($this->homeRoute($effectiveRole))
                ->with('error', 'No tiene permisos para acceder a esa sección.');
        }

        $lastSeenTouch = (int) $request->session()->get('last_seen_touch', 0);
        if ($lastSeenTouch < now()->subSeconds(20)->timestamp) {
            ActiveAccountSessions::touch($currentUser, $request, (string) $sessionToken);

            $request->session()->put('last_seen_touch', now()->timestamp);
        }

        $request->session()->put('last_activity_at', now()->timestamp);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');

        return $response;
    }

    private function effectiveRoleFor(User $user): string
    {
        return $user->hasActiveTemporaryAdminPermission() ? 'admin' : $user->role;
    }

    private function syncSessionUser(Request $request, User $user, string $effectiveRole): void
    {
        $request->session()->put('auth_user', [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $effectiveRole,
            'role_label' => $effectiveRole === 'admin' && $user->role !== 'admin'
                ? 'Administrador temporal'
                : $user->role_label,
            'actual_role' => $user->role,
            'temporary_admin' => $effectiveRole === 'admin' && $user->role !== 'admin',
            'cargo' => $user->cargo,
        ]);
    }

    private function homeRoute(?string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'tecnico' => 'tecnico.dashboard',
            default => 'user.dashboard',
        };
    }

    private function clearActiveSession(Request $request, int $userId): void
    {
        ActiveAccountSessions::finish($userId, $request->session()->get('active_session_token'));
    }

    private function forgetAuthSession(Request $request): void
    {
        $request->session()->forget([
            'auth_user_id',
            'auth_user',
            'active_session_token',
            'last_seen_touch',
            'last_activity_at',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
