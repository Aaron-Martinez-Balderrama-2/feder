<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PaymentMethod;
use App\Models\PaymentPlan;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollmentService
{
    /**
     * Inscribe a un participante verificando prerrequisitos bloqueantes (CU3),
     * límite de avance (<50%), control ACID de cupos con bloqueo pesimista
     * y procesamiento estructurado de pago único o plan de cuotas (Req 10).
     */
    public function enroll(int $userId, int $courseId, array $paymentOptions = []): array
    {
        return DB::transaction(function () use ($userId, $courseId, $paymentOptions) {
            $course = Course::with('prerequisites')->where('id', $courseId)->lockForUpdate()->first();

            if (!$course || $course->status !== 'published') {
                return [
                    'ok' => false,
                    'code' => 'closed',
                    'message' => 'Esta capacitación no está disponible para inscripciones.',
                ];
            }

            if ($course->end_date && $course->end_date->copy()->endOfDay()->isPast()) {
                return [
                    'ok' => false,
                    'code' => 'expired',
                    'message' => 'Esta capacitación ya concluyó su fecha límite y dejó de estar disponible.',
                ];
            }

            // 1. Verificar inscripción activa previa antes de validar cupos
            $existing = Enrollment::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->first();

            if ($existing && $existing->status !== 'cancelled') {
                return [
                    'ok' => true,
                    'code' => 'already',
                    'enrollment_id' => $existing->id,
                    'message' => "Ya cuentas con inscripción activa en «{$course->title}».",
                ];
            }

            // 2. Validar Prerrequisitos Bloqueantes (CU3)
            foreach ($course->prerequisites as $prereq) {
                $isMandatory = (bool) ($prereq->pivot->is_mandatory ?? true);
                if (!$isMandatory) {
                    continue;
                }

                $completedEnrollment = Enrollment::where('user_id', $userId)
                    ->where('course_id', $prereq->id)
                    ->where('status', 'completed')
                    ->exists();

                $validCert = Certificate::where('user_id', $userId)
                    ->where('course_id', $prereq->id)
                    ->where('status', 'valid')
                    ->exists();

                if (!$completedEnrollment && !$validCert) {
                    return [
                        'ok' => false,
                        'code' => 'prerequisite_missing',
                        'prerequisite_course' => [
                            'id' => $prereq->id,
                            'title' => $prereq->title,
                            'slug' => $prereq->slug,
                        ],
                        'message' => "Prerrequisito bloqueante: antes de inscribirte en este curso debes aprobar «{$prereq->title}».",
                    ];
                }
            }

            // 3. Regla de negocio: el curso debe encontrarse por debajo del 50% de avance
            if ($course->progress_pct >= 50) {
                return [
                    'ok' => false,
                    'code' => 'cutoff_reached',
                    'message' => 'Esta cohorte ya superó el 50% de avance. Las inscripciones están cerradas.',
                ];
            }

            // 4. Control ACID de cupos disponibles
            $taken = Enrollment::where('course_id', $courseId)
                ->whereIn('status', ['active', 'completed'])
                ->count();

            if ($taken >= (int) $course->capacity) {
                return [
                    'ok' => false,
                    'code' => 'full',
                    'message' => "Los cupos disponibles se agotaron para «{$course->title}».",
                ];
            }

            // 5. Crear o reactivar matrícula
            if ($existing) {
                $existing->update([
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'completed_at' => null,
                ]);
                $enrollment = $existing;
            } else {
                try {
                    $enrollment = Enrollment::create([
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'status' => 'active',
                        'enrolled_at' => now(),
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    $dup = Enrollment::where('user_id', $userId)->where('course_id', $courseId)->first();
                    return [
                        'ok' => true,
                        'code' => 'already',
                        'enrollment_id' => $dup?->id,
                        'message' => "Ya cuentas con inscripción activa en «{$course->title}».",
                    ];
                }
            }

            // 6. Gestión estructurada de Pago Único o Plan de Cuotas (INF513 Req 10)
            $price = (float) ($course->price ?? 0);
            $plan = null;
            $transaction = null;

            if ($price > 0) {
                $planType = ($paymentOptions['plan_type'] ?? 'unico') === 'cuotas' ? 'cuotas' : 'unico';
                $maxInstallments = max(1, (int) ($course->installments_allowed ?? 1));
                $installmentsTotal = $planType === 'cuotas'
                    ? min($maxInstallments, max(2, (int) ($paymentOptions['installments_total'] ?? $maxInstallments)))
                    : 1;

                $paymentMethodId = $paymentOptions['payment_method_id'] ?? null;
                if (!$paymentMethodId && !empty($paymentOptions['new_method'])) {
                    $nm = $paymentOptions['new_method'];
                    $createdMethod = PaymentMethod::create([
                        'user_id' => $userId,
                        'method_type' => $nm['method_type'] ?? 'qr_banca',
                        'provider_name' => $nm['provider_name'] ?? 'QR Simple Bolivia',
                        'masked_reference' => $nm['masked_reference'] ?? ('QR-' . random_int(1000, 9999)),
                        'holder_name' => $nm['holder_name'] ?? 'Titular',
                        'is_default' => true,
                    ]);
                    $paymentMethodId = $createdMethod->id;
                }

                $plan = PaymentPlan::updateOrCreate(
                    ['user_id' => $userId, 'course_id' => $courseId],
                    [
                        'enrollment_id' => $enrollment->id,
                        'plan_type' => $planType,
                        'total_amount' => $price,
                        'installments_total' => $installmentsTotal,
                        'status' => 'pendiente',
                        'next_due_date' => $planType === 'cuotas' ? now()->addDays(30)->format('Y-m-d') : null,
                    ]
                );

                $payNow = (bool) ($paymentOptions['pay_first_now'] ?? true);
                if ($payNow) {
                    $firstAmount = round($price / $installmentsTotal, 2);
                    $transaction = PaymentTransaction::create([
                        'payment_plan_id' => $plan->id,
                        'user_id' => $userId,
                        'payment_method_id' => $paymentMethodId,
                        'installment_number' => 1,
                        'amount' => $firstAmount,
                        'currency' => 'BOB',
                        'reference_code' => 'FED-' . strtoupper(Str::random(8)),
                        'payment_status' => 'completado',
                        'paid_at' => now(),
                        'notes' => $planType === 'cuotas'
                            ? "Pago inicial Cuota 1/{$installmentsTotal} - {$course->title}"
                            : "Pago único completo - {$course->title}",
                    ]);
                    $plan->refresh();
                }
            }

            AuditLog::record(
                'create',
                'enrollments',
                "Inscripción confirmada en curso #{$course->id} ({$course->title})",
                $userId
            );

            return [
                'ok' => true,
                'code' => 'enrolled',
                'enrollment_id' => $enrollment->id,
                'payment_plan' => $plan,
                'transaction' => $transaction,
                'message' => "¡Inscripción confirmada en «{$course->title}»! Ya puedes ingresar a tu aula virtual.",
            ];
        }, 3);
    }
}
