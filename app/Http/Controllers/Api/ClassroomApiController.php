<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAttendance;
use App\Models\CourseClass;
use App\Models\CourseMaterial;
use App\Models\Enrollment;
use App\Models\EvaluationAttempt;
use App\Models\FairParticipation;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\ModuleEvaluation;
use App\Models\UsageSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassroomApiController extends Controller
{
    /**
     * Panel personal del participante / docente con sus cursos activos y certificados.
     */
    public function myWorkspace(Request $request): JsonResponse
    {
        $user = $request->user();

        $enrollments = Enrollment::with([
            'course.category:id,name,icon',
            'course.instructor:id,name',
            'course.period:id,name,code',
        ])
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->orderByDesc('enrolled_at')
            ->get();

        $instructedCourses = [];
        if ($user->isInstructor() || $user->isAdmin() || $user->isDirector()) {
            $q = Course::with(['category:id,name', 'period:id,name,code'])
                ->withCount(['enrollments' => fn ($b) => $b->whereIn('status', ['active', 'completed'])]);
            if ($user->isInstructor() && !$user->isAdmin() && !$user->isDirector()) {
                $q->where('instructor_id', $user->id);
            }
            $instructedCourses = $q->orderByDesc('id')->get();
        }

        $certificates = Certificate::with(['course:id,title,slug,hours_duration', 'issuer:id,name'])
            ->where('user_id', $user->id)
            ->where('status', 'valid')
            ->orderByDesc('issued_at')
            ->get();

        return response()->json([
            'ok' => true,
            'enrollments' => $enrollments,
            'instructed_courses' => $instructedCourses,
            'certificates' => $certificates,
            'total_active_seconds' => (int) ($user->total_active_seconds ?? 0),
        ]);
    }

    /**
     * CU4: Aula Virtual Integral optimizada para baja conectividad, con materiales por fase/subfase,
     * foros por clase, evaluaciones automáticas, feria, asistencias, tiempo de uso y certificados.
     */
    public function showClassroom(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        $canManage = $user->isAdmin() || $user->isDirector() || ($user->isInstructor() && (int) $course->instructor_id === (int) $user->id);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (!$canManage && !$enrollment) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes estar inscrito en este curso para ingresar al Aula Virtual.',
            ], 403);
        }

        if (!$canManage && $course->end_date && $course->end_date->copy()->endOfDay()->isPast() && $enrollment?->status !== 'completed') {
            return response()->json([
                'ok' => false,
                'message' => 'Esta capacitación concluyó su fecha límite y cerró su aula activa.',
            ], 403);
        }

        $course->load([
            'category:id,name,slug,icon',
            'instructor:id,name,email,profile_picture',
            'period:id,name,code',
            'modules.classes',
            'materials.uploader:id,name',
        ]);

        // RF-02.1 & RF-02.2: Bloqueo secuencial estricto de Fases para Participantes.
        $accessState = self::resolveModuleAccess($course, $user->id, $canManage);
        $unlockedModuleIds = $accessState['unlocked_ids'];
        $moduleAccessMap = $accessState['map'];

        // Evaluaciones del curso: ocultar evaluaciones de fases bloqueadas a participantes
        $evaluations = ModuleEvaluation::with('questions')
            ->where('course_id', $course->id)
            ->when(!$canManage, fn ($q) => $q->where('is_published', true))
            ->orderBy('module_id')
            ->get()
            ->filter(fn ($ev) => $canManage || !$ev->module_id || in_array((int) $ev->module_id, $unlockedModuleIds, true))
            ->values()
            ->map(function ($ev) use ($user, $canManage) {
                $myAttempts = EvaluationAttempt::where('evaluation_id', $ev->id)
                    ->where('user_id', $user->id)
                    ->orderByDesc('submitted_at')
                    ->get();

                return [
                    'id' => $ev->id,
                    'course_id' => $ev->course_id,
                    'module_id' => $ev->module_id,
                    'title' => $ev->title,
                    'instructions' => $ev->instructions,
                    'passing_score' => $ev->passing_score,
                    'max_attempts' => $ev->max_attempts,
                    'is_published' => $ev->is_published,
                    'questions' => $ev->questions->map(fn ($q) => [
                        'id' => $q->id,
                        'question_text' => $q->question_text,
                        'question_type' => $q->question_type,
                        'options' => $q->options,
                        'points' => $q->points,
                        'correct_option' => $canManage ? $q->correct_option : null,
                        'explanation' => $canManage ? $q->explanation : null,
                    ]),
                    'my_attempts' => $myAttempts,
                    'best_percentage' => $myAttempts->max('percentage') ?? null,
                    'has_passed' => $myAttempts->contains('passed', true),
                ];
            });

        // Filtrar materiales y subfases para que un estudiante NO pueda acceder al contenido ni enlaces de fases bloqueadas
        if (!$canManage) {
            $visibleMaterials = $course->materials
                ->filter(fn ($m) => !$m->module_id || in_array((int) $m->module_id, $unlockedModuleIds, true))
                ->values();
            $course->setRelation('materials', $visibleMaterials);

            $filteredModules = $course->modules->map(function ($mod) use ($moduleAccessMap) {
                $info = $moduleAccessMap[$mod->id] ?? ['is_unlocked' => false, 'lock_reason' => 'Fase bloqueada'];
                $mod->setAttribute('is_unlocked', $info['is_unlocked']);
                $mod->setAttribute('lock_reason', $info['lock_reason']);
                if (!$info['is_unlocked']) {
                    // Ocultar contenido detallado y enlaces de subfases mientras la fase siga bloqueada
                    $mod->setRelation('classes', collect());
                }
                return $mod;
            });
            $course->setRelation('modules', $filteredModules);
        } else {
            $staffModules = $course->modules->map(function ($mod) {
                $mod->setAttribute('is_unlocked', true);
                $mod->setAttribute('lock_reason', null);
                return $mod;
            });
            $course->setRelation('modules', $staffModules);
        }

        // Hilos de Foro del curso y sus respuestas
        $forumThreads = ForumThread::with([
            'user:id,name,role,profile_picture',
            'module:id,title',
            'courseClass:id,title',
            'replies.user:id,name,role,profile_picture',
        ])
            ->where('course_id', $course->id)
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        // Asistencias del usuario actual
        $classIds = $course->classes->pluck('id')->all();
        $myAttendances = CourseAttendance::where('user_id', $user->id)
            ->whereIn('class_id', $classIds)
            ->get()
            ->keyBy('class_id');

        // Feria y Certificado propios
        $myFair = FairParticipation::where('course_id', $course->id)->where('user_id', $user->id)->first();
        $myCertificate = Certificate::with('issuer:id,name')->where('course_id', $course->id)->where('user_id', $user->id)->first();

        // Tiempo de uso del participante en este curso
        $myCourseSeconds = (int) UsageSession::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->sum('seconds_spent');

        // Si es Instructor / Director / Admin: incluir nómina completa con asistencias, tiempo de uso, notas, feria y certificados
        $roster = [];
        $allFair = [];
        $allCertificates = [];

        if ($canManage) {
            $enrolledRows = Enrollment::with('user.entrepreneurProfile:id,user_id,business_name,sector')
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->orderBy('id')
                ->get();

            $totalClassesCount = max(1, count($classIds));

            foreach ($enrolledRows as $enr) {
                $stu = $enr->user;
                if (!$stu) {
                    continue;
                }

                $stuAttRecords = CourseAttendance::where('user_id', $stu->id)
                    ->whereIn('class_id', $classIds)
                    ->get();

                $presentCount = $stuAttRecords->whereIn('status', ['presente', 'tardanza', 'justificado'])->count();
                $attPct = count($classIds) > 0 ? (int) round(($presentCount / $totalClassesCount) * 100) : 100;

                // Promedio de mejores intentos en evaluaciones del curso
                $evalBestScores = [];
                foreach ($evaluations as $evData) {
                    $best = EvaluationAttempt::where('evaluation_id', $evData['id'])
                        ->where('user_id', $stu->id)
                        ->max('percentage');
                    if ($best !== null) {
                        $evalBestScores[] = (float) $best;
                    }
                }
                $avgEval = count($evalBestScores) > 0 ? round(array_sum($evalBestScores) / count($evalBestScores), 1) : null;

                $courseSeconds = (int) UsageSession::where('user_id', $stu->id)
                    ->where('course_id', $course->id)
                    ->sum('seconds_spent');

                $fairRec = FairParticipation::where('course_id', $course->id)->where('user_id', $stu->id)->first();
                $certRec = Certificate::where('course_id', $course->id)->where('user_id', $stu->id)->first();

                $roster[] = [
                    'enrollment_id' => $enr->id,
                    'enrollment_status' => $enr->status,
                    'enrolled_at' => $enr->enrolled_at?->format('Y-m-d'),
                    'user' => [
                        'id' => $stu->id,
                        'name' => $stu->name,
                        'email' => $stu->email,
                        'phone' => $stu->phone,
                        'id_document' => $stu->id_document,
                        'business_name' => $stu->entrepreneurProfile?->business_name,
                        'total_active_seconds' => (int) $stu->total_active_seconds,
                    ],
                    'course_active_seconds' => $courseSeconds,
                    'attendance_pct' => $attPct,
                    'attendance_by_class' => $stuAttRecords->pluck('status', 'class_id'),
                    'avg_evaluation_score' => $avgEval,
                    'fair' => $fairRec,
                    'certificate' => $certRec,
                ];
            }

            $allFair = FairParticipation::with('user:id,name,email')->where('course_id', $course->id)->get();
            $allCertificates = Certificate::with(['user:id,name,email', 'issuer:id,name'])->where('course_id', $course->id)->get();
        }

        return response()->json([
            'ok' => true,
            'can_manage' => $canManage,
            'course' => $course,
            'unlocked_module_ids' => $unlockedModuleIds,
            'enrollment' => $enrollment,
            'evaluations' => $evaluations,
            'forum_threads' => $forumThreads,
            'my_attendances' => $myAttendances,
            'my_fair' => $myFair,
            'my_certificate' => $myCertificate,
            'my_course_seconds' => $myCourseSeconds,
            'roster' => $roster,
            'all_fair' => $allFair,
            'all_certificates' => $allCertificates,
        ]);
    }

    // =========================================================================
    // MATERIALES POR CURSO, FASE Y SUBFASE + OPTIMIZACIÓN BAJA CONECTIVIDAD
    // =========================================================================
    public function storeMaterial(Request $request, Course $course): JsonResponse
    {
        CurriculumApiController::authorizeCourseOperation($request->user(), $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:150'],
            'module_id' => ['nullable', 'integer', 'exists:course_modules,id'],
            'class_id' => ['nullable', 'integer', 'exists:course_classes,id'],
            'resource_type' => ['required', 'string', 'in:documento,guia_ligera,plantilla,audio_ligero,enlace'],
            'external_url' => ['nullable', 'url', 'max:1000'],
            'offline_summary' => ['nullable', 'string', 'max:4000'],
            'file' => ['nullable', 'file', 'max:15360', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp,mp3,zip'],
        ]);

        $filePath = null;
        $fileSize = 0;

        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $fileSize = (int) $uploaded->getSize();
            $ext = strtolower($uploaded->getClientOriginalExtension() ?: 'pdf');
            $dir = public_path('uploads/materials');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $filename = 'mat_' . $course->id . '_' . time() . '_' . Str::random(5) . '.' . $ext;
            $uploaded->move($dir, $filename);
            $filePath = 'uploads/materials/' . $filename;
        }

        if (!$filePath && empty($validated['external_url']) && empty($validated['offline_summary'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes adjuntar un archivo, un enlace web o escribir una guía resumida para lectura rápida.',
            ], 422);
        }

        $tier = 'ultra_ligero';
        if ($fileSize > 2 * 1024 * 1024) {
            $tier = 'pesado';
        } elseif ($fileSize > 350 * 1024) {
            $tier = 'estandar';
        }

        // Si se indicó subfase pero no fase, inferir module_id automáticamente
        $moduleId = $validated['module_id'] ?? null;
        if (!$moduleId && !empty($validated['class_id'])) {
            $moduleId = CourseClass::where('id', $validated['class_id'])->value('module_id');
        }

        $material = CourseMaterial::create([
            'course_id' => $course->id,
            'module_id' => $moduleId,
            'class_id' => $validated['class_id'] ?? null,
            'title' => $validated['title'],
            'resource_type' => $validated['resource_type'],
            'file_path' => $filePath,
            'external_url' => $validated['external_url'] ?? null,
            'file_size_bytes' => $fileSize,
            'bandwidth_tier' => $tier,
            'offline_summary' => $validated['offline_summary'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLog::record('create', 'course_materials', "Material subido en curso #{$course->id}: {$material->title}");

        return response()->json([
            'ok' => true,
            'material' => $material->load('uploader:id,name'),
            'message' => 'Material didáctico publicado correctamente en el aula.',
        ], 201);
    }

    public function destroyMaterial(Request $request, CourseMaterial $material): JsonResponse
    {
        CurriculumApiController::authorizeCourseOperation($request->user(), $material->course);

        if ($material->file_path && file_exists(public_path($material->file_path))) {
            @unlink(public_path($material->file_path));
        }

        $title = $material->title;
        $material->delete();
        AuditLog::record('delete', 'course_materials', "Material eliminado: {$title}");

        return response()->json([
            'ok' => true,
            'message' => "Material «{$title}» eliminado.",
        ]);
    }

    // =========================================================================
    // FORO INTERACTIVO POR CURSO Y CLASE / SUBFASE
    // =========================================================================
    public function storeThread(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        $this->ensureEnrolledOrStaff($user, $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:4', 'max:180'],
            'body' => ['required', 'string', 'min:4', 'max:4000'],
            'module_id' => ['nullable', 'integer', 'exists:course_modules,id'],
            'class_id' => ['nullable', 'integer', 'exists:course_classes,id'],
        ]);

        $moduleId = $validated['module_id'] ?? null;
        if (!$moduleId && !empty($validated['class_id'])) {
            $moduleId = CourseClass::where('id', $validated['class_id'])->value('module_id');
        }

        $isStaff = $user->isAdmin() || $user->isDirector() || ($user->isInstructor() && (int) $course->instructor_id === (int) $user->id);
        if ($moduleId && !$isStaff) {
            $accessState = self::resolveModuleAccess($course, $user->id, false);
            if (!in_array((int) $moduleId, $accessState['unlocked_ids'], true)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Esta fase todavía está bloqueada. Debes completar primero la fase anterior.',
                ], 403);
            }
        }

        $thread = ForumThread::create([
            'course_id' => $course->id,
            'module_id' => $moduleId,
            'class_id' => $validated['class_id'] ?? null,
            'user_id' => $user->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_pinned' => false,
            'is_resolved' => false,
        ]);

        return response()->json([
            'ok' => true,
            'thread' => $thread->load(['user:id,name,role,profile_picture', 'module:id,title', 'courseClass:id,title', 'replies.user:id,name,role']),
            'message' => 'Tu consulta o comentario fue publicado en el foro de la clase.',
        ], 201);
    }

    public function storeReply(Request $request, ForumThread $thread): JsonResponse
    {
        $user = $request->user();
        $course = $thread->course;
        $this->ensureEnrolledOrStaff($user, $course);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        $isStaff = $user->isAdmin() || $user->isDirector() || ($user->isInstructor() && (int) $course->instructor_id === (int) $user->id);

        $reply = ForumReply::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => $validated['body'],
            'is_instructor_answer' => $isStaff,
        ]);

        if ($isStaff && !$thread->is_resolved) {
            $thread->update(['is_resolved' => true]);
        }

        return response()->json([
            'ok' => true,
            'reply' => $reply->load('user:id,name,role,profile_picture'),
            'replies_count' => $thread->fresh()->replies_count,
            'message' => 'Respuesta publicada en el foro.',
        ], 201);
    }

    public function toggleThreadPin(Request $request, ForumThread $thread): JsonResponse
    {
        CurriculumApiController::authorizeCourseOperation($request->user(), $thread->course);
        $thread->update(['is_pinned' => !$thread->is_pinned]);

        return response()->json([
            'ok' => true,
            'thread' => $thread,
            'message' => $thread->is_pinned ? 'Tema destacado al inicio del foro.' : 'Tema desanclado.',
        ]);
    }

    public function destroyThread(Request $request, ForumThread $thread): JsonResponse
    {
        $user = $request->user();
        if ((int) $thread->user_id !== (int) $user->id) {
            CurriculumApiController::authorizeCourseOperation($user, $thread->course);
        }

        $thread->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Publicación eliminada del foro.',
        ]);
    }

    // =========================================================================
    // PROGRAMAR ASISTENCIA, TOMAR LISTA MANUAL Y AUTO-REGISTRO DEL PARTICIPANTE
    // =========================================================================
    public function scheduleAttendance(Request $request, CourseClass $class): JsonResponse
    {
        CurriculumApiController::authorizeCourseOperation($request->user(), $class->module->course);

        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:immediate,scheduled,close'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'attendance_opens_at' => ['nullable', 'date'],
            'attendance_expires_at' => ['nullable', 'date', 'after:attendance_opens_at'],
        ]);

        if ($validated['mode'] === 'close') {
            $class->update([
                'attendance_opens_at' => null,
                'attendance_expires_at' => now()->subMinute(),
            ]);
            return response()->json([
                'ok' => true,
                'class' => $class->fresh(),
                'message' => 'Ventana de asistencia cerrada.',
            ]);
        }

        if ($validated['mode'] === 'scheduled' && !empty($validated['attendance_opens_at']) && !empty($validated['attendance_expires_at'])) {
            $class->update([
                'attendance_opens_at' => $validated['attendance_opens_at'],
                'attendance_expires_at' => $validated['attendance_expires_at'],
            ]);
        } else {
            $mins = (int) ($validated['duration_minutes'] ?? 45);
            $class->update([
                'attendance_opens_at' => now(),
                'attendance_expires_at' => now()->addMinutes($mins),
            ]);
        }

        return response()->json([
            'ok' => true,
            'class' => $class->fresh(),
            'message' => 'Asistencia habilitada/programada correctamente.',
        ]);
    }

    public function markStudentAttendance(Request $request, CourseClass $class): JsonResponse
    {
        $user = $request->user();
        $course = $class->module->course;

        $enrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if (!$enrolled) {
            return response()->json([
                'ok' => false,
                'message' => 'No te encuentras inscrito en este curso.',
            ], 403);
        }

        $accessState = self::resolveModuleAccess($course, $user->id, false);
        if (!in_array((int) $class->module_id, $accessState['unlocked_ids'], true)) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes marcar asistencia en una fase que aún no ha sido habilitada.',
            ], 403);
        }

        if (!$class->is_attendance_active) {
            return response()->json([
                'ok' => false,
                'message' => 'El horario para registrar asistencia en esta clase aún no ha iniciado o ya finalizó.',
            ], 422);
        }

        try {
            $record = CourseAttendance::updateOrCreate(
                ['user_id' => $user->id, 'class_id' => $class->id],
                [
                    'status' => 'presente',
                    'recorded_by' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            CourseAttendance::where('user_id', $user->id)
                ->where('class_id', $class->id)
                ->update([
                    'status' => 'presente',
                    'recorded_by' => $user->id,
                    'updated_at' => now(),
                ]);
            $record = CourseAttendance::where('user_id', $user->id)
                ->where('class_id', $class->id)
                ->first();
        }

        return response()->json([
            'ok' => true,
            'attendance' => $record,
            'message' => "¡Tu asistencia quedó registrada en «{$class->title}»!",
        ]);
    }

    public function recordManualAttendance(Request $request, CourseClass $class): JsonResponse
    {
        CurriculumApiController::authorizeCourseOperation($request->user(), $class->module->course);

        $validated = $request->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'records.*.status' => ['required', 'string', 'in:presente,tardanza,justificado,ausente'],
        ]);

        foreach ($validated['records'] as $item) {
            if ($item['status'] === 'ausente') {
                CourseAttendance::where('user_id', $item['user_id'])
                    ->where('class_id', $class->id)
                    ->delete();
            } else {
                CourseAttendance::updateOrCreate(
                    ['user_id' => $item['user_id'], 'class_id' => $class->id],
                    [
                        'status' => $item['status'],
                        'recorded_by' => $request->user()->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        AuditLog::record('update', 'course_attendance', "Toma de asistencia manual en clase #{$class->id} ({$class->title})");

        return response()->json([
            'ok' => true,
            'message' => 'Lista de asistencia guardada correctamente.',
        ]);
    }

    protected function ensureEnrolledOrStaff($user, Course $course): void
    {
        if ($user->isAdmin() || $user->isDirector() || ($user->isInstructor() && (int) $course->instructor_id === (int) $user->id)) {
            return;
        }

        $enrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if (!$enrolled) {
            abort(403, 'Debes pertenecer a este curso para participar.');
        }
    }

    /**
     * Resuelve qué fases están desbloqueadas para un participante (RF-02.1 + RF-02.2):
     * - Fase 1 siempre habilitada para inscritos.
     * - Fase K (>1) requiere que el instructor/admin haya marcado como completada la Fase K-1
     *   (is_completed = true o todas sus subfases marcadas como dictadas) Y que el participante
     *   haya aprobado la evaluación de la Fase K-1 (si dicha fase tiene evaluación publicada).
     */
    public static function resolveModuleAccess(Course $course, int $userId, bool $canManage): array
    {
        $modules = $course->relationLoaded('modules')
            ? $course->modules
            : $course->modules()->with('classes')->get();

        $unlockedIds = [];
        $map = [];

        if ($canManage) {
            foreach ($modules as $mod) {
                $unlockedIds[] = (int) $mod->id;
                $map[$mod->id] = ['is_unlocked' => true, 'lock_reason' => null];
            }
            return ['unlocked_ids' => $unlockedIds, 'map' => $map];
        }

        $allUnlockedSoFar = true;
        $pendingReason = null;

        foreach ($modules->values() as $idx => $mod) {
            if ($idx === 0 || $allUnlockedSoFar) {
                $unlockedIds[] = (int) $mod->id;
                $map[$mod->id] = ['is_unlocked' => true, 'lock_reason' => null];
            } else {
                $map[$mod->id] = ['is_unlocked' => false, 'lock_reason' => $pendingReason];
                continue;
            }

            // Verificar si esta fase habilita la siguiente
            $classes = $mod->relationLoaded('classes') ? $mod->classes : $mod->classes()->get();
            $allClassesCompleted = $classes->count() > 0 && $classes->every(fn ($c) => (bool) $c->is_completed);
            $phaseMarkedDone = (bool) $mod->is_completed || $allClassesCompleted;

            if (!$phaseMarkedDone) {
                $allUnlockedSoFar = false;
                $pendingReason = "Bloqueada: el instructor o administrador aún debe marcar como completada la Fase " . ($idx + 1) . " ({$mod->title}).";
                continue;
            }

            // Verificar evaluaciones publicadas de esta fase (RF-02.2)
            $evalIds = ModuleEvaluation::where('module_id', $mod->id)
                ->where('is_published', true)
                ->pluck('id');

            foreach ($evalIds as $evId) {
                $passed = EvaluationAttempt::where('evaluation_id', $evId)
                    ->where('user_id', $userId)
                    ->where('passed', true)
                    ->exists();
                if (!$passed) {
                    $allUnlockedSoFar = false;
                    $pendingReason = "Bloqueada: debes aprobar la evaluación de la Fase " . ($idx + 1) . " ({$mod->title}) para continuar.";
                    break;
                }
            }
        }

        return ['unlocked_ids' => $unlockedIds, 'map' => $map];
    }
}
