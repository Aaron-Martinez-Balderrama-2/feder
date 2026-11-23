<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'id_document' => ['required', 'string', 'min:5', 'max:100'],
            'phone' => ['required', 'string', 'min:5', 'max:100'],
            'birth_date' => [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            ],
            'avatar' => ['nullable', 'string'],
        ], [
            'birth_date.before_or_equal' => 'Debes tener al menos 18 años para participar.',
            'email.unique' => 'Ya existe una cuenta con este correo.',
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'role' => 'student',
            'id_document' => $request->id_document,
            'phone' => $request->phone,
            'birth_date' => $request->birth_date,
            'avatar' => $request->avatar ?: 'aurora',
            'is_active' => true,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "¡Bienvenido a Feder, {$user->name}! Tu cuenta está activa.");
    }
}
