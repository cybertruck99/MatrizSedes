<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('auth_user')) {
            return redirect()->route($this->homeRoute(session('auth_user.role')));
        }

        return view('auth.login');
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

        session([
            'auth_user_id' => $user->id,
            'auth_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'role_label' => $user->role_label,
            ],
        ]);

        return redirect()->route($this->homeRoute($user->role));
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['auth_user_id', 'auth_user']);
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
}
