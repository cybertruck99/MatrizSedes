<?php

namespace App\Http\Controllers;

use App\Models\PasswordRecoveryToken;
use App\Models\User;
use App\Support\ActiveAccountSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (session('auth_user')) {
            return redirect()->route($this->homeRoute(session('auth_user.role')));
        }

        $baseAdminNotice = null;
        $baseAdmin = User::query()
            ->where('is_base_admin', true)
            ->where('active', true)
            ->whereNull('base_setup_completed_at')
            ->first();

        if (
            $baseAdmin
            && (
                mb_strtolower(trim((string) $baseAdmin->username), 'UTF-8') !== 'adminbase1'
                || ! Hash::check('admin123', $baseAdmin->password)
            )
        ) {
            $baseAdmin->forceFill([
                'is_base_admin' => false,
                'base_setup_completed_at' => now(),
            ])->save();
            $baseAdmin = null;
        }

        if ($baseAdmin && User::withTrashed()->count() > 1) {
            if (! $baseAdmin->base_credentials_shown_at) {
                $baseAdmin->forceFill(['base_credentials_shown_at' => now()])->save();
            }

            $baseAdmin = null;
        }

        if ($baseAdmin && ! $baseAdmin->base_credentials_shown_at && ! $this->isMobileRequest($request)) {
            $baseAdminNotice = [
                'username' => $baseAdmin->username,
                'password' => 'admin123',
            ];
            $baseAdmin->forceFill(['base_credentials_shown_at' => now()])->save();
        }

        return view('auth.login', compact('baseAdminNotice'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $credentials['username'])
            ->where('active', true)
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('username'))
                ->with('error', 'Usuario o contraseña incorrectos.');
        }

        if (! ActiveAccountSessions::canStart($user)) {
            return back()->withInput($request->only('username'))
                ->with('error', 'Esta cuenta ya tiene una sesión activa en otro dispositivo. Cierre sesión en ese dispositivo.');
        }

        $request->session()->regenerate();
        $sessionToken = ActiveAccountSessions::start($user, $request);

        if (! $sessionToken) {
            return back()->withInput($request->only('username'))
                ->with('error', 'Esta cuenta ya tiene una sesión activa en otro dispositivo. Cierre sesión en ese dispositivo.');
        }

        $effectiveRole = $this->effectiveRoleFor($user);

        session([
            'auth_user_id' => $user->id,
            'auth_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $effectiveRole,
                'role_label' => $this->roleLabelFor($user, $effectiveRole),
                'actual_role' => $user->role,
                'temporary_admin' => $effectiveRole === 'admin' && $user->role !== 'admin',
                'cargo' => $user->cargo,
            ],
            'active_session_token' => $sessionToken,
            'last_seen_touch' => now()->timestamp,
            'last_activity_at' => now()->timestamp,
        ]);

        if ($user->is_base_admin && ! $user->base_setup_completed_at) {
            return redirect()->route('admin.base-admin.setup');
        }

        return redirect()->route($this->homeRoute($effectiveRole));
    }

    public function searchPasswordRecovery(Request $request)
    {
        $data = $request->validate([
            'recovery_username' => ['required', 'string', 'max:50'],
        ], [
            'recovery_username.required' => 'Introduzca su nombre de usuario.',
        ]);

        $user = User::where('username', $data['recovery_username'])
            ->where('active', true)
            ->first();

        if (! $user) {
            return back()
                ->withInput(['recovery_username' => $data['recovery_username']])
                ->with('recovery_mode', true)
                ->with('recovery_error', 'El usuario introducido no fue correcto. Si necesita ayuda, aproxímese al área de sistemas o a la oficina del asistente técnico(a).');
        }

        return back()
            ->withInput(['recovery_username' => $data['recovery_username']])
            ->with('recovery_mode', true)
            ->with('recovery_found', true)
            ->with('recovery_message', 'Se encontró su Usuario, para confirmar la solicitud de recuperar su contraseña presione "Solicitar token de recuperación" o aproximarse al área de sistemas');
    }

    public function requestPasswordRecoveryToken(Request $request)
    {
        $data = $request->validate([
            'recovery_username' => ['required', 'string', 'max:50'],
        ]);

        $user = User::where('username', $data['recovery_username'])
            ->where('active', true)
            ->first();

        if (! $user) {
            return back()
                ->withInput(['recovery_username' => $data['recovery_username']])
                ->with('recovery_mode', true)
                ->with('recovery_error', 'El usuario introducido no fue correcto. Si necesita ayuda, aproxímese al área de sistemas o a la oficina del asistente técnico(a).');
        }

        if ($user->passwordRecoveryTokens()->active()->exists()) {
            return back()
                ->withInput(['recovery_username' => $data['recovery_username']])
                ->with('recovery_mode', true)
                ->with('recovery_success', 'Ya existe un token activo para este usuario. Debe esperar a que expire el token anterior.');
        }

        PasswordRecoveryToken::create([
            'user_id' => $user->id,
            'token' => Str::random(48),
            'expires_at' => now()->addMinutes(30),
        ]);

        return back()
            ->withInput(['recovery_username' => $data['recovery_username']])
            ->with('recovery_mode', true)
            ->with('recovery_success', 'Token obtenido. Se verificó su Nombre de Usuario y se recuperó la contraseña, aproximarse al área de sistemas o a la oficina del asistente técnico(a) para obtener su contraseña.');
    }

    public function logout(Request $request)
    {
        if ($request->session()->has('auth_user_id')) {
            ActiveAccountSessions::finish(
                (int) $request->session()->get('auth_user_id'),
                $request->session()->get('active_session_token')
            );
        }

        $request->session()->forget(['auth_user_id', 'auth_user', 'active_session_token', 'last_seen_touch', 'last_activity_at']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
    }

    private function homeRoute(?string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'tecnico' => 'tecnico.dashboard',
            default => 'user.dashboard',
        };
    }

    private function isMobileRequest(Request $request): bool
    {
        $userAgent = mb_strtolower((string) $request->userAgent());

        return (bool) preg_match('/android|iphone|ipad|ipod|mobile|windows phone|blackberry|opera mini/i', $userAgent);
    }

    private function effectiveRoleFor(User $user): string
    {
        return $user->hasActiveTemporaryAdminPermission() ? 'admin' : $user->role;
    }

    private function roleLabelFor(User $user, string $effectiveRole): string
    {
        return $effectiveRole === 'admin' && $user->role !== 'admin'
            ? 'Administrador temporal'
            : $user->role_label;
    }
}
