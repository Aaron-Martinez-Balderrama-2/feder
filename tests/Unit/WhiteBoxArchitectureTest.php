<?php

namespace Tests\Unit;

use App\Http\Middleware\SanitizeInputMiddleware;
use App\Models\Category;
use App\Models\Course;
use App\Models\CoursePrerequisite;
use App\Models\Enrollment;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\PageVisit;
use App\Models\PageVisitLog;
use App\Models\PaymentPlan;
use App\Models\PaymentTransaction;
use App\Models\UsageSession;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhiteBoxArchitectureTest extends TestCase
{
    /**
     * Caja Blanca 1: Verificación interna de sanitización contra XSS, scripts e inyección de atributos.
     */
    public function test_whitebox_input_sanitizer_strips_malicious_payloads_and_preserves_safe_fields(): void
    {
        $middleware = new SanitizeInputMiddleware();
        $req = Request::create('/api/test', 'POST', [
            'title' => "  Curso <script>alert('xss')</script> Real  ",
            'body' => 'Hola <img src=x onerror="stealCookie()"> javascript:evil() mundo',
            'nested' => [
                'note' => "<iframe src='http://malicious.site'></iframe>Texto limpio\0",
            ],
            'password' => 'Clave<script>123</script>!',
        ]);

        $response = $middleware->handle($req, fn ($r) => response()->json($r->all()));
        $cleaned = json_decode($response->getContent(), true);

        $this->assertEquals('Curso  Real', $cleaned['title']);
        $this->assertStringNotContainsString('<script>', $cleaned['title']);
        $this->assertStringNotContainsString('onerror=', $cleaned['body']);
        $this->assertStringNotContainsString('javascript:', $cleaned['body']);
        $this->assertEquals('Texto limpio', $cleaned['nested']['note']);
        // El campo password no debe corromperse
        $this->assertEquals('Clave<script>123</script>!', $cleaned['password']);
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    /**
     * Caja Blanca 2: Verificación directa de los 4 Triggers PL/pgSQL en PostgreSQL.
     */
    public function test_whitebox_postgresql_triggers_execute_atomically(): void
    {
        DB::beginTransaction();
        try {
            $user = User::where('email', 'estudiante@feder.edu.bo')->firstOrFail();
            $course = Course::firstOrFail();

            // Trigger 1: trg_increment_page_visits
            $testPath = '/ruta-prueba-trigger-' . Str::lower(Str::random(6));
            PageVisitLog::create(['page_path' => $testPath, 'page_title' => 'Test Trigger', 'ip_address' => '127.0.0.1']);
            PageVisitLog::create(['page_path' => $testPath, 'page_title' => 'Test Trigger', 'ip_address' => '127.0.0.1']);

            $visitCounter = PageVisit::find($testPath);
            $this->assertNotNull($visitCounter);
            $this->assertEquals(2, $visitCounter->hits_count);

            // Trigger 2: trg_sync_payment_plan_totals
            $plan = PaymentPlan::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'plan_type' => 'cuotas',
                'total_amount' => 300.00,
                'installments_total' => 2,
                'status' => 'pendiente',
            ]);

            PaymentTransaction::create([
                'payment_plan_id' => $plan->id,
                'user_id' => $user->id,
                'installment_number' => 1,
                'amount' => 150.00,
                'reference_code' => 'TRG-TX-' . Str::upper(Str::random(6)),
                'payment_status' => 'completado',
            ]);

            $plan->refresh();
            $this->assertEquals(150.0, $plan->paid_amount);
            $this->assertEquals(1, $plan->installments_paid);
            $this->assertEquals('parcial', $plan->status);

            PaymentTransaction::create([
                'payment_plan_id' => $plan->id,
                'user_id' => $user->id,
                'installment_number' => 2,
                'amount' => 150.00,
                'reference_code' => 'TRG-TX-' . Str::upper(Str::random(6)),
                'payment_status' => 'completado',
            ]);

            $plan->refresh();
            $this->assertEquals(300.0, $plan->paid_amount);
            $this->assertEquals(2, $plan->installments_paid);
            $this->assertEquals('pagado', $plan->status);

            // Trigger 3: trg_sync_user_active_seconds
            $beforeSecs = (int) $user->total_active_seconds;
            UsageSession::create([
                'user_id' => $user->id,
                'course_id' => null,
                'session_date' => '2025-01-01',
                'seconds_spent' => 180,
                'pings_count' => 6,
            ]);
            $user->refresh();
            $this->assertEquals($beforeSecs + 180, (int) $user->total_active_seconds);

            // Trigger 4: trg_sync_forum_replies_count
            $thread = ForumThread::create([
                'course_id' => $course->id,
                'user_id' => $user->id,
                'title' => 'Pregunta Trigger',
                'body' => 'Contenido de prueba',
            ]);
            $this->assertEquals(0, $thread->replies_count);

            $reply = ForumReply::create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'body' => 'Respuesta automática',
            ]);
            $this->assertEquals(1, $thread->fresh()->replies_count);

            $reply->delete();
            $this->assertEquals(0, $thread->fresh()->replies_count);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Caja Blanca 3: Ramas internas de EnrollmentService (Prerrequisito bloqueante, Cupos ACID y Corte 50%).
     */
    public function test_whitebox_enrollment_service_branches_prerequisites_capacity_and_cutoff(): void
    {
        DB::beginTransaction();
        try {
            $service = app(EnrollmentService::class);
            $cat = Category::firstOrFail();

            $u1 = User::create([
                'name' => 'WB Participante 1',
                'email' => 'wb1_' . Str::random(5) . '@test.bo',
                'password' => 'password123',
                'role' => 'participant',
                'is_active' => true,
            ]);
            $u2 = User::create([
                'name' => 'WB Participante 2',
                'email' => 'wb2_' . Str::random(5) . '@test.bo',
                'password' => 'password123',
                'role' => 'participant',
                'is_active' => true,
            ]);

            $baseCourse = Course::create([
                'category_id' => $cat->id,
                'title' => 'Curso Base Prerrequisito',
                'slug' => 'wb-base-' . Str::lower(Str::random(5)),
                'hook' => 'Base',
                'description' => 'Desc',
                'outcome' => 'Out',
                'capacity' => 10,
                'price' => 0,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(10)->format('Y-m-d'),
                'status' => 'published',
            ]);

            $targetCourse = Course::create([
                'category_id' => $cat->id,
                'title' => 'Curso Con Cupo 1 y Prerrequisito',
                'slug' => 'wb-target-' . Str::lower(Str::random(5)),
                'hook' => 'Target',
                'description' => 'Desc',
                'outcome' => 'Out',
                'capacity' => 1,
                'price' => 100,
                'installments_allowed' => 2,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(10)->format('Y-m-d'),
                'status' => 'published',
            ]);

            CoursePrerequisite::create([
                'course_id' => $targetCourse->id,
                'prerequisite_course_id' => $baseCourse->id,
                'is_mandatory' => true,
            ]);

            // Rama A: Bloqueado por prerrequisito no aprobado
            $resBlocked = $service->enroll($u1->id, $targetCourse->id);
            $this->assertFalse($resBlocked['ok']);
            $this->assertEquals('prerequisite_missing', $resBlocked['code']);

            // Aprobar curso base para ambos usuarios
            Enrollment::create(['user_id' => $u1->id, 'course_id' => $baseCourse->id, 'status' => 'completed']);
            Enrollment::create(['user_id' => $u2->id, 'course_id' => $baseCourse->id, 'status' => 'completed']);

            // Rama B: Inscripción exitosa con plan de 2 cuotas
            $resOk = $service->enroll($u1->id, $targetCourse->id, ['plan_type' => 'cuotas', 'installments_total' => 2]);
            $this->assertTrue($resOk['ok']);
            $this->assertEquals('enrolled', $resOk['code']);
            $this->assertEquals('parcial', $resOk['payment_plan']->status);

            // Rama C: Usuario ya inscrito recibe 'already' aunque el cupo esté lleno
            $resAlready = $service->enroll($u1->id, $targetCourse->id);
            $this->assertTrue($resAlready['ok']);
            $this->assertEquals('already', $resAlready['code']);

            // Rama D: Segundo usuario bloqueado por cupo lleno (capacity = 1)
            $resFull = $service->enroll($u2->id, $targetCourse->id);
            $this->assertFalse($resFull['ok']);
            $this->assertEquals('full', $resFull['code']);

            // Rama E: Corte por avance >= 50%
            $targetCourse->update(['capacity' => 5, 'progress_pct' => 50]);
            $resCutoff = $service->enroll($u2->id, $targetCourse->id);
            $this->assertFalse($resCutoff['ok']);
            $this->assertEquals('cutoff_reached', $resCutoff['code']);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Caja Blanca 4: Auditoría estática de higiene de código (Cero archivos muertos Vue, cero carpeta BORRAR).
     */
    public function test_whitebox_zero_dead_files_and_production_bundle_integrity(): void
    {
        $this->assertDirectoryDoesNotExist(base_path('BORRAR'));
        $this->assertFileDoesNotExist(resource_path('js/app.js'));
        $this->assertFileDoesNotExist(resource_path('js/bootstrap.js'));
        $this->assertFileExists(public_path('favicon.png'));
        $this->assertFileExists(public_path('build/manifest.json'));

        // Ningún archivo .vue residual
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('js')));
        $vueFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'vue') {
                $vueFiles[] = $file->getPathname();
            }
        }
        $this->assertEmpty($vueFiles, 'Se encontraron archivos .vue muertos: ' . implode(', ', $vueFiles));

        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $this->assertArrayHasKey('resources/js/app.jsx', $manifest);
        $this->assertArrayHasKey('resources/css/app.css', $manifest);
    }
}
