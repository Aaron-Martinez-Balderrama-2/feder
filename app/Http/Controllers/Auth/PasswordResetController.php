<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    /**
     * Show the request password reset form.
     */
    public function showForgotPassword(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
            'directResetUrl' => session('direct_reset_url'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa una dirección de correo válida.',
            'email.exists' => 'No encontramos ninguna cuenta registrada con este correo.',
        ]);

        $plainToken = Str::random(60);

        // Store hashed token in database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset', [
            'token' => $plainToken,
            'email' => $request->email,
        ]);

        // Try sending an email if mail driver is active
        try {
            Mail::raw(
                "Hola,\n\nHas solicitado restablecer tu contraseña en Feder.\nHaz clic en el siguiente enlace para crear una nueva contraseña:\n\n{$resetUrl}\n\nEste enlace caduca en 60 minutos.\nSi no solicitaste este cambio, puedes ignorar este mensaje.\n\nFeder - Educación Ejecutiva",
                function ($message) use ($request) {
                    $message->to($request->email)
                        ->subject('Recuperación de Contraseña - Feder Plataforma Educativa');
                }
            );
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el correo de recuperación vía SMTP: ' . $e->getMessage());
        }

        return back()
            ->with('status', 'Hemos generado el enlace para restablecer tu contraseña.')
            ->with('direct_reset_url', $resetUrl)
            ->with('success', 'Enlace de recuperación generado. Puedes continuar con el restablecimiento.');
    }

    /**
     * Show the password reset form with token.
     */
    public function showResetPassword(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Handle updating the password.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'token.required' => 'El token de recuperación es inválido.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un formato de correo válido.',
            'email.exists' => 'No encontramos una cuenta con ese correo.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return back()->withErrors([
                'email' => 'El enlace de recuperación es inválido o ya ha sido utilizado.',
            ]);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return back()->withErrors([
                'email' => 'El enlace de recuperación ha expirado. Por favor, solicita uno nuevo.',
            ]);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password = $request->password;
            $user->save();
        }

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with(
            'success',
            '¡Tu contraseña ha sido restablecida exitosamente! Inicia sesión con tus nuevas credenciales.'
        );
    }
}
