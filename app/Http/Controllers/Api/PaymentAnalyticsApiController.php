<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAttendance;
use App\Models\Enrollment;
use App\Models\EntrepreneurProfile;
use App\Models\EvaluationAttempt;
use App\Models\FairParticipation;
use App\Models\PageVisit;
use App\Models\PaymentMethod;
use App\Models\PaymentPlan;
use App\Models\PaymentTransaction;
use App\Models\SocialOrganization;
use App\Models\UsageSession;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentAnalyticsApiController extends Controller
{
    // =========================================================================
    // INF513 Req 10: MÓDULO DE PAGOS ELECTRÓNICOS Y PLANES DE PAGO
    // =========================================================================
    public function myPayments(Request $request): JsonResponse
    {
        $user = $request->user();

        $methods = PaymentMethod::where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        $plans = PaymentPlan::with([
            'course:id,title,slug,modality',
            'transactions.method:id,provider_name,masked_reference',
        ])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'ok' => true,
            'methods' => $methods,
            'plans' => $plans,
        ]);
    }

    public function storePaymentMethod(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'method_type' => ['required', 'string', 'in:qr_banca,tarjeta,transferencia'],
            'provider_name' => ['required', 'string', 'max:80'],
            'masked_reference' => ['required', 'string', 'max:60'],
            'holder_name' => ['required', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['is_default'])) {
            PaymentMethod::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $method = PaymentMethod::create([
            'user_id' => $user->id,
            'method_type' => $validated['method_type'],
            'provider_name' => $validated['provider_name'],
            'masked_reference' => $validated['masked_reference'],
            'holder_name' => $validated['holder_name'],
            'is_default' => (bool) ($validated['is_default'] ?? ($user->paymentMethods()->count() === 0)),
        ]);

        AuditLog::record('create', 'payment_methods', "Método de pago registrado: {$method->provider_name}");

        return response()->json([
            'ok' => true,
            'method' => $method,
            'message' => 'Método de pago guardado correctamente.',
        ], 201);
    }

    public function destroyPaymentMethod(Request $request, PaymentMethod $method): JsonResponse
    {
        if ((int) $method->user_id !== (int) $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'No autorizado.');
        }

        $method->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Método de pago eliminado.',
        ]);
    }

    /**
     * Inscripción a curso procesando pago único o plan de cuotas (ACID + Prerrequisitos + Pagos).
     */
    public function enrollCheckout(Request $request, Course $course, EnrollmentService $service): JsonResponse
    {
        $validated = $request->validate([
            'plan_type' => ['nullable', 'string', 'in:unico,cuotas'],
            'installments_total' => ['nullable', 'integer', 'min:1', 'max:12'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'pay_first_now' => ['nullable', 'boolean'],
            'new_method' => ['nullable', 'array'],
            'new_method.method_type' => ['nullable', 'string', 'in:qr_banca,tarjeta,transferencia'],
            'new_method.provider_name' => ['nullable', 'string', 'max:80'],
            'new_method.masked_reference' => ['nullable', 'string', 'max:60'],
            'new_method.holder_name' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $service->enroll($request->user()->id, $course->id, $validated);

        if (!$result['ok']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Procesa el pago de la siguiente cuota pendiente en un Plan de Pagos (dispara Trigger PostgreSQL).
     */
    public function payInstallment(Request $request, PaymentPlan $plan): JsonResponse
    {
        $user = $request->user();
        if ((int) $plan->user_id !== (int) $user->id && !$user->isAdmin()) {
            abort(403, 'No tienes acceso a este plan de pagos.');
        }

        if ($plan->status === 'pagado') {
            return response()->json([
                'ok' => false,
                'message' => 'Este plan de pagos ya se encuentra cancelado en su totalidad.',
            ], 422);
        }

        $validated = $request->validate([
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
        ]);

        $remaining = max(0, round((float) $plan->total_amount - (float) $plan->paid_amount, 2));
        $remainingInstallments = max(1, (int) $plan->installments_total - (int) $plan->installments_paid);
        $amountToPay = $remainingInstallments <= 1 ? $remaining : round($remaining / $remainingInstallments, 2);
        $nextNumber = ((int) $plan->installments_paid) + 1;

        $tx = PaymentTransaction::create([
            'payment_plan_id' => $plan->id,
            'user_id' => $plan->user_id,
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'installment_number' => $nextNumber,
            'amount' => $amountToPay,
            'currency' => 'BOB',
            'reference_code' => 'FED-' . strtoupper(Str::random(8)),
            'payment_status' => 'completado',
            'paid_at' => now(),
            'notes' => "Pago de Cuota {$nextNumber}/{$plan->installments_total}",
        ]);

        // El Trigger PostgreSQL trg_sync_payment_plan_totals ya actualizó paid_amount, installments_paid y status
        $plan->refresh();
        if ($plan->status !== 'pagado') {
            $plan->update(['next_due_date' => now()->addDays(30)->format('Y-m-d')]);
        } else {
            $plan->update(['next_due_date' => null]);
        }

        AuditLog::record('payment', 'payment_transactions', "Pago registrado {$tx->reference_code} por {$amountToPay} BOB en plan #{$plan->id}");

        return response()->json([
            'ok' => true,
            'plan' => $plan->load(['course:id,title,slug', 'transactions.method']),
            'transaction' => $tx,
            'message' => "¡Pago procesado exitosamente! Recibo N.° {$tx->reference_code} por {$amountToPay} Bs.",
        ]);
    }

    // =========================================================================
    // CU7 & CU8 + INF513 Req 8: MÉTRICAS LMS, DEMOGRAFÍA, IMPACTO SOCIAL Y REPORTES REALES
    // =========================================================================
    public function dashboardMetrics(Request $request): JsonResponse
    {
        $periodId = $request->input('period_id');
        $categoryId = $request->input('category_id');

        $coursesQuery = Course::with(['category:id,name', 'period:id,name,code'])
            ->withCount(['enrollments' => fn ($q) => $q->whereIn('status', ['active', 'completed'])]);

        if ($periodId) {
            $coursesQuery->where('period_id', (int) $periodId);
        }
        if ($categoryId) {
            $coursesQuery->where('category_id', (int) $categoryId);
        }

        $coursesList = $coursesQuery->orderByDesc('enrollments_count')->get();

        // Calcular datos reales por curso: matrículas, asistencias registradas, horas de uso, certificados e ingresos reales
        $courseReportRows = $coursesList->map(function (Course $c) {
            $classIds = $c->classes()->pluck('course_classes.id')->all();
            $attCount = count($classIds) > 0
                ? CourseAttendance::whereIn('class_id', $classIds)->whereIn('status', ['presente', 'tardanza', 'justificado'])->count()
                : 0;

            $expectedAttendances = max(1, count($classIds) * max(1, (int) $c->enrollments_count));
            $attRate = count($classIds) > 0 && $c->enrollments_count > 0
                ? min(100, (int) round(($attCount / $expectedAttendances) * 100))
                : 0;

            $revenue = (float) PaymentPlan::where('course_id', $c->id)->sum('paid_amount');
            $certs = Certificate::where('course_id', $c->id)->where('status', 'valid')->count();
            $usageSeconds = (int) UsageSession::where('course_id', $c->id)->sum('seconds_spent');

            return [
                'id' => $c->id,
                'title' => $c->title,
                'category' => $c->category?->name ?? 'General',
                'period' => $c->period?->code ?? 'Actual',
                'modality' => $c->modality,
                'capacity' => $c->capacity,
                'enrollments_count' => (int) $c->enrollments_count,
                'occupancy_pct' => $c->capacity > 0 ? (int) round(($c->enrollments_count / $c->capacity) * 100) : 0,
                'attendance_count' => $attCount,
                'attendance_rate_pct' => $attRate,
                'progress_pct' => (int) $c->progress_pct,
                'certificates_count' => $certs,
                'revenue_bob' => round($revenue, 2),
                'active_minutes' => (int) round($usageSeconds / 60),
            ];
        })->values();

        // Demografía real de participantes (CU7)
        $participants = User::whereIn('role', ['participant', 'student'])->get(['id', 'gender', 'birth_date', 'city', 'total_active_seconds']);

        $genderStats = $participants->groupBy(fn ($u) => $u->gender ?: 'No especificado')
            ->map->count();

        $ageRanges = [
            '18-29 años (Jóvenes Adultos)' => 0,
            '30-49 años (Adultos)' => 0,
            '50+ años (Adultos Mayores)' => 0,
            'Sin dato' => 0,
        ];
        foreach ($participants as $p) {
            if (!$p->birth_date) {
                $ageRanges['Sin dato']++;
                continue;
            }
            $age = $p->birth_date->age;
            if ($age < 30) {
                $ageRanges['18-29 años (Jóvenes Adultos)']++;
            } elseif ($age < 50) {
                $ageRanges['30-49 años (Adultos)']++;
            } else {
                $ageRanges['50+ años (Adultos Mayores)']++;
            }
        }

        // Impacto Social Real (CU2 + CU7)
        $profiles = EntrepreneurProfile::get();
        $sectorBreakdown = $profiles->groupBy('sector')->map->count();
        $stageBreakdown = $profiles->groupBy('business_stage')->map->count();
        $totalJobsGenerated = (int) $profiles->sum('employees_count');

        // Tráfico y Flujo de Accesos Web Reales (INF513 Req 7 y 8)
        $topPages = PageVisit::orderByDesc('hits_count')->take(8)->get();
        $dayExpr = DB::getDriverName() === 'pgsql'
            ? "TO_CHAR(visited_at, 'YYYY-MM-DD') as day, COUNT(*) as hits"
            : "SUBSTR(CAST(visited_at AS TEXT), 1, 10) as day, COUNT(*) as hits";
        $dailyVisits = DB::table('page_visit_logs')
            ->selectRaw($dayExpr)
            ->groupBy('day')
            ->orderBy('day')
            ->limit(14)
            ->get();

        // Resumen Financiero y Académico Global Real
        $kpis = [
            'total_courses' => Course::count(),
            'published_courses' => Course::where('status', 'published')->count(),
            'total_participants' => $participants->count(),
            'total_enrollments' => Enrollment::whereIn('status', ['active', 'completed'])->count(),
            'total_attendances' => CourseAttendance::whereIn('status', ['presente', 'tardanza', 'justificado'])->count(),
            'total_certificates' => Certificate::where('status', 'valid')->count(),
            'total_revenue_bob' => round((float) PaymentTransaction::where('payment_status', 'completado')->sum('amount'), 2),
            'pending_receivable_bob' => round((float) PaymentPlan::whereIn('status', ['pendiente', 'parcial'])->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as due')->value('due'), 2),
            'evaluation_pass_rate' => EvaluationAttempt::count() > 0
                ? round((EvaluationAttempt::where('passed', true)->count() / EvaluationAttempt::count()) * 100, 1)
                : 0,
            'fair_projects_count' => FairParticipation::count(),
            'partner_organizations_count' => SocialOrganization::where('agreement_status', 'activo')->count(),
            'entrepreneurs_registered' => $profiles->count(),
            'jobs_supported' => $totalJobsGenerated,
            'total_platform_minutes' => (int) round(((int) User::sum('total_active_seconds')) / 60),
            'total_page_views' => (int) PageVisit::sum('hits_count'),
        ];

        $recentTransactions = PaymentTransaction::with([
            'user:id,name,email',
            'plan.course:id,title',
        ])->orderByDesc('paid_at')->take(10)->get();

        return response()->json([
            'ok' => true,
            'kpis' => $kpis,
            'course_reports' => $courseReportRows,
            'demographics' => [
                'by_gender' => $genderStats,
                'by_age_range' => $ageRanges,
            ],
            'social_impact' => [
                'by_sector' => $sectorBreakdown,
                'by_stage' => $stageBreakdown,
                'verified_profiles' => $profiles->whereNotNull('verified_at')->count(),
                'jobs_supported' => $totalJobsGenerated,
            ],
            'web_traffic' => [
                'top_pages' => $topPages,
                'daily_visits' => $dailyVisits,
            ],
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /**
     * Exportación CSV real de reportes de cursos cotizados, asistencia e ingresos (CU8).
     */
    public function exportReportCsv(Request $request): StreamedResponse
    {
        $data = $this->dashboardMetrics($request)->getData(true);
        $rows = $data['course_reports'] ?? [];

        AuditLog::record('resource_access', 'reports_csv', 'Exportación de reporte estadístico oficial en CSV');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 para lectura directa en Excel en español
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'ID',
                'Curso',
                'Área / Categoría',
                'Modalidad',
                'Inscritos',
                'Cupo Máximo',
                '% Ocupación',
                'Asistencias Registradas',
                '% Asistencia',
                '% Avance Fases',
                'Certificados Emitidos',
                'Tiempo Uso Aula (Min)',
            ], ';');

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['id'],
                    $r['title'],
                    $r['category'],
                    $r['modality'],
                    $r['enrollments_count'],
                    $r['capacity'],
                    $r['occupancy_pct'] . '%',
                    $r['attendance_count'],
                    $r['attendance_rate_pct'] . '%',
                    $r['progress_pct'] . '%',
                    $r['certificates_count'],
                    $r['active_minutes'],
                ], ';');
            }
            fclose($out);
        }, 'reporte_ejecutivo_feder_' . date('Ymd_His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
