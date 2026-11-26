<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\MenuItem;
use App\Models\PageVisit;
use App\Models\PageVisitLog;
use App\Models\Setting;
use App\Models\SocialOrganization;
use App\Models\UsageSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalApiController extends Controller
{
    /**
     * Inicializa el estado global de la SPA: menú dinámico desde BD según RBAC,
     * ajustes editables de sede, usuario autenticado y contador persistente de visitas.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $path = $this->normalizePath((string) $request->input('path', '/'));
        $title = trim((string) $request->input('title', 'Inicio · FEDER')) ?: 'FEDER';

        $visitData = $this->registerVisit($request, $path, $title);

        $user = $request->user();
        $permissions = $user ? $user->permissionSlugs() : [];

        // Menú dinámico por permisos RBAC solo para usuarios autenticados
        $menus = collect();
        if ($user) {
            $allMenus = MenuItem::where('is_active', true)
                ->whereNotIn('route_path', ['/', '/pagos'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $menus = $allMenus->filter(function (MenuItem $item) use ($user, $permissions) {
                if ($user->isAdmin()) {
                    return true;
                }
                if (empty($item->permission_slug)) {
                    return true;
                }
                return in_array($item->permission_slug, $permissions, true);
            })->values();
        }

        $settingsMap = Setting::pluck('setting_value', 'setting_key')->toArray();

        return response()->json([
            'ok' => true,
            'user' => $user ? $this->serializeUser($user, $permissions) : null,
            'menus' => $menus,
            'settings' => $settingsMap,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug', 'icon', 'description']),
            'periods' => AcademicPeriod::orderByDesc('is_active')->orderByDesc('start_date')->get(),
            'visits' => $visitData,
        ]);
    }

    /**
     * Registra acceso a cualquier ruta de la SPA y devuelve el contador actualizado del Footer.
     */
    public function visit(Request $request): JsonResponse
    {
        $path = $this->normalizePath((string) $request->input('path', '/'));
        $title = trim((string) $request->input('title', 'FEDER')) ?: 'FEDER';

        return response()->json([
            'ok' => true,
            'visits' => $this->registerVisit($request, $path, $title),
        ]);
    }

    /**
     * Búsqueda global predictiva en el Header (INF513 Req 9).
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([
                'ok' => true,
                'courses' => [],
                'classes' => [],
                'organizations' => [],
                'certificates' => [],
            ]);
        }

        $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $courses = Course::with('category:id,name,slug')
            ->where('status', 'published')
            ->where(function ($b) use ($q, $like) {
                $b->where('title', $like, "%{$q}%")
                  ->orWhere('hook', $like, "%{$q}%")
                  ->orWhere('description', $like, "%{$q}%");
            })
            ->take(6)
            ->get(['id', 'category_id', 'title', 'slug', 'hook', 'level', 'price', 'modality']);

        $classes = CourseClass::with('module.course:id,title,slug,status')
            ->where(function ($b) use ($q, $like) {
                $b->where('title', $like, "%{$q}%")
                  ->orWhere('description', $like, "%{$q}%");
            })
            ->take(5)
            ->get(['id', 'module_id', 'title'])
            ->filter(fn ($c) => $c->module?->course?->status === 'published')
            ->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'phase_title' => $c->module?->title,
                'course_title' => $c->module?->course?->title,
                'course_slug' => $c->module?->course?->slug,
            ])
            ->values();

        $organizations = SocialOrganization::where('name', $like, "%{$q}%")
            ->orWhere('municipality', $like, "%{$q}%")
            ->take(4)
            ->get(['id', 'name', 'municipality', 'organization_type']);

        $certificates = Certificate::with(['user:id,name', 'course:id,title'])
            ->where('status', 'valid')
            ->where('verification_code', $like, "%{$q}%")
            ->take(3)
            ->get()
            ->map(fn ($cert) => [
                'verification_code' => $cert->verification_code,
                'participant' => $cert->user?->name,
                'course' => $cert->course?->title,
            ]);

        return response()->json([
            'ok' => true,
            'courses' => $courses,
            'classes' => $classes,
            'organizations' => $organizations,
            'certificates' => $certificates,
        ]);
    }

    /**
     * Registra pulsos reales de tiempo activo de uso en plataforma y curso (CU4 / requerimiento cliente).
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $user = $request->user();
        $seconds = min(120, max(10, (int) $request->input('seconds', 30)));
        $courseId = $request->input('course_id') ? (int) $request->input('course_id') : null;
        $lastPath = $this->normalizePath((string) $request->input('path', '/'));
        $today = now()->format('Y-m-d');

        if ($courseId && !Course::where('id', $courseId)->exists()) {
            $courseId = null;
        }

        $session = UsageSession::where('user_id', $user->id)
            ->whereDate('session_date', $today)
            ->where(function ($q) use ($courseId) {
                if ($courseId === null) {
                    $q->whereNull('course_id');
                } else {
                    $q->where('course_id', $courseId);
                }
            })
            ->first();

        if ($session) {
            $session->update([
                'seconds_spent' => $session->seconds_spent + $seconds,
                'pings_count' => $session->pings_count + 1,
                'last_path' => $lastPath,
                'last_ping_at' => now(),
            ]);
        } else {
            $session = UsageSession::create([
                'user_id' => $user->id,
                'course_id' => $courseId,
                'session_date' => $today,
                'seconds_spent' => $seconds,
                'pings_count' => 1,
                'last_path' => $lastPath,
                'last_ping_at' => now(),
            ]);
        }

        $user->refresh();

        return response()->json([
            'ok' => true,
            'session_seconds_today' => $session->seconds_spent,
            'total_active_seconds' => (int) $user->total_active_seconds,
        ]);
    }

    protected function registerVisit(Request $request, string $path, string $title): array
    {
        // Insertar en page_visit_logs dispara automáticamente el Trigger PostgreSQL trg_increment_page_visits
        PageVisitLog::create([
            'page_path' => $path,
            'page_title' => mb_substr($title, 0, 155),
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) ($request->userAgent() ?? ''), 0, 250),
            'visited_at' => now(),
        ]);

        $pageRow = PageVisit::find($path);
        $totalHits = (int) PageVisit::sum('hits_count');

        return [
            'page_path' => $path,
            'page_hits' => (int) ($pageRow?->hits_count ?? 1),
            'total_hits' => max(1, $totalHits),
        ];
    }

    protected function normalizePath(string $rawPath): string
    {
        $clean = '/' . trim(parse_url($rawPath, PHP_URL_PATH) ?: '/', '/');
        if ($clean === '//') {
            $clean = '/';
        }
        return mb_substr($clean, 0, 175);
    }

    public static function serializeUser($user, ?array $permissions = null): array
    {
        $perms = $permissions ?? $user->permissionSlugs();
        $theme = $user->preferred_theme ?: 'adultos';
        if ($theme === 'ninos') {
            $theme = 'adultos';
        }
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->normalizedRole(),
            'role_id' => $user->role_id,
            'id_document' => $user->id_document,
            'phone' => $user->phone,
            'birth_date' => $user->birth_date?->format('Y-m-d'),
            'city' => $user->city,
            'education_level' => $user->education_level,
            'gender' => $user->gender,
            'profile_picture' => $user->profile_picture,
            'initials' => $user->initials,
            'is_active' => (bool) $user->is_active,
            'total_active_seconds' => (int) ($user->total_active_seconds ?? 0),
            'preferred_theme' => $theme,
            'preferred_color_mode' => $user->preferred_color_mode ?: 'auto',
            'font_scale' => (float) ($user->font_scale ?: 1.0),
            'high_contrast' => (bool) $user->high_contrast,
            'permissions' => $perms,
        ];
    }
}
