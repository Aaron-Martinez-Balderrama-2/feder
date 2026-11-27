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
     * Caja Negra 1: Puerta obligatoria de Login, Restricción +18 años, Rechazo de modo niño,
     * 4 Roles, RBAC 403, Bitácora, Preferencias Adultos y Recuperación de Contraseña.
     */
    public function test_blackbox_auth_rbac_adult_only_preferences_and_audit_trail(): void
    {
        DB::beginTransaction();
        try {
            // 1. Bloqueo 401 sin login al consultar catálogo interno o buscador interno
            $this->getJson('/api/courses')->assertStatus(401);
            $this->getJson('/api/search?q=Finanzas')->assertStatus(401);

            // Bootstrap sin sesión NO debe exponer menús internos
            $guestBoot = $this->postJson('/api/bootstrap', ['path' => '/ingresar', 'title' => 'Ingresar']);
            $guestBoot->assertStatus(200)->assertJsonCount(0, 'menus');

            // 2. Rechazo de menores de 18 años en registro
            $minorReg = $this->postJson('/api/auth/register', [
                'name' => 'Menor Prueba',
                'email' => 'menor_' . Str::lower(Str::random(4)) . '@test.bo',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'id_document' => '11223344',
                'phone' => '70011223',
                'birth_date' => now()->subYears(16)->format('Y-m-d'),
            ]);
            $minorReg->assertStatus(422);

            // 3. Intento fallido de login -> validación en Español y bitácora
            $badLogin = $this->postJson('/api/auth/login', [
                'email' => 'a3ronmartinez@gmail.com',
                'password' => 'clave_incorrecta_xyz',
            ]);
            $badLogin->assertStatus(422);

            // 4. Login exitoso como Admin
            $adminLogin = $this->postJson('/api/auth/login', [
                'email' => 'a3ronmartinez@gmail.com',
                'password' => 'password',
            ]);
            $adminLogin->assertStatus(200)->assertJsonPath('ok', true);
            $adminToken = $adminLogin->json('token');
            $this->assertNotEmpty($adminToken);

            // 5. Bootstrap autenticado devuelve menú por permisos y contador de visitas
            $bootAdmin = $this->withHeader('X-Auth-Token', $adminToken)
                ->postJson('/api/bootstrap', ['path' => '/mi-aula', 'title' => 'Mi Panel']);
            $bootAdmin->assertStatus(200)
                ->assertJsonPath('ok', true)
                ->assertJsonPath('user.role', 'admin');
            $this->assertGreaterThanOrEqual(8, count($bootAdmin->json('menus')));
            $this->assertGreaterThanOrEqual(1, $bootAdmin->json('visits.page_hits'));

            // 6. Login como Participante -> bloqueo 403 en ruta administrativa
            $partLogin = $this->postJson('/api/auth/login', [
                'email' => 'estudiante@feder.edu.bo',
                'password' => 'password',
            ]);
            $partLogin->assertStatus(200);
            $partToken = $partLogin->json('token');

            $denied = $this->withHeader('X-Auth-Token', $partToken)
                ->getJson('/api/manage/users');
            $denied->assertStatus(403)->assertJsonPath('ok', false);

            // 7. Preferencias de estilo: 'ninos' es rechazado (422) y estilos adultos funcionan (200)
            $this->withHeader('X-Auth-Token', $partToken)
                ->postJson('/api/auth/preferences', [
                    'preferred_theme' => 'ninos',
                    'preferred_color_mode' => 'dark',
                    'font_scale' => 1.0,
                    'high_contrast' => false,
                ])
                ->assertStatus(422);

            $prefRes = $this->withHeader('X-Auth-Token', $partToken)
                ->postJson('/api/auth/preferences', [
                    'preferred_theme' => 'lectura',
                    'preferred_color_mode' => 'dark',
                    'font_scale' => 1.15,
                    'high_contrast' => true,
                ]);
            $prefRes->assertStatus(200)->assertJsonPath('preferences.preferred_theme', 'lectura');

            // 8. Recuperación de contraseña completa
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

            // 9. Verificar que la bitácora registró login_failed y access_denied
            $auditRes = $this->withHeader('X-Auth-Token', $adminToken)->getJson('/api/manage/audit-logs');
            $auditRes->assertStatus(200);
            $this->assertGreaterThanOrEqual(1, $auditRes->json('summary.logins_failed'));
            $this->assertGreaterThanOrEqual(1, $auditRes->json('summary.access_denied'));
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Caja Negra 2: Ciclo completo CU2 a CU8 para Fundación Gratuita:
     * Cursos gratuitos por fases editables, edición de evaluaciones, edición/reemplazo de certificados y plantilla, foro, materiales y reportes reales.
     */
    public function test_blackbox_full_free_foundation_lifecycle_editable_evaluations_and_certificates(): void
    {
        $uploadedPathsToClean = [];
        DB::beginTransaction();
        try {
            $adminLogin = $this->postJson('/api/auth/login', ['email' => 'a3ronmartinez@gmail.com', 'password' => 'password']);
            $adminHeaders = ['X-Auth-Token' => $adminLogin->json('token')];

            $instLogin = $this->postJson('/api/auth/login', ['email' => 'docente@feder.edu.bo', 'password' => 'password']);
            $instHeaders = ['X-Auth-Token' => $instLogin->json('token')];

            // Registrar nuevo participante mayor de 18 años (CU1)
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

            // CU2: Guardar ficha de emprendimiento y validarla
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

            // CU3: Crear curso 100% gratuito sin periodo/semestre ni cuotas, con 4 fases base editables
            $catId = DB::table('categories')->value('id');
            $instructorId = User::where('email', 'docente@feder.edu.bo')->value('id');

            $courseRes = $this->withHeaders($adminHeaders)->postJson('/api/manage/courses', [
                'title' => 'Gestión Integral Textil E2E',
                'category_id' => $catId,
                'instructor_id' => $instructorId,
                'hook' => 'Mejora tu taller de confección.',
                'description' => 'Curso gratuito completo de producción y ventas.',
                'outcome' => 'Duplicar productividad.',
                'level' => 'inicial',
                'capacity' => 20,
                'modality' => 'hibrida',
                'hours_duration' => 45,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(20)->format('Y-m-d'),
                'status' => 'published',
            ]);
            $courseRes->assertStatus(201);
            $courseId = $courseRes->json('course.id');
            $this->assertEquals(0, (float) $courseRes->json('course.price'));
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

            // Inscripción 100% Gratuita de un clic
            $enroll = $this->withHeaders($stuHeaders)->postJson("/api/courses/{$courseId}/checkout", [
                'plan_type' => 'unico',
                'installments_total' => 1,
                'pay_first_now' => false,
            ]);
            $enroll->assertStatus(200)->assertJsonPath('ok', true);

            // CU4: Subir material en Fase 1 y Fase 2; verificar BLOQUEO estricto de Fase 2 hasta completar/aprobar Fase 1
            $secondModId = $modules[1]['id'];
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

            $matRes2 = $this->withHeaders($instHeaders)->post("/api/manage/courses/{$courseId}/materials", [
                'title' => 'Material Confidencial Fase 2',
                'module_id' => $secondModId,
                'resource_type' => 'guia_ligera',
                'offline_summary' => 'Solo visible tras aprobar Fase 1.',
            ], ['Accept' => 'application/json']);
            $matRes2->assertStatus(201);

            // Participante entra al aula ANTES de completar Fase 1 -> NO debe ver material de Fase 2
            $classroomLocked = $this->withHeaders($stuHeaders)->getJson("/api/classroom/{$courseId}");
            $classroomLocked->assertStatus(200)
                ->assertJsonPath('course.modules.0.is_unlocked', true)
                ->assertJsonPath('course.modules.1.is_unlocked', false);
            $matTitlesLocked = collect($classroomLocked->json('course.materials'))->pluck('title')->all();
            $this->assertContains('Plantilla de Costeo Textil', $matTitlesLocked);
            $this->assertNotContains('Material Confidencial Fase 2', $matTitlesLocked);

            // Intentar evaluar Fase 2 antes de habilitar -> bloqueado 403
            $evalMod2 = $this->withHeaders($instHeaders)->postJson("/api/manage/courses/{$courseId}/evaluations", [
                'module_id' => $secondModId,
                'title' => 'Examen Fase 2',
                'passing_score' => 60,
                'max_attempts' => 2,
                'questions' => [
                    [
                        'question_text' => 'Pregunta Fase 2',
                        'question_type' => 'true_false',
                        'options' => [['key' => 'V', 'text' => 'Verdadero'], ['key' => 'F', 'text' => 'Falso']],
                        'correct_option' => 'V',
                        'points' => 100,
                    ],
                ],
            ]);
            $evalMod2->assertStatus(201);
            $eval2Id = $evalMod2->json('evaluation.id');
            $q2Id = $evalMod2->json('evaluation.questions.0.id');

            $this->withHeaders($stuHeaders)->postJson("/api/evaluations/{$eval2Id}/attempt", [
                'answers' => [$q2Id => 'V'],
            ])->assertStatus(403);

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

            // CU5: Crear evaluación, EDITAR evaluación y preguntas, rendirla y evaluar Feria
            $evalRes = $this->withHeaders($instHeaders)->postJson("/api/manage/courses/{$courseId}/evaluations", [
                'module_id' => $firstModId,
                'title' => 'Autoevaluación Inicial',
                'passing_score' => 60,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'question_text' => 'Pregunta inicial',
                        'question_type' => 'multiple_choice',
                        'options' => [['key' => 'A', 'text' => 'Correcta'], ['key' => 'B', 'text' => 'Incorrecta']],
                        'correct_option' => 'A',
                        'points' => 100,
                    ],
                ],
            ]);
            $evalRes->assertStatus(201);
            $evalId = $evalRes->json('evaluation.id');

            // Editar la evaluación y reemplazar sus preguntas (100% editable)
            $editEvalRes = $this->withHeaders($instHeaders)->putJson("/api/manage/evaluations/{$evalId}", [
                'module_id' => $firstModId,
                'title' => 'Autoevaluación Fase 1 Editada',
                'passing_score' => 70,
                'max_attempts' => 4,
                'questions' => [
                    [
                        'question_text' => '¿Cuánto margen mínimo se sugiere cubrir?',
                        'question_type' => 'multiple_choice',
                        'options' => [['key' => 'A', 'text' => 'Solo tela'], ['key' => 'B', 'text' => 'Todos los costos más utilidad']],
                        'correct_option' => 'B',
                        'points' => 100,
                        'explanation' => 'Debes cubrir costos fijos, variables y ganancia.',
                    ],
                ],
            ]);
            $editEvalRes->assertStatus(200)->assertJsonPath('evaluation.title', 'Autoevaluación Fase 1 Editada');
            $qId = $editEvalRes->json('evaluation.questions.0.id');

            $attemptRes = $this->withHeaders($stuHeaders)->postJson("/api/evaluations/{$evalId}/attempt", [
                'answers' => [$qId => 'B'],
            ]);
            $attemptRes->assertStatus(200)
                ->assertJsonPath('attempt.percentage', 100)
                ->assertJsonPath('attempt.passed', true);

            // Instructor marca como completada la Fase 1 -> ahora que aprobó evaluación, Fase 2 y su material se desbloquean
            $this->withHeaders($instHeaders)
                ->postJson("/api/manage/modules/{$firstModId}/toggle-complete")
                ->assertStatus(200)
                ->assertJsonPath('module.is_completed', true);

            $classroomUnlocked = $this->withHeaders($stuHeaders)->getJson("/api/classroom/{$courseId}");
            $classroomUnlocked->assertStatus(200)->assertJsonPath('course.modules.1.is_unlocked', true);
            $matTitlesUnlocked = collect($classroomUnlocked->json('course.materials'))->pluck('title')->all();
            $this->assertContains('Material Confidencial Fase 2', $matTitlesUnlocked);

            $this->withHeaders($stuHeaders)
                ->postJson("/api/classroom/{$courseId}/fair-register", ['project_title' => 'Uniformes Escolares Lucía'])
                ->assertStatus(200);

            // CU6: Emitir certificado, EDITARLO / REEMPLAZAR su archivo y personalizar plantilla institucional
            $certPdf1 = UploadedFile::fake()->create('certificado_v1.pdf', 80, 'application/pdf');
            $certRes = $this->withHeaders($instHeaders)->post("/api/manage/courses/{$courseId}/certificates", [
                'user_id' => $stuId,
                'final_grade' => 92,
                'attendance_pct' => 95,
                'hours_completed' => 45,
                'printable_file' => $certPdf1,
            ], ['Accept' => 'application/json']);
            $certRes->assertStatus(200);
            $certId = $certRes->json('certificate.id');
            if ($certRes->json('certificate.custom_file_path')) {
                $uploadedPathsToClean[] = public_path($certRes->json('certificate.custom_file_path'));
            }

            // Editar certificado y reemplazar PDF como Administrador
            $certPdf2 = UploadedFile::fake()->create('certificado_reemplazado_v2.pdf', 90, 'application/pdf');
            $customQr = 'FEDER-EDIT-' . Str::upper(Str::random(5));
            $updCertRes = $this->withHeaders($adminHeaders)->post("/api/manage/certificates/{$certId}/update", [
                'verification_code' => $customQr,
                'final_grade' => 99,
                'hours_completed' => 60,
                'status' => 'valid',
                'printable_file' => $certPdf2,
            ], ['Accept' => 'application/json']);
            $updCertRes->assertStatus(200)
                ->assertJsonPath('certificate.verification_code', $customQr)
                ->assertJsonPath('certificate.hours_completed', 60);
            if ($updCertRes->json('certificate.custom_file_path')) {
                $uploadedPathsToClean[] = public_path($updCertRes->json('certificate.custom_file_path'));
            }

            // Personalizar plantilla institucional de certificados
            $this->withHeaders($adminHeaders)->postJson('/api/manage/settings', [
                'institution_name' => 'Fundación FEDER Bolivia',
                'office_address' => 'Av. Bush UAGRM',
                'office_map_embed' => '-17.776111,-63.195000',
                'certificate_header_title' => 'CERTIFICADO OFICIAL PERSONALIZADO',
                'certificate_authority' => 'Ing. Aaron Martinez',
                'certificate_authority_role' => 'Dirección General FEDER',
            ])->assertStatus(200);

            // Inspección pública mediante Código QR Y por Cédula de Identidad (CI) sin login (RF-05.1 + RF-05.3)
            $this->getJson("/api/certificates/verify/{$customQr}")
                ->assertStatus(200)
                ->assertJsonPath('valid', true)
                ->assertJsonPath('certificate.participant_name', 'Lucía Quispe Condori')
                ->assertJsonPath('settings.certificate_header_title', 'CERTIFICADO OFICIAL PERSONALIZADO');

            $this->getJson('/api/certificates/verify/9482716')
                ->assertStatus(200)
                ->assertJsonPath('valid', true)
                ->assertJsonPath('certificate.verification_code', $customQr);

            // Búsqueda global autenticada y Reportes reales + CSV sin columnas de cobros
            $this->withHeaders($adminHeaders)->getJson('/api/search?q=Textil')
                ->assertStatus(200)
                ->assertJsonPath('ok', true);

            $metricsRes = $this->withHeaders($adminHeaders)->getJson('/api/analytics/dashboard');
            $metricsRes->assertStatus(200)->assertJsonPath('ok', true);
            $this->assertGreaterThanOrEqual(1, $metricsRes->json('kpis.total_enrollments'));

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

    /**
     * Caja Negra 3 (INF513 3.2): Roles diferenciados y Menú Dinámico desde BD en función de permisos activos.
     */
    public function test_blackbox_rbac_dynamic_database_menu_and_role_differentiation(): void
    {
        DB::beginTransaction();
        try {
            $adminToken = $this->postJson('/api/auth/login', ['email' => 'a3ronmartinez@gmail.com', 'password' => 'password'])->assertStatus(200)->json('token');
            $partToken = $this->postJson('/api/auth/login', ['email' => 'estudiante@feder.edu.bo', 'password' => 'password'])->assertStatus(200)->json('token');
            $instToken = $this->postJson('/api/auth/login', ['email' => 'docente@feder.edu.bo', 'password' => 'password'])->assertStatus(200)->json('token');
            $dirToken = $this->postJson('/api/auth/login', ['email' => 'director@feder.edu.bo', 'password' => 'password'])->assertStatus(200)->json('token');

            $rbacRes = $this->withHeader('X-Auth-Token', $adminToken)->getJson('/api/manage/rbac');
            $rbacRes->assertStatus(200)->assertJsonPath('ok', true);
            $roles = collect($rbacRes->json('roles'));
            $permissions = collect($rbacRes->json('permissions'));

            // Mínimo 2 roles de negocio diferenciados (excluyendo admin): Participante, Instructor y Director Ejecutivo
            $businessRoles = $roles->where('slug', '!=', 'admin');
            $this->assertGreaterThanOrEqual(3, $businessRoles->count());

            $partBoot = $this->withHeader('X-Auth-Token', $partToken)->postJson('/api/bootstrap', ['path' => '/mi-aula'])->json('menus');
            $instBoot = $this->withHeader('X-Auth-Token', $instToken)->postJson('/api/bootstrap', ['path' => '/mi-aula'])->json('menus');
            $dirBoot = $this->withHeader('X-Auth-Token', $dirToken)->postJson('/api/bootstrap', ['path' => '/mi-aula'])->json('menus');

            $partRoutes = collect($partBoot)->pluck('route_path')->all();
            $instRoutes = collect($instBoot)->pluck('route_path')->all();
            $dirRoutes = collect($dirBoot)->pluck('route_path')->all();

            $this->assertNotContains('/gestion/cursos', $partRoutes);
            $this->assertContains('/gestion/cursos', $instRoutes);
            $this->assertNotContains('/gestion/usuarios', $instRoutes);
            $this->assertContains('/gestion/reportes', $dirRoutes);

            // Crear ítem de menú dinámico en BD sujeto a permiso metrics.view
            $createMenu = $this->withHeader('X-Auth-Token', $adminToken)->postJson('/api/manage/menus', [
                'label' => 'Resumen Ejecutivo Prueba',
                'route_path' => '/gestion/reportes',
                'icon' => 'chart',
                'permission_slug' => 'metrics.view',
                'section' => 'management',
                'sort_order' => 15,
                'is_active' => true,
            ]);
            $createMenu->assertStatus(201);
            $menuId = $createMenu->json('menu.id');

            // Director lo ve porque tiene metrics.view; Participante NO lo ve
            $dirLabels = collect($this->withHeader('X-Auth-Token', $dirToken)->postJson('/api/bootstrap', ['path' => '/mi-aula'])->json('menus'))->pluck('label')->all();
            $partLabels = collect($this->withHeader('X-Auth-Token', $partToken)->postJson('/api/bootstrap', ['path' => '/mi-aula'])->json('menus'))->pluck('label')->all();
            $this->assertContains('Resumen Ejecutivo Prueba', $dirLabels);
            $this->assertNotContains('Resumen Ejecutivo Prueba', $partLabels);

            // Ocultar el ítem en BD (is_active = false) -> desaparece inmediatamente del bootstrap
            $this->withHeader('X-Auth-Token', $adminToken)->putJson("/api/manage/menus/{$menuId}", [
                'label' => 'Resumen Ejecutivo Prueba',
                'route_path' => '/gestion/reportes',
                'icon' => 'chart',
                'permission_slug' => 'metrics.view',
                'section' => 'management',
                'sort_order' => 15,
                'is_active' => false,
            ])->assertStatus(200);

            $dirLabelsHidden = collect($this->withHeader('X-Auth-Token', $dirToken)->postJson('/api/bootstrap', ['path' => '/mi-aula'])->json('menus'))->pluck('label')->all();
            $this->assertNotContains('Resumen Ejecutivo Prueba', $dirLabelsHidden);

            // Reactivar el ítem y conceder metrics.view al rol Participante -> aparece automáticamente en su menú
            $this->withHeader('X-Auth-Token', $adminToken)->putJson("/api/manage/menus/{$menuId}", [
                'label' => 'Resumen Ejecutivo Prueba',
                'route_path' => '/gestion/reportes',
                'icon' => 'chart',
                'permission_slug' => 'metrics.view',
                'section' => 'management',
                'sort_order' => 15,
                'is_active' => true,
            ])->assertStatus(200);

            $partRole = $roles->firstWhere('slug', 'participant');
            $metricsPerm = $permissions->firstWhere('slug', 'metrics.view');
            $partPermIds = collect($partRole['permissions'])->pluck('id')->push($metricsPerm['id'])->unique()->values()->all();
            $this->withHeader('X-Auth-Token', $adminToken)->putJson("/api/manage/rbac/roles/{$partRole['id']}", [
                'permission_ids' => $partPermIds,
            ])->assertStatus(200);

            $partLabelsGranted = collect($this->withHeader('X-Auth-Token', $partToken)->postJson('/api/bootstrap', ['path' => '/mi-aula'])->json('menus'))->pluck('label')->all();
            $this->assertContains('Resumen Ejecutivo Prueba', $partLabelsGranted);

            $this->withHeader('X-Auth-Token', $adminToken)->deleteJson("/api/manage/menus/{$menuId}")->assertStatus(200);
        } finally {
            DB::rollBack();
        }
    }
}
