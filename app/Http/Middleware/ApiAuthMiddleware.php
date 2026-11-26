<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    public static function resolveUserFromRequest(Request $request): ?User
    {
        $rawToken = $request->bearerToken() ?: $request->header('X-Auth-Token');

        if ($rawToken && is_string($rawToken)) {
            $hash = hash('sha256', trim($rawToken));
            $tokenRecord = ApiToken::with('user.roleModel.permissions')
                ->where('token_hash', $hash)
                ->first();

            if ($tokenRecord) {
                if ($tokenRecord->expires_at && $tokenRecord->expires_at->isPast()) {
                    $tokenRecord->delete();
                    return null;
                }

                $user = $tokenRecord->user;
                if ($user && $user->is_active) {
                    if (!$tokenRecord->last_used_at || $tokenRecord->last_used_at->diffInMinutes(now()) >= 5) {
                        $tokenRecord->update(['last_used_at' => now()]);
                    }
                    Auth::setUser($user);
                    $request->setUserResolver(fn () => $user);
                    return $user;
                }
            }
        }

        if (Auth::check()) {
            $sessionUser = Auth::user();
            if ($sessionUser && $sessionUser->is_active) {
                $request->setUserResolver(fn () => $sessionUser);
                return $sessionUser;
            }
        }

        return null;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = self::resolveUserFromRequest($request);

        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu sesión ha expirado o no has iniciado sesión. Ingresa nuevamente.',
            ], 401);
        }

        return $next($request);
    }
}
