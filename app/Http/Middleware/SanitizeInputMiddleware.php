<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInputMiddleware
{
    /**
     * Campos que no deben alterarse (contraseñas o imágenes base64).
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'cover_base64',
        'profile_picture_base64',
        'file_base64',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        if (!empty($input)) {
            $request->merge($this->cleanArray($input));
        }

        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }

    protected function cleanArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array((string) $key, $this->except, true)) {
                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->cleanArray($value);
            } elseif (is_string($value)) {
                // Eliminar bytes nulos, etiquetas <script>/<iframe>/<object> y manejadores inline maliciosos
                $clean = str_replace("\0", '', $value);
                $clean = preg_replace('/<\s*(script|iframe|object|embed)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $clean) ?? $clean;
                $clean = preg_replace('/<\s*(script|iframe|object|embed)[^>]*\/?>/is', '', $clean) ?? $clean;
                $clean = preg_replace('/\bon\w+\s*=\s*(["\']).*?\1/is', '', $clean) ?? $clean;
                $clean = preg_replace('/javascript\s*:/i', '', $clean) ?? $clean;
                $data[$key] = trim($clean);
            }
        }
        return $data;
    }
}
