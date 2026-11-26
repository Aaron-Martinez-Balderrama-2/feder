<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiOptionalAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        ApiAuthMiddleware::resolveUserFromRequest($request);
        return $next($request);
    }
}
