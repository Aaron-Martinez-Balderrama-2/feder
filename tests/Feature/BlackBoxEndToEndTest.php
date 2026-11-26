<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlackBoxEndToEndTest extends TestCase
{
    /**
     * Caja Negra 1: CU1 Autenticación API, 4 Roles, Menú Dinámico BD, RBAC 403, Bitácora, Preferencias y Recuperación.
     */
    public function test_blackbox_auth_rbac_dynamic_menus_preferences_and_audit_trail(): void
    {
        DB::beginTransaction();
        try {
            // 1. Intento fallido -> validación en Español y registro en bitácora
            $badLogin = $this->postJson('/api/auth/login', [
                'email' => 'a3ronmartinez@gmail.com',
                'password' => 'clave_incorrecta_xyz',
            ]);
            $badLogin->assertStatus(422);

            // 2. Login exitoso como Admin
            $adminLogin = $this->postJson('/api/auth/login', [
                'email' => 'a3ronmartinez@gmail.com',
                'password' => 'password',
            ]);
            $adminLogin->assertStatus(200)->assertJsonPath('ok', true);
            $adminToken = $adminLogin->json('token');
            $this->assertNotEmpty($adminToken);

            // 3. Bootstrap devuelve menú dinámico completo y contador de visitas del Footer
            $bootAdmin = $this->withHeader('X-Auth-Token', $adminToken)
                ->postJson('/api/bootstrap', ['path' => '/cursos', 'title' => 'Catálogo']);
            $bootAdmin->assertStatus(200)
                ->assertJsonPath('ok', true)
                ->assertJsonPath('user.role', 'admin');
            $this->assertGreaterThanOrEqual(8, count($bootAdmin->json('menus')));
            $this->assertGreaterThanOrEqual(1, $bootAdmin->json('visits.page_hits'));

            // 4. Login como Participante -> menú restringido y bloqueo 403 en ruta administrativa
            $partLogin = $this->postJson('/api/auth/login', [
                'email' => 'estudiante@feder.edu.bo',
                'password' => 'password',
            ]);
            $partLogin->assertStatus(200);
            $partToken = $partLogin->json('token');

            $denied = $this->withHeader('X-Auth-Token', $partToken)
                ->getJson('/api/manage/users');
            $denied->assertStatus(403)->assertJsonPath('ok', false);

            // 5. Preferencias de 3 Temas + Día/Noche + Accesibilidad WCAG
            $prefRes = $this->withHeader('X-Auth-Token', $partToken)
                ->postJson('/api/auth/preferences', [
                    'preferred_theme' => 'jovenes',
                    'preferred_color_mode' => 'dark',
                    'font_scale' => 1.15,
                    'high_contrast' => true,
                ]);
            $prefRes->assertStatus(200)->assertJsonPath('preferences.preferred_theme', 'jovenes');

            // 6. Recuperación de contraseña completa
            $forgot = $this->postJson('/api/auth/forgot-password', ['email' => 'emprendedor@feder.edu.bo']);
            $forgot->assertStatus(200);
            $code = $forgot->json('recovery_code_preview');
            $this->assertNotEmpty($code);

            $reset = $this->postJson('/api/auth/reset-password', [
                'email' => 'emprendedor@feder.edu.bo',
                'token' => $code,
                'password' => 'nuevaClaveSegura99',
                'password_confirmation' => 'nuevaClaveSegura99',
            ]);
            $reset->assertStatus(200)->assertJsonPath('ok', true);

            // 7. Verificar que la bitácora registró login_failed y access_denied
            $auditRes = $this->withHeader('X-Auth-Token', $adminToken)->getJson('/api/manage/audit-logs');
            $auditRes->assertStatus(200);
            $this->assertGreaterThanOrEqual(1, $auditRes->json('summary.logins_failed'));
            $this->assertGreaterThanOrEqual(1, $auditRes->json('summary.access_denied'));
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Caja Negra 2: Flujo integral CU2, CU3, CU4, CU5, CU6, CU7, CU8, Pagos, Foro, Materiales, QR y Reporte CSV.
     */
    public function test_blackbox_full_business_lifecycle_cu2_to_cu8_and_payments(): void
    {
        $uploadedPathsToClean = [];
        DB::beginTransaction();
        try {
            $adminLogin = $this->postJson('/api/auth/login', ['email' => 'a3ronmartinez@gmail.com', 'password' => 'password']);
            $adminHeaders = ['X-Auth-Token' => $adminLogin->json('token')];

            $instLogin = $this->postJson('/api/auth/login', ['email' => 'docente@feder.edu.bo', 'password' => 'password']);
            $instHeaders = ['X-Auth-Token' => $instLogin->json('token')];

            // Registrar nuevo participante emprendedor (CU1)
            $regRes = $this->postJson('/api/auth/register', [
                'name' => 'Lucía Quispe Condori',
                'email' => 'lucia_' . Str::lower(Str::random(5)) . '@feder.bo',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'id_document' => '9482716',
                'phone' => '71234567',
                'birth_date' => '1992-03-10',
                'city' => 'Warnes',
                'gender' => 'Femenino',
            ]);
            $regRes->assertStatus(201);
            $stuId = $regRes->json('user.id');
            $stuHeaders = ['X-Auth-Token' => $regRes->json('token')];

            // CU2: Guardar ficha de emprendimiento y verificarla
            $socSave = $this->withHeaders($stuHeaders)->postJson('/api/social/my-profile', [
                'business_name' => 'Confecciones Lucía',
                'sector' => 'Textil y Confección',
                'business_stage' => 'en_marcha',
                'employees_count' => 2,
                'municipality' => 'Warnes',
            ]);
            $socSave->assertStatus(200);
            $profId = $socSave->json('profile.id');

            $this->withHeaders($adminHeaders)
                ->postJson("/api/manage/social/profiles/{$profId}/verify", ['social_impact_notes' => 'Verificado'])
                ->assertStatus(200);

            // CU3: Crear curso verificando las 4 fases por defecto y su edición + subfases
            $catId = DB::table('categories')->value('id');
            $instructorId = User::where('email', 'docente@feder.edu.bo')->value('id');

            $courseRes = $this->withHeaders($adminHeaders)->postJson('/api/manage/courses', [
                'title' => ' Gestión Integral Textil E2E ',
                'category_id' => $catId,
                'instructor_id' => $instructorId,
                'hook' => 'Mejora tu taller de confección.',
                'description' => 'Curso completo de producción y ventas.',
                'outcome' => 'Duplicar productividad.',
                'level' => 'inicial',
                'capacity' => 20,
                'price' => 200,
                'installments_allowed' => 2,
                'modality' => 'hibrida',
                'hours_duration' => 45,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(20)->format('Y-m-d'),
                'status' => 'published',
            ]);
            $courseRes->assertStatus(201);
            $courseId = $courseRes->json('course.id');
            $modules = $courseRes->json('course.modules');
            $this->assertCount(4, $modules);
            $this->assertEquals('Modelo de negocio', $modules[0]['title']);

            // Agregar una 5ta fase y una subfase
            $mod5Res = $this->withHeaders($instHeaders)->postJson("/api/manage/courses/{$courseId}/modules", [
                'title' => 'Control de Calidad Textil',
            ]);
            $mod5Res->assertStatus(201);
            $firstModId = $modules[0]['id'];

            $subRes = $this->withHeaders($instHeaders)->postJson("/api/manage/modules/{$firstModId}/classes", [
                'title' => 'Subfase 1.1: Costeo de Prendas',
                'summary_light_text' => 'Guía ligera para internet móvil lento.',
            ]);
            $subRes->assertStatus(201);
            $classId = $subRes->json('class.id');

            // Req 10: Inscripción de Lucía en Plan de 2 Cuotas y pago de la 2da cuota
            $checkout = $this->withHeaders($stuHeaders)->postJson("/api/courses/{$courseId}/checkout", [
                'plan_type' => 'cuotas',
                'installments_total' => 2,
                'pay_first_now' => true,
                'new_method' => [
                    'method_type' => 'qr_banca',
                    'provider_name' => 'Banco Unión QR',
                    'masked_reference' => 'QR-9988',
                    'holder_name' => 'Lucía Quispe',
                ],
            ]);
            $checkout->assertStatus(200)->assertJsonPath('payment_plan.status', 'parcial');
            $planId = $checkout->json('payment_plan.id');

            $pay2 = $this->withHeaders($stuHeaders)->postJson("/api/payments/plans/{$planId}/pay", []);
            $pay2->assertStatus(200)->assertJsonPath('plan.status', 'pagado');

            // CU4: Subir material por fase y subfase, foro de preguntas, asistencia y pulso de tiempo
            $fakePdf = UploadedFile::fake()->create('guia_costeo.pdf', 120, 'application/pdf');
            $matRes = $this->withHeaders($instHeaders)->post("/api/manage/courses/{$courseId}/materials", [
                'title' => 'Plantilla de Costeo Textil',
                'module_id' => $firstModId,
                'class_id' => $classId,
                'resource_type' => 'documento',
                'offline_summary' => 'Resumen de 3 pasos.',
                'file' => $fakePdf,
            ], ['Accept' => 'application/json']);
            $matRes->assertStatus(201)->assertJsonPath('material.bandwidth_tier', 'ultra_ligero');
            if ($matRes->json('material.file_path')) {
                $uploadedPathsToClean[] = public_path($matRes->json('material.file_path'));
            }

            $threadRes = $this->withHeaders($stuHeaders)->postJson("/api/classroom/{$courseId}/forum", [
                'title' => '¿Cómo calculo merma de tela?',
                'body' => 'Tengo duda sobre el porcentaje de desperdicio al cortar.',
                'class_id' => $classId,
            ]);
            $threadRes->assertStatus(201);
            $threadId = $threadRes->json('thread.id');

            $this->withHeaders($instHeaders)
                ->postJson("/api/forum/threads/{$threadId}/replies", ['body' => 'Agrega un 8% sobre el metraje base.'])
                ->assertStatus(201)
                ->assertJsonPath('replies_count', 1);

            $this->withHeaders($instHeaders)
                ->postJson("/api/manage/classes/{$classId}/schedule-attendance", ['mode' => 'immediate', 'duration_minutes' => 30])
                ->assertStatus(200);

            $this->withHeaders($stuHeaders)
                ->postJson("/api/classes/{$classId}/mark-attendance")
                ->assertStatus(200);

            $this->withHeaders($stuHeaders)
                ->postJson('/api/usage/heartbeat', ['seconds' => 60, 'course_id' => $courseId, 'path' => "/aula/{$courseId}"])
                ->assertStatus(200);

            // CU5: Evaluación con calificación automática y Feria
            $evalRes = $this->withHeaders($instHeaders)->postJson("/api/manage/courses/{$courseId}/evaluations", [
                'module_id' => $firstModId,
                'title' => 'Examen Fase 1',
                'passing_score' => 60,
                'max_attempts' => 2,
                'questions' => [
                    [
                        'question_text' => '¿Cuánto margen mínimo se sugiere cubrir?',
                        'question_type' => 'multiple_choice',
                        'options' => [['key' => 'A', 'text' => 'Todos los costos más utilidad'], ['key' => 'B', 'text' => 'Solo tela']],
                        'correct_option' => 'A',
                        'points' => 100,
                    ],
                ],
            ]);
            $evalRes->assertStatus(201);
            $evalId = $evalRes->json('evaluation.id');
            $qId = $evalRes->json('evaluation.questions.0.id');

            $attemptRes = $this->withHeaders($stuHeaders)->postJson("/api/evaluations/{$evalId}/attempt", [
                'answers' => [$qId => 'A'],
            ]);
            $attemptRes->assertStatus(200)
                ->assertJsonPath('attempt.percentage', 100)
                ->assertJsonPath('attempt.passed', true);

            $this->withHeaders($stuHeaders)
                ->postJson("/api/classroom/{$courseId}/fair-register", ['project_title' => 'Uniformes Escolares Lucía'])
                ->assertStatus(200);

            $this->withHeaders($instHeaders)
                ->postJson("/api/manage/courses/{$courseId}/fair-evaluate", [
                    'user_id' => $stuId,
                    'project_title' => 'Uniformes Escolares Lucía',
                    'stand_code' => 'B-12',
                    'rubric_score' => 95,
                    'participation_status' => 'destacado',
                ])
                ->assertStatus(200);

            // CU6: Emitir certificado imprimible y verificar públicamente su código QR sin sesión
            $certPdf = UploadedFile::fake()->create('certificado_firmado.pdf', 80, 'application/pdf');
            $certRes = $this->withHeaders($instHeaders)->post("/api/manage/courses/{$courseId}/certificates", [
                'user_id' => $stuId,
                'final_grade' => 97,
                'attendance_pct' => 100,
                'hours_completed' => 45,
                'printable_file' => $certPdf,
            ], ['Accept' => 'application/json']);
            $certRes->assertStatus(200);
            $qrCode = $certRes->json('certificate.verification_code');
            if ($certRes->json('certificate.custom_file_path')) {
                $uploadedPathsToClean[] = public_path($certRes->json('certificate.custom_file_path'));
            }

            // Inspección pública sin token
            $this->getJson("/api/certificates/verify/{$qrCode}")
                ->assertStatus(200)
                ->assertJsonPath('valid', true)
                ->assertJsonPath('certificate.participant_name', 'Lucía Quispe Condori');

            // Req 9 + CU7 + CU8: Búsqueda global predictiva y Reportes reales + CSV
            $this->getJson('/api/search?q=Textil')
                ->assertStatus(200)
                ->assertJsonPath('ok', true);

            $metricsRes = $this->withHeaders($adminHeaders)->getJson('/api/analytics/dashboard');
            $metricsRes->assertStatus(200)->assertJsonPath('ok', true);
            $this->assertGreaterThan(0, $metricsRes->json('kpis.total_revenue_bob'));

            $csvRes = $this->withHeaders($adminHeaders)->get('/api/analytics/export-csv');
            $csvRes->assertStatus(200);
            $this->assertStringContainsString('text/csv', (string) $csvRes->headers->get('Content-Type'));
        } finally {
            DB::rollBack();
            foreach ($uploadedPathsToClean as $p) {
                if (file_exists($p)) {
                    @unlink($p);
                }
            }
        }
    }
}
