<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\CourseModule;
use App\Models\CoursePrerequisite;
use App\Models\Enrollment;
use App\Models\SocialOrganization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CurriculumApiController extends Controller
{
    /**
     * Portada pública con cifras reales de base de datos (cero valores estáticos ficticios).
     */
    public function homeOverview(Request $request): JsonResponse
    {
        $featuredCourses = Course::with([
            'category:id,name,slug,icon',
            'instructor:id,name,profile_picture',
            'period:id,name,code',
            'prerequisites:id,title,slug',
        ])
            ->withCount(['enrollments' => fn ($q) => $q->whereIn('status', ['active', 'completed'])])
            ->where('status', 'published')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        $realStats = [
            'published_courses' => Course::where('status', 'published')->count(),
            'active_participants' => User::whereIn('role', ['participant', 'student'])->where('is_active', true)->count(),
            'total_enrollments' => Enrollment::whereIn('status', ['active', 'completed'])->count(),
            'certificates_issued' => Certificate::where('status', 'valid')->count(),
            'partner_organizations' => SocialOrganization::where('agreement_status', 'activo')->count(),
        ];

        return response()->json([
            'ok' => true,
            'featured_courses' => $featuredCourses,
            'stats' => $realStats,
            'default_phases' => Course::DEFAULT_PHASES,
        ]);
    }

    /**
     * Catálogo paginado de 10 en 10 con filtros y estado de prerrequisitos.
     */
    public function indexCatalog(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $categorySlug = trim((string) $request->input('category', ''));
        $level = trim((string) $request->input('level', ''));
        $periodId = $request->input('period_id');

        $query = Course::with([
            'category:id,name,slug,icon',
            'instructor:id,name,profile_picture',
            'period:id,name,code',
            'prerequisites:id,title,slug',
        ])
            ->withCount(['enrollments' => fn ($q) => $q->whereIn('status', ['active', 'completed'])])
            ->where('status', 'published');

        if ($search !== '') {
            $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($b) use ($search, $like) {
                $b->where('title', $like, "%{$search}%")
                  ->orWhere('hook', $like, "%{$search}%")
                  ->orWhere('description', $like, "%{$search}%");
            });
        }

        if ($categorySlug !== '') {
            $query->whereHas('category', fn ($b) => $b->where('slug', $categorySlug));
        }

        if ($level !== '') {
            $query->where('level', $level);
        }

        if ($periodId) {
            $query->where('period_id', (int) $periodId);
        }

        $courses = $query->orderByDesc('id')->paginate(10)->withQueryString();

        $user = $request->user();
        $enrolledCourseIds = [];
        $completedCourseIds = [];

        if ($user) {
            $userEnrollments = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->get(['course_id', 'status']);
            $enrolledCourseIds = $userEnrollments->pluck('course_id')->all();
            $completedCourseIds = $userEnrollments->where('status', 'completed')->pluck('course_id')->all();

            $certIds = Certificate::where('user_id', $user->id)->where('status', 'valid')->pluck('course_id')->all();
            $completedCourseIds = array_values(array_unique(array_merge($completedCourseIds, $certIds)));
        }

        return response()->json([
            'ok' => true,
            'courses' => $courses,
            'enrolled_course_ids' => $enrolledCourseIds,
            'completed_course_ids' => $completedCourseIds,
        ]);
    }

    /**
     * Detalle público/autenticado de una oferta académica con sus fases, subfases y prerrequisitos.
     */
    public function showCourse(Request $request, string $slug): JsonResponse
    {
        $course = Course::with([
            'category:id,name,slug,icon',
            'instructor:id,name,email,profile_picture',
            'period:id,name,code',
            'prerequisites:id,title,slug',
            'modules.classes',
        ])
            ->withCount(['enrollments' => fn ($q) => $q->whereIn('status', ['active', 'completed'])])
            ->where('slug', $slug)
            ->firstOrFail();

        $user = $request->user();
        if ($course->status !== 'published' && (!$user || ($user->isParticipant() && !$user->isAdmin()))) {
            abort(404, 'Curso no encontrado.');
        }

        $enrollment = null;
        $missingPrerequisites = [];
        $paymentMethods = [];

        if ($user) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->first();

            foreach ($course->prerequisites as $prereq) {
                $done = Enrollment::where('user_id', $user->id)
                    ->where('course_id', $prereq->id)
                    ->where('status', 'completed')
                    ->exists()
                    || Certificate::where('user_id', $user->id)
                        ->where('course_id', $prereq->id)
                        ->where('status', 'valid')
                        ->exists();

                if (!$done) {
                    $missingPrerequisites[] = [
                        'id' => $prereq->id,
                        'title' => $prereq->title,
                        'slug' => $prereq->slug,
                    ];
                }
            }

            $paymentMethods = $user->paymentMethods()->orderByDesc('is_default')->get();
        }

        return response()->json([
            'ok' => true,
            'course' => $course,
            'available_seats' => $course->available_seats,
            'is_open' => $course->is_open,
            'enrollment' => $enrollment,
            'missing_prerequisites' => $missingPrerequisites,
            'payment_methods' => $paymentMethods,
        ]);
    }

    // =========================================================================
    // GESTIÓN ADMINISTRATIVA / DOCENTE DE CATEGORÍAS Y PERIODOS EDITABLES (CU3)
    // =========================================================================
    public function indexCategories(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'categories' => Category::withCount('courses')->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'icon' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        if (Category::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::lower(Str::random(4));
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'icon' => $validated['icon'] ?: 'spark',
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        AuditLog::record('create', 'categories', "Creación de categoría curricular: {$category->name}");

        return response()->json([
            'ok' => true,
            'category' => $category,
            'message' => 'Categoría creada correctamente.',
        ], 201);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'icon' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?: $category->icon,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        AuditLog::record('update', 'categories', "Actualización de categoría: {$category->name}");

        return response()->json([
            'ok' => true,
            'category' => $category,
            'message' => 'Categoría actualizada.',
        ]);
    }

    public function destroyCategory(Category $category): JsonResponse
    {
        $name = $category->name;
        $category->delete();
        AuditLog::record('delete', 'categories', "Eliminación de categoría: {$name}");

        return response()->json([
            'ok' => true,
            'message' => "Categoría «{$name}» eliminada.",
        ]);
    }

    public function indexPeriods(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'periods' => AcademicPeriod::withCount('courses')->orderByDesc('start_date')->get(),
        ]);
    }

    public function storePeriod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', 'unique:academic_periods,code'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['required', 'boolean'],
        ]);

        $period = AcademicPeriod::create($validated);
        AuditLog::record('create', 'academic_periods', "Nuevo periodo académico: {$period->name} ({$period->code})");

        return response()->json([
            'ok' => true,
            'period' => $period,
            'message' => 'Periodo / convocatoria registrado correctamente.',
        ], 201);
    }

    public function updatePeriod(Request $request, AcademicPeriod $period): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', 'unique:academic_periods,code,' . $period->id],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['required', 'boolean'],
        ]);

        $period->update($validated);
        AuditLog::record('update', 'academic_periods', "Edición de periodo académico: {$period->code}");

        return response()->json([
            'ok' => true,
            'period' => $period,
            'message' => 'Periodo académico actualizado.',
        ]);
    }

    public function destroyPeriod(AcademicPeriod $period): JsonResponse
    {
        $name = $period->name;
        $period->delete();
        AuditLog::record('delete', 'academic_periods', "Eliminación de periodo: {$name}");

        return response()->json([
            'ok' => true,
            'message' => "Periodo «{$name}» eliminado.",
        ]);
    }

    // =========================================================================
    // GESTIÓN DE OFERTAS / CURSOS, PRERREQUISITOS, FASES Y SUBFASES EDITABLES
    // =========================================================================
    public function manageCourses(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = trim((string) $request->input('search', ''));

        $query = Course::with([
            'category:id,name,slug',
            'instructor:id,name,email',
            'period:id,name,code',
            'prerequisites:id,title,slug',
        ])->withCount(['enrollments', 'modules', 'materials']);

        if ($user->isInstructor() && !$user->isAdmin() && !$user->isDirector()) {
            $query->where('instructor_id', $user->id);
        }

        if ($search !== '') {
            $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('title', $like, "%{$search}%");
        }

        return response()->json([
            'ok' => true,
            'courses' => $query->orderByDesc('id')->paginate(10)->withQueryString(),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug']),
            'periods' => AcademicPeriod::orderByDesc('start_date')->get(['id', 'name', 'code', 'is_active']),
            'instructors' => User::whereIn('role', ['instructor', 'admin', 'director'])->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'all_courses_list' => Course::orderBy('title')->get(['id', 'title', 'slug']),
            'default_phases' => Course::DEFAULT_PHASES,
        ]);
    }

    public function storeCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:4', 'max:160'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
            'period_id' => ['nullable', 'integer', 'exists:academic_periods,id'],
            'hook' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'outcome' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', 'in:inicial,intermedio,avanzado'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'installments_allowed' => ['nullable', 'integer', 'min:1', 'max:12'],
            'modality' => ['required', 'string', 'in:presencial,virtual,hibrida'],
            'classroom_location' => ['nullable', 'string', 'max:160'],
            'hours_duration' => ['required', 'integer', 'min:1', 'max:1000'],
            'requires_fair_participation' => ['nullable', 'boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'meet_url' => ['nullable', 'url', 'max:500'],
            'whatsapp_url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'cover_base64' => ['nullable', 'string'],
            'prerequisite_ids' => ['nullable', 'array'],
            'prerequisite_ids.*' => ['integer', 'exists:courses,id'],
            'phases' => ['nullable', 'array'],
            'phases.*' => ['string', 'max:160'],
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $slug = Str::slug($validated['title']);
            if (Course::where('slug', $slug)->exists()) {
                $slug .= '-' . Str::lower(Str::random(5));
            }

            $coverPath = null;
            if (!empty($validated['cover_base64'])) {
                $coverPath = AuthApiController::saveBase64Image($validated['cover_base64'], 'uploads/courses', 'course');
            }

            $course = Course::create([
                'category_id' => $validated['category_id'],
                'instructor_id' => $validated['instructor_id'] ?? $request->user()->id,
                'period_id' => $validated['period_id'] ?? null,
                'title' => $validated['title'],
                'slug' => $slug,
                'hook' => $validated['hook'],
                'description' => $validated['description'],
                'outcome' => $validated['outcome'],
                'level' => $validated['level'],
                'capacity' => $validated['capacity'],
                'price' => $validated['price'] ?? 0,
                'installments_allowed' => $validated['installments_allowed'] ?? 1,
                'modality' => $validated['modality'],
                'classroom_location' => $validated['classroom_location'] ?? null,
                'hours_duration' => $validated['hours_duration'],
                'requires_fair_participation' => (bool) ($validated['requires_fair_participation'] ?? false),
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'color' => '#F7780F',
                'cover_image' => $coverPath,
                'meet_url' => $validated['meet_url'] ?? null,
                'whatsapp_url' => $validated['whatsapp_url'] ?? null,
                'status' => $validated['status'],
            ]);

            // Fases iniciales: usa la lista enviada o las fases base configuradas (100% editables)
            $configuredPhasesRaw = \App\Models\Setting::getVal('default_phases_json', '');
            $configuredPhases = json_decode((string) $configuredPhasesRaw, true);
            $fallbackPhases = (is_array($configuredPhases) && count($configuredPhases) > 0)
                ? array_values(array_filter($configuredPhases))
                : Course::DEFAULT_PHASES;
            $phaseNames = !empty($validated['phases']) ? array_values(array_filter($validated['phases'])) : $fallbackPhases;
            $prevId = null;
            $weight = count($phaseNames) > 0 ? (int) floor(100 / count($phaseNames)) : 25;

            foreach ($phaseNames as $idx => $pTitle) {
                $mod = CourseModule::create([
                    'course_id' => $course->id,
                    'title' => $pTitle,
                    'description' => "Módulo práctico de {$pTitle}.",
                    'position' => $idx + 1,
                    'prerequisite_module_id' => $prevId,
                    'weight_pct' => $weight,
                ]);
                $prevId = $mod->id;
            }

            // Prerrequisitos bloqueantes (CU3)
            if (!empty($validated['prerequisite_ids'])) {
                foreach (array_unique($validated['prerequisite_ids']) as $pid) {
                    if ((int) $pid !== $course->id) {
                        CoursePrerequisite::create([
                            'course_id' => $course->id,
                            'prerequisite_course_id' => (int) $pid,
                            'is_mandatory' => true,
                        ]);
                    }
                }
            }

            AuditLog::record('create', 'courses', "Creación de oferta curricular #{$course->id}: {$course->title}");

            return response()->json([
                'ok' => true,
                'course' => $course->load(['modules', 'prerequisites:id,title,slug']),
                'message' => "Curso «{$course->title}» creado con sus fases configuradas.",
            ], 201);
        });
    }

    public function updateCourse(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourseOperation($request->user(), $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:4', 'max:160'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
            'period_id' => ['nullable', 'integer', 'exists:academic_periods,id'],
            'hook' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'outcome' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', 'in:inicial,intermedio,avanzado'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'installments_allowed' => ['nullable', 'integer', 'min:1', 'max:12'],
            'modality' => ['required', 'string', 'in:presencial,virtual,hibrida'],
            'classroom_location' => ['nullable', 'string', 'max:160'],
            'hours_duration' => ['required', 'integer', 'min:1', 'max:1000'],
            'requires_fair_participation' => ['nullable', 'boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'meet_url' => ['nullable', 'url', 'max:500'],
            'whatsapp_url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'cover_base64' => ['nullable', 'string'],
            'prerequisite_ids' => ['nullable', 'array'],
            'prerequisite_ids.*' => ['integer', 'exists:courses,id'],
        ]);

        if (!empty($validated['cover_base64'])) {
            $coverPath = AuthApiController::saveBase64Image($validated['cover_base64'], 'uploads/courses', 'course_' . $course->id);
            if ($coverPath) {
                $course->cover_image = $coverPath;
            }
        }

        $course->fill([
            'category_id' => $validated['category_id'],
            'instructor_id' => $validated['instructor_id'] ?? $course->instructor_id,
            'period_id' => $validated['period_id'] ?? null,
            'title' => $validated['title'],
            'hook' => $validated['hook'],
            'description' => $validated['description'],
            'outcome' => $validated['outcome'],
            'level' => $validated['level'],
            'capacity' => $validated['capacity'],
            'price' => $validated['price'] ?? 0,
            'installments_allowed' => $validated['installments_allowed'] ?? 1,
            'modality' => $validated['modality'],
            'classroom_location' => $validated['classroom_location'] ?? null,
            'hours_duration' => $validated['hours_duration'],
            'requires_fair_participation' => (bool) ($validated['requires_fair_participation'] ?? false),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'meet_url' => $validated['meet_url'] ?? null,
            'whatsapp_url' => $validated['whatsapp_url'] ?? null,
            'status' => $validated['status'],
        ])->save();

        if (array_key_exists('prerequisite_ids', $validated)) {
            CoursePrerequisite::where('course_id', $course->id)->delete();
            foreach (array_unique($validated['prerequisite_ids'] ?? []) as $pid) {
                if ((int) $pid !== $course->id) {
                    CoursePrerequisite::create([
                        'course_id' => $course->id,
                        'prerequisite_course_id' => (int) $pid,
                        'is_mandatory' => true,
                    ]);
                }
            }
        }

        AuditLog::record('update', 'courses', "Actualización de curso #{$course->id}: {$course->title}");

        return response()->json([
            'ok' => true,
            'course' => $course->load(['category', 'instructor:id,name', 'period', 'prerequisites:id,title,slug']),
            'message' => 'Datos de la oferta académica actualizados correctamente.',
        ]);
    }

    public function destroyCourse(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourseOperation($request->user(), $course);
        $title = $course->title;
        $course->delete();

        AuditLog::record('delete', 'courses', "Eliminación de curso: {$title}");

        return response()->json([
            'ok' => true,
            'message' => "El curso «{$title}» fue eliminado.",
        ]);
    }

    // =========================================================================
    // FASES EDITABLES (Aumentar, Disminuir, Renombrar o Reordenar las 4 Fases)
    // =========================================================================
    public function storeModule(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourseOperation($request->user(), $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'position' => ['nullable', 'integer', 'min:1', 'max:99'],
            'prerequisite_module_id' => ['nullable', 'integer', 'exists:course_modules,id'],
            'weight_pct' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $nextPos = $validated['position'] ?? (($course->modules()->max('position') ?? 0) + 1);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'position' => $nextPos,
            'prerequisite_module_id' => $validated['prerequisite_module_id'] ?? null,
            'weight_pct' => $validated['weight_pct'] ?? 25,
        ]);

        AuditLog::record('create', 'course_modules', "Nueva fase agregada al curso #{$course->id}: {$module->title}");

        return response()->json([
            'ok' => true,
            'module' => $module->load('classes'),
            'message' => "Fase «{$module->title}» agregada al curso.",
        ], 201);
    }

    public function updateModule(Request $request, CourseModule $module): JsonResponse
    {
        $this->authorizeCourseOperation($request->user(), $module->course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'position' => ['required', 'integer', 'min:1', 'max:99'],
            'prerequisite_module_id' => ['nullable', 'integer', 'exists:course_modules,id'],
            'weight_pct' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        if (!empty($validated['prerequisite_module_id']) && (int) $validated['prerequisite_module_id'] === $module->id) {
            $validated['prerequisite_module_id'] = null;
        }

        $module->update($validated);
        AuditLog::record('update', 'course_modules', "Edición de fase #{$module->id}: {$module->title}");

        return response()->json([
            'ok' => true,
            'module' => $module->fresh('classes'),
            'message' => 'Fase actualizada correctamente.',
        ]);
    }

    public function toggleModuleComplete(Request $request, CourseModule $module): JsonResponse
    {
        $course = $module->course;
        $this->authorizeCourseOperation($request->user(), $course);

        $newState = !$module->is_completed;
        $module->update([
            'is_completed' => $newState,
            'completed_at' => $newState ? now() : null,
        ]);

        // Sincronizar también el estado de las subfases internas si el docente completó toda la fase
        if ($newState) {
            $module->classes()->where('is_completed', false)->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }

        $pct = $course->recalculateProgress();
        AuditLog::record(
            'update',
            'course_modules',
            "Fase «{$module->title}» marcada como " . ($newState ? 'completada (habilita siguiente fase)' : 'en curso')
        );

        return response()->json([
            'ok' => true,
            'module' => $module->fresh('classes'),
            'progress_pct' => $pct,
            'message' => $newState
                ? "Fase «{$module->title}» marcada como completada. Siguiente fase habilitada para los participantes que aprobaron."
                : "Fase «{$module->title}» marcada nuevamente como en progreso.",
        ]);
    }

    public function destroyModule(Request $request, CourseModule $module): JsonResponse
    {
        $course = $module->course;
        $this->authorizeCourseOperation($request->user(), $course);

        $title = $module->title;
        $module->delete();
        $course->recalculateProgress();

        AuditLog::record('delete', 'course_modules', "Eliminación de fase «{$title}» en curso #{$course->id}");

        return response()->json([
            'ok' => true,
            'progress_pct' => $course->progress_pct,
            'message' => "La fase «{$title}» fue quitada del curso.",
        ]);
    }

    // =========================================================================
    // SUBFASES / CLASES EDITABLES DENTRO DE CADA FASE
    // =========================================================================
    public function storeClass(Request $request, CourseModule $module): JsonResponse
    {
        $course = $module->course;
        $this->authorizeCourseOperation($request->user(), $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
            'summary_light_text' => ['nullable', 'string', 'max:3000'],
        ]);

        $nextPos = ($module->classes()->max('position') ?? 0) + 1;

        $cls = CourseClass::create([
            'module_id' => $module->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'position' => $nextPos,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'summary_light_text' => $validated['summary_light_text'] ?? null,
            'is_completed' => false,
        ]);

        $pct = $course->recalculateProgress();
        AuditLog::record('create', 'course_classes', "Nueva subfase/clase «{$cls->title}» en fase #{$module->id}");

        return response()->json([
            'ok' => true,
            'class' => $cls,
            'progress_pct' => $pct,
            'message' => 'Subfase / clase agregada correctamente.',
        ], 201);
    }

    public function updateClass(Request $request, CourseClass $class): JsonResponse
    {
        $course = $class->module->course;
        $this->authorizeCourseOperation($request->user(), $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'position' => ['nullable', 'integer', 'min:1', 'max:999'],
            'scheduled_at' => ['nullable', 'date'],
            'summary_light_text' => ['nullable', 'string', 'max:3000'],
        ]);

        $class->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'position' => $validated['position'] ?? $class->position,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'summary_light_text' => $validated['summary_light_text'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'class' => $class->fresh(),
            'message' => 'Subfase / clase actualizada.',
        ]);
    }

    public function toggleClassComplete(Request $request, CourseClass $class): JsonResponse
    {
        $module = $class->module;
        $course = $module->course;
        $this->authorizeCourseOperation($request->user(), $course);

        $newState = !$class->is_completed;
        $class->update([
            'is_completed' => $newState,
            'completed_at' => $newState ? now() : null,
        ]);

        // Si todas las subfases de la fase quedan dictadas, marcar automáticamente la fase como completada
        $totalInMod = $module->classes()->count();
        $doneInMod = $module->classes()->where('is_completed', true)->count();
        $moduleCompleted = $totalInMod > 0 && $totalInMod === $doneInMod;
        $module->update([
            'is_completed' => $moduleCompleted,
            'completed_at' => $moduleCompleted ? ($module->completed_at ?? now()) : null,
        ]);

        $pct = $course->recalculateProgress();

        return response()->json([
            'ok' => true,
            'class' => $class->fresh(),
            'module_completed' => $moduleCompleted,
            'progress_pct' => $pct,
            'message' => $newState ? 'Subfase marcada como dictada/avanzada.' : 'Subfase reabierta como pendiente.',
        ]);
    }

    public function destroyClass(Request $request, CourseClass $class): JsonResponse
    {
        $course = $class->module->course;
        $this->authorizeCourseOperation($request->user(), $course);

        $title = $class->title;
        $class->delete();
        $pct = $course->recalculateProgress();

        AuditLog::record('delete', 'course_classes', "Eliminación de subfase «{$title}»");

        return response()->json([
            'ok' => true,
            'progress_pct' => $pct,
            'message' => "Subfase «{$title}» eliminada.",
        ]);
    }

    public static function authorizeCourseOperation($user, Course $course): void
    {
        if ($user->isAdmin() || $user->isDirector()) {
            return;
        }
        if ($user->isInstructor() && (int) $course->instructor_id === (int) $user->id) {
            return;
        }
        abort(403, 'Solo el instructor asignado, dirección ejecutiva o administración pueden modificar este curso.');
    }
}
