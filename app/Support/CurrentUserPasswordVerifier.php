<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CurrentUserPasswordVerifier
{
    public function verify(Request $request): User
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
        ], [
            'current_password.required' => 'Ingrese su contraseña para confirmar la acción.',
        ]);

        $user = User::query()
            ->whereKey(session('auth_user_id'))
            ->where('active', true)
            ->first();

        if (! $user || ! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'La contraseña ingresada no es correcta.',
            ]);
        }

        return $user;
    }
}
