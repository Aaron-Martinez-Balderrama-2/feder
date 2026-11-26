<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MenuItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserRbacApiController extends Controller
{
    // =========================================================================
    // CU1: GESTIÓN DE USUARIOS (Participante, Administrador, Director Ejecutivo, Instructor) + FOTOS
    // =========================================================================
    public function indexUsers(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $role = trim((string) $request->input('role', ''));

        $query = User::with('entrepreneurProfile:id,user_id,business_name,sector');

        if ($search !== '') {
            $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($b) use ($search, $like) {
                $b->where('name', $like, "%{$search}%")
                  ->orWhere('email', $like, "%{$search}%")
                  ->orWhere('id_document', $like, "%{$search}%");
            });
        }

        if ($role !== '') {
            if ($role === 'participant') {
                $query->whereIn('role', ['participant', 'student']);
            } else {
                $query->where('role', $role);
            }
        }

        $users = $query->orderByDesc('id')->paginate(10)->withQueryString();
        $roles = Role::orderBy('id')->get(['id', 'slug', 'name', 'description']);

        return response()->json([
            'ok' => true,
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:admin,director,instructor,participant'],
            'id_document' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'city' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'profile_picture_base64' => ['nullable', 'string'],
        ]);

        $roleModel = Role::where('slug', $validated['role'])->first();

        $user = User::create([
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'password' => $validated['password'],
            'role' => $validated['role'],
            'role_id' => $roleModel?->id,
            'id_document' => $validated['id_document'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'city' => $validated['city'] ?? 'Santa Cruz de la Sierra',
            'gender' => $validated['gender'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['profile_picture_base64'])) {
            $savedPath = AuthApiController::saveBase64Image($validated['profile_picture_base64'], 'uploads/users', 'user_' . $user->id);
            if ($savedPath) {
                $user->update(['profile_picture' => $savedPath, 'avatar' => $savedPath]);
            }
        }

        AuditLog::record('create', 'users', "Creación de usuario #{$user->id} ({$user->email}) con rol {$user->role}");

        return response()->json([
            'ok' => true,
            'user' => $user->fresh(),
            'message' => "Usuario «{$user->name}» registrado correctamente.",
        ], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:admin,director,instructor,participant'],
            'id_document' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'city' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'profile_picture_base64' => ['nullable', 'string'],
            'remove_picture' => ['nullable', 'boolean'],
        ]);

        // Evitar que el administrador activo se desactive o degrade a sí mismo accidentalmente
        if ($request->user()->id === $user->id && (!$validated['is_active'] || $validated['role'] !== 'admin')) {
            return response()->json([
                'ok' => false,
                'message' => 'Por seguridad no puedes quitar el rol de Administrador ni desactivar tu propia cuenta en uso.',
            ], 422);
        }

        $roleModel = Role::where('slug', $validated['role'])->first();

        $user->name = $validated['name'];
        $user->email = mb_strtolower($validated['email']);
        $user->role = $validated['role'];
        $user->role_id = $roleModel?->id;
        $user->id_document = $validated['id_document'] ?? $user->id_document;
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->birth_date = $validated['birth_date'] ?? $user->birth_date;
        $user->city = $validated['city'] ?? $user->city;
        $user->gender = $validated['gender'] ?? $user->gender;
        $user->is_active = $validated['is_active'];

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        if (!empty($validated['remove_picture'])) {
            if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
                @unlink(public_path($user->profile_picture));
            }
            $user->profile_picture = null;
            $user->avatar = null;
        } elseif (!empty($validated['profile_picture_base64'])) {
            $savedPath = AuthApiController::saveBase64Image($validated['profile_picture_base64'], 'uploads/users', 'user_' . $user->id);
            if ($savedPath) {
                if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
                    @unlink(public_path($user->profile_picture));
                }
                $user->profile_picture = $savedPath;
                $user->avatar = $savedPath;
            }
        }

        $user->save();
        AuditLog::record('update', 'users', "Actualización de usuario #{$user->id} ({$user->email}) -> rol {$user->role}");

        return response()->json([
            'ok' => true,
            'user' => $user->fresh(),
            'message' => "Datos de «{$user->name}» actualizados correctamente.",
        ]);
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar tu propia cuenta activa.',
            ], 422);
        }

        $name = $user->name;
        if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
            @unlink(public_path($user->profile_picture));
        }
        $user->delete();

        AuditLog::record('delete', 'users', "Eliminación de usuario {$name}");

        return response()->json([
            'ok' => true,
            'message' => "El usuario «{$name}» fue eliminado del sistema.",
        ]);
    }

    // =========================================================================
    // MATRIZ RBAC, MENÚ DINÁMICO DESDE BD Y AJUSTES INSTITUCIONALES
    // =========================================================================
    public function rbacMatrix(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'roles' => Role::with('permissions:id,slug')->orderBy('id')->get(),
            'permissions' => Permission::orderBy('module')->orderBy('id')->get(),
            'menus' => MenuItem::orderBy('sort_order')->orderBy('id')->get(),
            'settings' => Setting::pluck('setting_value', 'setting_key')->toArray(),
        ]);
    }

    public function updateRolePermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        if ($role->slug === 'admin') {
            $role->permissions()->sync(Permission::pluck('id')->all());
        } else {
            $role->permissions()->sync($validated['permission_ids']);
        }

        AuditLog::record('update', 'rbac_matrix', "Actualización de permisos del rol {$role->name}");

        return response()->json([
            'ok' => true,
            'role' => $role->load('permissions:id,slug'),
            'message' => "Permisos del rol «{$role->name}» actualizados correctamente.",
        ]);
    }

    public function storeMenu(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'route_path' => ['required', 'string', 'max:140'],
            'icon' => ['nullable', 'string', 'max:40'],
            'permission_slug' => ['nullable', 'string', 'max:80'],
            'section' => ['required', 'string', 'in:main,workspace,management'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ]);

        $menu = MenuItem::create([
            'label' => $validated['label'],
            'route_path' => $validated['route_path'],
            'icon' => $validated['icon'] ?: 'spark',
            'permission_slug' => $validated['permission_slug'] ?: null,
            'section' => $validated['section'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $validated['is_active'],
        ]);

        AuditLog::record('create', 'menu_items', "Nuevo ítem de menú dinámico: {$menu->label}");

        return response()->json([
            'ok' => true,
            'menu' => $menu,
            'message' => 'Enlace agregado al menú dinámico.',
        ], 201);
    }

    public function updateMenu(Request $request, MenuItem $menuItem): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'route_path' => ['required', 'string', 'max:140'],
            'icon' => ['nullable', 'string', 'max:40'],
            'permission_slug' => ['nullable', 'string', 'max:80'],
            'section' => ['required', 'string', 'in:main,workspace,management'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ]);

        $menuItem->update([
            'label' => $validated['label'],
            'route_path' => $validated['route_path'],
            'icon' => $validated['icon'] ?: 'spark',
            'permission_slug' => $validated['permission_slug'] ?: null,
            'section' => $validated['section'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $validated['is_active'],
        ]);

        AuditLog::record('update', 'menu_items', "Edición de menú dinámico: {$menuItem->label}");

        return response()->json([
            'ok' => true,
            'menu' => $menuItem,
            'message' => 'Menú dinámico actualizado.',
        ]);
    }

    public function destroyMenu(MenuItem $menuItem): JsonResponse
    {
        $label = $menuItem->label;
        $menuItem->delete();
        AuditLog::record('delete', 'menu_items', "Eliminación de menú dinámico: {$label}");

        return response()->json([
            'ok' => true,
            'message' => "Enlace «{$label}» eliminado del menú.",
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'institution_name' => ['required', 'string', 'max:180'],
            'institution_tagline' => ['nullable', 'string', 'max:220'],
            'office_address' => ['required', 'string', 'max:255'],
            'office_phone' => ['nullable', 'string', 'max:80'],
            'office_email' => ['nullable', 'email', 'max:120'],
            'office_hours' => ['nullable', 'string', 'max:180'],
            'office_map_embed' => ['required', 'string', 'max:120'],
            'certificate_header_title' => ['nullable', 'string', 'max:180'],
            'certificate_intro_text' => ['nullable', 'string', 'max:220'],
            'certificate_body_text' => ['nullable', 'string', 'max:600'],
            'certificate_authority' => ['nullable', 'string', 'max:160'],
            'certificate_authority_role' => ['nullable', 'string', 'max:160'],
            'certificate_secondary_authority' => ['nullable', 'string', 'max:160'],
            'certificate_secondary_role' => ['nullable', 'string', 'max:160'],
            'default_phases_json' => ['nullable', 'string', 'max:1200'],
        ]);

        foreach ($validated as $k => $v) {
            Setting::setVal($k, $v ?? '');
        }

        AuditLog::record('update', 'settings', 'Actualización de datos institucionales, plantilla de certificados y fases base');

        return response()->json([
            'ok' => true,
            'settings' => Setting::pluck('setting_value', 'setting_key')->toArray(),
            'message' => 'Configuración institucional guardada correctamente.',
        ]);
    }

    // =========================================================================
    // BITÁCORA DE AUDITORÍA Y TRÁFICO (INF513 Req 4)
    // =========================================================================
    public function auditLogs(Request $request): JsonResponse
    {
        $eventType = trim((string) $request->input('event_type', ''));
        $search = trim((string) $request->input('search', ''));

        $query = AuditLog::with('user:id,name,email,role');

        if ($eventType !== '') {
            $query->where('event_type', $eventType);
        }

        if ($search !== '') {
            $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($b) use ($search, $like) {
                $b->where('resource', $like, "%{$search}%")
                  ->orWhere('action_detail', $like, "%{$search}%")
                  ->orWhere('ip_address', $like, "%{$search}%")
                  ->orWhere('email_attempted', $like, "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('id')->paginate(10)->withQueryString();

        $summary = [
            'logins_success' => AuditLog::where('event_type', 'login_success')->count(),
            'logins_failed' => AuditLog::where('event_type', 'login_failed')->count(),
            'access_denied' => AuditLog::where('event_type', 'access_denied')->count(),
            'top_routes' => DB::table('page_visits')
                ->orderByDesc('hits_count')
                ->limit(6)
                ->get(['page_path', 'page_title', 'hits_count', 'last_visited_at']),
        ];

        return response()->json([
            'ok' => true,
            'logs' => $logs,
            'summary' => $summary,
        ]);
    }
}
