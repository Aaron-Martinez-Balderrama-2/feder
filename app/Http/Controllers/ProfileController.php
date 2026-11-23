<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\File;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'id_document' => $user->id_document,
                'phone' => $user->phone,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'gender' => $user->gender,
                'avatar' => $user->avatar ?: $user->profile_picture,
                'profile_picture' => $user->profile_picture,
            ],
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->id_document = $validated['id_document'] ?? null;
        $user->phone = $validated['phone'] ?? null;
        $user->birth_date = $validated['birth_date'] ?? null;
        $user->gender = $validated['gender'] ?? null;

        // Gestión de foto de perfil
        if (!empty($validated['remove_picture'])) {
            if ($user->profile_picture && File::exists(public_path($user->profile_picture))) {
                File::delete(public_path($user->profile_picture));
            }
            $user->profile_picture = null;
            $user->avatar = null;
        } elseif (!empty($validated['profile_picture_base64']) && preg_match('/^data:image\/(\w+);base64,/', $validated['profile_picture_base64'])) {
            $data = substr($validated['profile_picture_base64'], strpos($validated['profile_picture_base64'], ',') + 1);
            $decoded = base64_decode($data);

            if ($decoded !== false) {
                $dir = public_path('uploads/users');
                if (!File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }

                $filename = 'user_' . $user->id . '_' . time() . '.jpg';
                $dest = $dir . DIRECTORY_SEPARATOR . $filename;

                if (file_put_contents($dest, $decoded)) {
                    if ($user->profile_picture && File::exists(public_path($user->profile_picture))) {
                        File::delete(public_path($user->profile_picture));
                    }
                    $user->profile_picture = 'uploads/users/' . $filename;
                    $user->avatar = 'uploads/users/' . $filename;
                }
            }
        }

        $user->save();

        return back()->with('success', 'Perfil actualizado correctamente.');
    }
}
