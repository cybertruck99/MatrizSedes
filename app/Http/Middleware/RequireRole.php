<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = session('auth_user');

        if (!$user || !isset($user['role'])) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para continuar.');
        }

        if (!in_array($user['role'], $roles, true)) {
            return redirect()->route('login')->with('error', 'No tiene permisos para acceder a esa sección.');
        }

        return $next($request);
    }
}
