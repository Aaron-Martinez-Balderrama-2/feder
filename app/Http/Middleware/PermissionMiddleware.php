<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Verifica que el usuario autenticado posea al menos uno de los permisos indicados en la matriz RBAC.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes iniciar sesión para realizar esta acción.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu cuenta se encuentra desactivada. Contacta a la administración.',
            ], 403);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        // Soportar lista separada por comas o barras verticales
        $needed = [];
        foreach ($permissions as $permGroup) {
            foreach (preg_split('/[|,]/', $permGroup) ?: [] as $slug) {
                $slug = trim($slug);
                if ($slug !== '') {
                    $needed[] = $slug;
                }
            }
        }

        foreach ($needed as $perm) {
            if ($user->hasPermission($perm)) {
                return $next($request);
            }
        }

        AuditLog::record(
            'access_denied',
            implode(',', $needed),
            'Intento bloqueado por Matriz RBAC para rol ' . $user->normalizedRole(),
            $user->id,
            $user->email,
            403
        );

        return response()->json([
            'ok' => false,
            'message' => 'No cuentas con los permisos necesarios en tu perfil para acceder a este módulo.',
        ], 403);
    }
}
