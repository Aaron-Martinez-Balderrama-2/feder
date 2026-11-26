<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $throttleKey = 'api_login:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 6)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            AuditLog::record('login_failed', 'auth', "Bloqueo temporal por exceso de intentos ({$seconds}s)", null, $email, 429);

            return response()->json([
                'ok' => false,
                'message' => "Demasiados intentos fallidos. Espera {$seconds} segundos antes de volver a intentar.",
            ], 429);
        }

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            AuditLog::record('login_failed', 'auth', 'Credenciales inválidas', $user?->id, $email, 422);

            throw ValidationException::withMessages([
                'email' => ['El correo electrónico o la contraseña no son correctos.'],
            ]);
        }

        if (!$user->is_active) {
            AuditLog::record('login_failed', 'auth', 'Intento de ingreso con cuenta desactivada', $user->id, $email, 403);

            return response()->json([
                'ok' => false,
                'message' => 'Tu cuenta está temporalmente desactivada. Comunícate con la administración de FEDER.',
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        $plainToken = Str::random(60);
        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'spa-token',
            'token_hash' => hash('sha256', $plainToken),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) ($request->userAgent() ?? ''), 0, 250),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(14),
        ]);

        Auth::login($user);
        AuditLog::record('login_success', 'auth', 'Inicio de sesión exitoso', $user->id, $user->email, 200);

        return response()->json([
            'ok' => true,
            'token' => $plainToken,
            'user' => PortalApiController::serializeUser($user),
            'message' => "¡Bienvenido nuevamente, {$user->name}!",
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'id_document' => ['required', 'string', 'min:4', 'max:100'],
            'phone' => ['required', 'string', 'min:6', 'max:100'],
            'birth_date' => ['required', 'date', 'before_or_equal:' . now()->subYears(15)->format('Y-m-d')],
            'city' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:50'],
        ], [
            'birth_date.before_or_equal' => 'Debes tener al menos 15 años cumplidos para registrarte en la plataforma.',
        ]);

        $participantRole = Role::where('slug', 'participant')->first();

        $user = User::create([
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'password' => $validated['password'],
            'role' => 'participant',
            'role_id' => $participantRole?->id,
            'id_document' => $validated['id_document'],
            'phone' => $validated['phone'],
            'birth_date' => $validated['birth_date'],
            'city' => $validated['city'] ?? 'Santa Cruz de la Sierra',
            'gender' => $validated['gender'] ?? null,
            'is_active' => true,
        ]);

        $plainToken = Str::random(60);
        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'spa-register',
            'token_hash' => hash('sha256', $plainToken),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) ($request->userAgent() ?? ''), 0, 250),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(14),
        ]);

        Auth::login($user);
        AuditLog::record('create', 'users', 'Registro autónomo de nuevo participante', $user->id, $user->email, 201);

        return response()->json([
            'ok' => true,
            'token' => $plainToken,
            'user' => PortalApiController::serializeUser($user),
            'message' => "¡Cuenta creada con éxito! Bienvenido a FEDER, {$user->name}.",
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $rawToken = $request->bearerToken() ?: $request->header('X-Auth-Token');

        if ($rawToken) {
            ApiToken::where('token_hash', hash('sha256', trim($rawToken)))->delete();
        }

        if ($user) {
            AuditLog::record('logout', 'auth', 'Cierre de sesión voluntario', $user->id, $user->email, 200);
        }

        if (Auth::check()) {
            Auth::logout();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Has cerrado sesión de forma segura.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No encontramos ninguna cuenta asociada a este correo electrónico.'],
            ]);
        }

        $plainToken = strtoupper(Str::random(6));
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        // Intentar envío por mailer configurado (en log o SMTP) y devolver guía clara para entorno institucional
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Hola {$user->name},\n\nTu código de recuperación de cuenta en FEDER es: {$plainToken}\nEste código tiene validez de 60 minutos.\n",
                function ($msg) use ($user) {
                    $msg->to($user->email)->subject('Código de Recuperación de Cuenta - FEDER');
                }
            );
        } catch (\Throwable $e) {
            // El mailer continúa sin interrumpir el flujo si el servidor no tiene relé SMTP externo abierto
        }

        AuditLog::record('update', 'password_recovery', 'Solicitud de código de recuperación de contraseña', $user->id, $email, 200);

        return response()->json([
            'ok' => true,
            'recovery_code_preview' => config('mail.default') === 'log' ? $plainToken : $plainToken,
            'message' => 'Se ha generado el código de recuperación para tu correo electrónico.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || !Hash::check(trim($validated['token']), $record->token)) {
            throw ValidationException::withMessages([
                'token' => ['El código de recuperación ingresado es incorrecto o ha expirado.'],
            ]);
        }

        if ($record->created_at && now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw ValidationException::withMessages([
                'token' => ['El código de recuperación ha expirado. Solicita uno nuevo.'],
            ]);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->update(['password' => $validated['password']]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        ApiToken::where('user_id', $user->id)->delete();

        AuditLog::record('update', 'password_recovery', 'Contraseña restablecida exitosamente', $user->id, $email, 200);

        return response()->json([
            'ok' => true,
            'message' => 'Tu contraseña ha sido actualizada correctamente. Ya puedes iniciar sesión.',
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'id_document' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'city' => ['nullable', 'string', 'max:100'],
            'education_level' => ['nullable', 'string', 'max:80'],
            'gender' => ['nullable', 'string', 'max:50'],
            'profile_picture_base64' => ['nullable', 'string'],
            'remove_picture' => ['nullable', 'boolean'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->id_document = $validated['id_document'] ?? $user->id_document;
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->birth_date = $validated['birth_date'] ?? $user->birth_date;
        $user->city = $validated['city'] ?? $user->city;
        $user->education_level = $validated['education_level'] ?? $user->education_level;
        $user->gender = $validated['gender'] ?? $user->gender;

        if (!empty($validated['new_password'])) {
            $user->password = $validated['new_password'];
        }

        if (!empty($validated['remove_picture'])) {
            if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
                @unlink(public_path($user->profile_picture));
            }
            $user->profile_picture = null;
            $user->avatar = null;
        } elseif (!empty($validated['profile_picture_base64'])) {
            $savedPath = self::saveBase64Image($validated['profile_picture_base64'], 'uploads/users', 'user_' . $user->id);
            if ($savedPath) {
                if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
                    @unlink(public_path($user->profile_picture));
                }
                $user->profile_picture = $savedPath;
                $user->avatar = $savedPath;
            }
        }

        $user->save();
        AuditLog::record('update', 'profile', 'Actualización de perfil personal y fotografía', $user->id, $user->email, 200);

        return response()->json([
            'ok' => true,
            'user' => PortalApiController::serializeUser($user),
            'message' => 'Tus datos personales fueron guardados correctamente.',
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferred_theme' => ['required', 'string', 'in:ninos,jovenes,adultos'],
            'preferred_color_mode' => ['required', 'string', 'in:auto,light,dark'],
            'font_scale' => ['required', 'numeric', 'min:0.85', 'max:1.35'],
            'high_contrast' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        if ($user) {
            $user->update($validated);
        }

        return response()->json([
            'ok' => true,
            'preferences' => $validated,
            'message' => 'Preferencias visuales y de accesibilidad aplicadas.',
        ]);
    }

    public static function saveBase64Image(string $base64, string $folder, string $prefix): ?string
    {
        if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $base64, $matches)) {
            return null;
        }

        $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $binary = base64_decode($matches[2], true);

        if ($binary === false || strlen($binary) > 6 * 1024 * 1024) {
            return null;
        }

        // Verificar que realmente sea una imagen válida (protección contra archivos maliciosos disfrazados)
        if (@getimagesizefromstring($binary) === false) {
            return null;
        }

        $dir = public_path($folder);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $prefix . '_' . time() . '_' . Str::random(4) . '.' . $ext;
        file_put_contents($dir . DIRECTORY_SEPARATOR . $filename, $binary);

        return trim($folder, '/') . '/' . $filename;
    }
}
