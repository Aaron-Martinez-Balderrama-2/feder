<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::orderBy('id', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id_document', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $users = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', ''),
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'role' => ['required', 'string', 'in:admin,instructor,student'],
            'id_document' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
        ]);

        // Guard against admin self-demoting or self-deactivating
        if ($currentUser->id === $user->id) {
            if ($data['role'] !== 'admin') {
                return back()->with('error', 'No puedes quitarte el rol de Administrador a ti mismo.');
            }
            if (!$data['is_active']) {
                return back()->with('error', 'No puedes desactivar tu propia cuenta de Administrador.');
            }
        }

        $user->update($data);

        return back()->with('success', "Usuario \"{$user->name}\" actualizado correctamente.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta de Administrador.');
        }

        // Unlink courses where this user is instructor to avoid breaking course history
        Course::where('instructor_id', $user->id)->update(['instructor_id' => null]);

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Usuario \"{$name}\" eliminado correctamente.");
    }
}
