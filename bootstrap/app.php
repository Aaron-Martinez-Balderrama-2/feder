<?php

use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\ApiOptionalAuthMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\SanitizeInputMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SanitizeInputMiddleware::class);

        $middleware->api(prepend: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->alias([
            'api.auth' => ApiAuthMiddleware::class,
            'api.optional' => ApiOptionalAuthMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage() ?: 'No se pudo completar la operación solicitada.',
                ], $e->getStatusCode());
            }
            return null;
        });
    })->create();
