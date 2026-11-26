<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EvaluationAttempt;
use App\Models\EvaluationQuestion;
use App\Models\FairParticipation;
use App\Models\ModuleEvaluation;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EvaluationCertificateApiController extends Controller
{
    // =========================================================================
    // CU5: GESTIÓN DE EVALUAR MÓDULOS Y PROCESAR CALIFICACIÓN AUTOMÁTICA
    // =========================================================================
    public function storeEvaluation(Request $request, Course $course): JsonResponse
    {
        CurriculumApiController::authorizeCourseOperation($request->user(), $course);

        $validated = $request->validate([
            'module_id' => ['required', 'integer', 'exists:course_modules,id'],
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'instructions' => ['nullable', 'string', 'max:1500'],
            'passing_score' => ['required', 'numeric', 'min:10', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string', 'min:4'],
            'questions.*.question_type' => ['required', 'string', 'in:multiple_choice,true_false'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.correct_option' => ['required', 'string', 'max:40'],
            'questions.*.points' => ['required', 'numeric', 'min:1', 'max:100'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($course, $validated) {
            $eval = ModuleEvaluation::create([
                'course_id' => $course->id,
                'module_id' => $validated['module_id'],
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'passing_score' => $validated['passing_score'],
                'max_attempts' => $validated['max_attempts'],
                'is_published' => true,
            ]);

            foreach ($validated['questions'] as $idx => $qData) {
                EvaluationQuestion::create([
                    'evaluation_id' => $eval->id,
                    'question_text' => $qData['question_text'],
                    'question_type' => $qData['question_type'],
                    'options' => $qData['options'],
                    'correct_option' => $qData['correct_option'],
                    'points' => $qData['points'],
                    'explanation' => $qData['explanation'] ?? null,
                    'position' => $idx + 1,
                ]);
            }

            AuditLog::record('create', 'module_evaluations', "Evaluación creada en curso #{$course->id}: {$eval->title}");

            return response()->json([
                'ok' => true,
                'evaluation' => $eval->load('questions'),
                'message' => 'Evaluación de módulo creada con calificación automática habilitada.',
            ], 201);
        });
    }

    public function destroyEvaluation(Request $request, ModuleEvaluation $evaluation): JsonResponse
    {
        CurriculumApiController::authorizeCourseOperation($request->user(), $evaluation->course);
        $title = $evaluation->title;
        $evaluation->delete();

        AuditLog::record('delete', 'module_evaluations', "Evaluación eliminada: {$title}");

        return response()->json([
            'ok' => true,
            'message' => "Evaluación «{$title}» eliminada.",
        ]);
    }

    /**
     * Procesa respuestas del participante y califica de forma 100% automática e inmediata (CU5).
     */
    public function submitAttempt(Request $request, ModuleEvaluation $evaluation): JsonResponse
    {
        $user = $request->user();
        $course = $evaluation->course;

        $enrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if (!$enrolled && !$user->isAdmin() && !$user->isInstructor()) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes estar inscrito en el curso para rendir esta evaluación.',
            ], 403);
        }

        $previousCount = EvaluationAttempt::where('evaluation_id', $evaluation->id)
            ->where('user_id', $user->id)
            ->count();

        if ($previousCount >= (int) $evaluation->max_attempts) {
            return response()->json([
                'ok' => false,
                'message' => "Ya alcanzaste el límite máximo de {$evaluation->max_attempts} intentos permitidos para esta evaluación.",
            ], 422);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $questions = $evaluation->questions()->orderBy('position')->get();
        $earnedPoints = 0.0;
        $maxPoints = 0.0;
        $feedback = [];

        foreach ($questions as $q) {
            $maxPoints += (float) $q->points;
            $given = (string) ($validated['answers'][$q->id] ?? $validated['answers'][(string) $q->id] ?? '');
            $isCorrect = trim($given) !== '' && mb_strtoupper(trim($given)) === mb_strtoupper(trim($q->correct_option));

            if ($isCorrect) {
                $earnedPoints += (float) $q->points;
            }

            $feedback[] = [
                'question_id' => $q->id,
                'given_option' => $given,
                'correct_option' => $q->correct_option,
                'is_correct' => $isCorrect,
                'explanation' => $q->explanation,
            ];
        }

        $percentage = $maxPoints > 0 ? round(($earnedPoints / $maxPoints) * 100, 2) : 0.0;
        $passed = $percentage >= (float) $evaluation->passing_score;

        $attempt = EvaluationAttempt::create([
            'evaluation_id' => $evaluation->id,
            'user_id' => $user->id,
            'answers' => $validated['answers'],
            'score' => round($earnedPoints, 2),
            'max_score' => round($maxPoints, 2),
            'percentage' => $percentage,
            'passed' => $passed,
            'submitted_at' => now(),
        ]);

        AuditLog::record(
            'create',
            'evaluation_attempts',
            "Evaluación #{$evaluation->id} rendida: {$percentage}% (" . ($passed ? 'Aprobado' : 'Reprobado') . ")",
            $user->id
        );

        return response()->json([
            'ok' => true,
            'attempt' => $attempt,
            'feedback' => $feedback,
            'message' => $passed
                ? "¡Felicidades! Aprobaste la evaluación con {$percentage}%."
                : "Obtuviste {$percentage}%. La nota mínima de aprobación es {$evaluation->passing_score}%. Revisa la retroalimentación e inténtalo de nuevo.",
        ]);
    }

    // =========================================================================
    // CU5: PARTICIPACIÓN EN FERIA EMPRESARIAL
    // =========================================================================
    public function saveFairParticipation(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (!$enrollment && !$user->isAdmin() && !$user->isInstructor()) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes pertenecer al curso para postular tu emprendimiento a la Feria.',
            ], 403);
        }

        $validated = $request->validate([
            'project_title' => ['required', 'string', 'min:3', 'max:180'],
            'fair_location' => ['nullable', 'string', 'max:160'],
        ]);

        $fair = FairParticipation::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            [
                'enrollment_id' => $enrollment?->id,
                'project_title' => $validated['project_title'],
                'fair_location' => $validated['fair_location'] ?? 'Feria Empresarial Productiva FEDER',
                'fair_date' => $course->end_date?->format('Y-m-d') ?? now()->addDays(10)->format('Y-m-d'),
            ]
        );

        return response()->json([
            'ok' => true,
            'fair' => $fair,
            'message' => '¡Tu emprendimiento quedó inscrito para la Feria Productiva del curso!',
        ]);
    }

    public function evaluateFairParticipation(Request $request, Course $course): JsonResponse
    {
        CurriculumApiController::authorizeCourseOperation($request->user(), $course);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'project_title' => ['required', 'string', 'max:180'],
            'stand_code' => ['nullable', 'string', 'max:40'],
            'rubric_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'participation_status' => ['required', 'string', 'in:registrado,participo,destacado,pendiente'],
            'evaluator_feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrollment = Enrollment::where('course_id', $course->id)->where('user_id', $validated['user_id'])->first();

        $fair = FairParticipation::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $validated['user_id']],
            [
                'enrollment_id' => $enrollment?->id,
                'project_title' => $validated['project_title'],
                'stand_code' => $validated['stand_code'] ?? null,
                'fair_date' => now()->format('Y-m-d'),
                'rubric_score' => $validated['rubric_score'],
                'participation_status' => $validated['participation_status'],
                'evaluator_feedback' => $validated['evaluator_feedback'] ?? null,
                'evaluated_by' => $request->user()->id,
            ]
        );

        AuditLog::record('update', 'fair_participations', "Calificación de Feria en curso #{$course->id} para usuario #{$validated['user_id']}: {$validated['rubric_score']} pts");

        return response()->json([
            'ok' => true,
            'fair' => $fair->load('user:id,name,email'),
            'message' => 'Evaluación de participación en Feria guardada correctamente.',
        ]);
    }

    // =========================================================================
    // CU6: CERTIFICADOS DIGITALES, SUBIDA DE IMPRIMIBLES E INSPECCIÓN PÚBLICA QR
    // =========================================================================
    public function issueOrUploadCertificate(Request $request, Course $course): JsonResponse
    {
        CurriculumApiController::authorizeCourseOperation($request->user(), $course);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'final_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'attendance_pct' => ['nullable', 'integer', 'min:0', 'max:100'],
            'hours_completed' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'printable_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $enrollment = Enrollment::where('course_id', $course->id)
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($enrollment && $enrollment->status !== 'completed') {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        $existing = Certificate::where('course_id', $course->id)
            ->where('user_id', $validated['user_id'])
            ->first();

        $customFilePath = $existing?->custom_file_path;
        if ($request->hasFile('printable_file')) {
            $file = $request->file('printable_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $dir = public_path('uploads/certificates');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $fname = 'cert_' . $course->id . '_' . $validated['user_id'] . '_' . time() . '.' . $ext;
            $file->move($dir, $fname);
            if ($customFilePath && file_exists(public_path($customFilePath))) {
                @unlink(public_path($customFilePath));
            }
            $customFilePath = 'uploads/certificates/' . $fname;
        }

        $code = $existing?->verification_code ?: ('FEDER-' . date('Y') . '-' . strtoupper(Str::random(6)));

        $cert = Certificate::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $validated['user_id']],
            [
                'verification_code' => $code,
                'enrollment_id' => $enrollment?->id,
                'issued_by' => $request->user()->id,
                'final_grade' => $validated['final_grade'] ?? $existing?->final_grade ?? 85.0,
                'attendance_pct' => $validated['attendance_pct'] ?? $existing?->attendance_pct ?? 100,
                'hours_completed' => $validated['hours_completed'] ?? $course->hours_duration ?? 60,
                'custom_file_path' => $customFilePath,
                'status' => 'valid',
                'issued_at' => $existing?->issued_at ?? now(),
            ]
        );

        AuditLog::record('certificate_issue', 'certificates', "Emisión/subida de certificado {$cert->verification_code} en curso #{$course->id}");

        return response()->json([
            'ok' => true,
            'certificate' => $cert->load(['user:id,name,email,id_document', 'course:id,title,hours_duration', 'issuer:id,name']),
            'message' => '¡Certificado emitido y habilitado para descarga e impresión del participante!',
        ]);
    }

    public function revokeCertificate(Request $request, Certificate $certificate): JsonResponse
    {
        CurriculumApiController::authorizeCourseOperation($request->user(), $certificate->course);
        $certificate->update(['status' => $certificate->status === 'valid' ? 'revoked' : 'valid']);

        AuditLog::record('update', 'certificates', "Cambio de estado de certificado {$certificate->verification_code} -> {$certificate->status}");

        return response()->json([
            'ok' => true,
            'certificate' => $certificate,
            'message' => $certificate->status === 'valid' ? 'Certificado reactivado.' : 'Certificado revocado.',
        ]);
    }

    /**
     * Inspección Pública de Certificados mediante Código QR (CU6 - Acceso público sin login).
     */
    public function verifyPublicQr(string $code): JsonResponse
    {
        $cleanCode = strtoupper(trim($code));
        $cert = Certificate::with([
            'user:id,name,id_document',
            'course:id,title,slug,hours_duration,modality,start_date,end_date',
            'issuer:id,name',
        ])
            ->whereRaw('UPPER(verification_code) = ?', [$cleanCode])
            ->first();

        if (!$cert) {
            return response()->json([
                'ok' => false,
                'valid' => false,
                'message' => 'No se encontró ningún certificado registrado con ese código de verificación.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'valid' => $cert->status === 'valid',
            'institution' => Setting::getVal('institution_name', 'FEDER'),
            'authority' => Setting::getVal('certificate_authority', 'Dirección Ejecutiva FEDER'),
            'certificate' => [
                'verification_code' => $cert->verification_code,
                'status' => $cert->status,
                'issued_at' => $cert->issued_at?->format('Y-m-d'),
                'final_grade' => $cert->final_grade,
                'attendance_pct' => $cert->attendance_pct,
                'hours_completed' => $cert->hours_completed,
                'custom_file_path' => $cert->custom_file_path,
                'participant_name' => $cert->user?->name,
                'participant_document' => $cert->user?->id_document,
                'course_title' => $cert->course?->title,
                'course_modality' => $cert->course?->modality,
                'issuer_name' => $cert->issuer?->name,
            ],
        ]);
    }
}
