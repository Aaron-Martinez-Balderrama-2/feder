<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->guest(route('login'))
                ->with('warning', 'Inicia sesión para continuar.');
        }

        $user = auth()->user();

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Tu cuenta se encuentra inactiva. Contacta al administrador.');
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'No tienes los privilegios requeridos para ingresar a esa sección.');
        }

        return $next($request);
    }
}
