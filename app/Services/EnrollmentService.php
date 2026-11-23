<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /**
     * Inscribe a un estudiante asegurando concurrencia estricta mediante transacciones ACID y bloqueo pesimista.
     */
    public function enroll(int $userId, int $courseId): array
    {
        return DB::transaction(function () use ($userId, $courseId) {
            // 1. Bloqueo pesimista a nivel de fila (evita race conditions en el último cupo)
            $course = Course::where('id', $courseId)->lockForUpdate()->first();

            if (!$course || $course->status !== 'published') {
                return [
                    'ok' => false,
                    'code' => 'closed',
                    'message' => 'Este curso no está disponible para inscripciones.',
                ];
            }

            // 2. Verificar si el estudiante ya tiene un registro activo
            $existing = Enrollment::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->first();

            if ($existing && $existing->status !== 'cancelled') {
                return [
                    'ok' => true,
                    'code' => 'already',
                    'message' => "Ya estás inscrito en «{$course->title}».",
                ];
            }

            // 3. Regla de negocio: el curso debe encontrarse en la primera mitad de su avance (< 50%)
            if ($course->progress_pct >= 50) {
                return [
                    'ok' => false,
                    'code' => 'closed',
                    'message' => 'El curso ha superado el 50% de su avance programado. Las inscripciones se encuentran cerradas.',
                ];
            }

            // 4. Control estricto de cupos disponibles
            $taken = Enrollment::where('course_id', $courseId)
                ->whereIn('status', ['active', 'completed'])
                ->count();

            if ($taken >= (int) $course->capacity) {
                return [
                    'ok' => false,
                    'code' => 'full',
                    'message' => "Los cupos se han agotado para «{$course->title}».",
                ];
            }

            // 5. Alta o reactivación
            if ($existing) {
                $existing->update([
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'completed_at' => null,
                ]);
            } else {
                Enrollment::create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'status' => 'active',
                    'enrolled_at' => now(),
                ]);
            }

            return [
                'ok' => true,
                'code' => 'enrolled',
                'message' => "¡Inscripción exitosa! Tu cupo está confirmado en «{$course->title}».",
            ];
        });
    }
}
