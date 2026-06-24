<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminPasswordVerifier
{
    public function verify(Request $request): void
    {
        $data = $request->validate([
            'admin_password' => ['required', 'string'],
        ], [
            'admin_password.required' => 'Ingrese su contraseña para confirmar la acción.',
        ]);

        $admin = User::query()
            ->whereKey(session('auth_user_id'))
            ->where('active', true)
            ->first();

        $canConfirmAsAdmin = $admin
            && ($admin->role === 'admin' || $admin->hasActiveTemporaryAdminPermission());

        if (! $canConfirmAsAdmin || ! Hash::check($data['admin_password'], $admin->password)) {
            throw ValidationException::withMessages([
                'admin_password' => 'La contraseña ingresada no es correcta.',
            ]);
        }
    }
}
