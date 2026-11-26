<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAttendance;
use App\Models\CourseClass;
use App\Models\CourseMaterial;
use App\Models\CourseModule;
use App\Models\CoursePrerequisite;
use App\Models\Enrollment;
use App\Models\EntrepreneurProfile;
use App\Models\EvaluationAttempt;
use App\Models\EvaluationQuestion;
use App\Models\FairParticipation;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\MenuItem;
use App\Models\ModuleEvaluation;
use App\Models\PaymentMethod;
use App\Models\PaymentPlan;
use App\Models\PaymentTransaction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SocialOrganization;
use App\Models\UsageSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Limpieza de posibles registros temporales de pruebas previas
        $like = \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        Course::where('title', $like, '%Test Ciclo%')->orWhere('title', $like, '%Capacidad Limitada ACID%')->delete();
        Category::where('name', $like, '%Test Ciclo%')->delete();

        // 1. Ajustes institucionales y plantilla de certificado dinámicos (100% editables desde panel)
        $settings = [
            'institution_name' => 'FEDER · Fundación de Educación y Desarrollo Empresarial',
            'institution_tagline' => 'Formación gratuita, práctica y estructurada por fases para microempresarios',
            'office_address' => 'Av. Bush, Ciudad Universitaria UAGRM · Módulo 236, Santa Cruz de la Sierra, Bolivia',
            'office_phone' => '+591 70031990',
            'office_email' => 'contacto@feder.edu.bo',
            'office_hours' => 'Lunes a Viernes 08:30 - 18:30 · Sábados 09:00 - 13:00',
            'office_map_embed' => '-17.776111,-63.195000',
            'certificate_header_title' => 'CERTIFICACIÓN DE FORMACIÓN EMPRESARIAL',
            'certificate_intro_text' => 'Otorga el presente certificado a:',
            'certificate_body_text' => 'Por haber completado y aprobado satisfactoriamente las fases prácticas, actividades y evaluaciones del programa gratuito de formación empresarial:',
            'certificate_authority' => 'Dra. Elena Rojas',
            'certificate_authority_role' => 'Directora Ejecutiva · Fundación FEDER',
            'certificate_secondary_authority' => 'Coordinación Académica FEDER',
            'certificate_secondary_role' => 'Registro y Certificación Oficial',
            'default_phases_json' => json_encode(Course::DEFAULT_PHASES, JSON_UNESCAPED_UNICODE),
        ];
        foreach ($settings as $k => $v) {
            Setting::setVal($k, $v);
        }

        // 2. Roles del Sistema (CU1 + INF513 Req 2: Admin + 3 roles de negocio diferenciados)
        $rolesData = [
            'admin' => [
                'name' => 'Administrador',
                'description' => 'Control total de usuarios, matriz RBAC, menús, configuración, pagos y bitácora.',
                'is_system' => true,
            ],
            'director' => [
                'name' => 'Director Ejecutivo',
                'description' => 'Supervisión estratégica, métricas LMS, vinculación social, reportes y certificados.',
                'is_system' => true,
            ],
            'instructor' => [
                'name' => 'Instructor',
                'description' => 'Operación de aula virtual, fases y subfases, materiales, evaluaciones, feria y asistencia.',
                'is_system' => true,
            ],
            'participant' => [
                'name' => 'Participante',
                'description' => 'Microempresario o estudiante en formación, aula virtual, foros, pagos y autogestión de certificados.',
                'is_system' => true,
            ],
        ];

        $roles = [];
        foreach ($rolesData as $slug => $data) {
            $roles[$slug] = Role::updateOrCreate(['slug' => $slug], $data);
        }

        // 3. Catálogo de Permisos Granulares (Matriz RBAC)
        $permissionsData = [
            ['slug' => 'classroom.learn', 'name' => 'Acceder a Mi Aula, Foros y Progreso Personal', 'module' => 'Aula Virtual'],
            ['slug' => 'payments.own', 'name' => 'Gestionar Mis Pagos, Métodos y Planes de Cuotas', 'module' => 'Pagos'],
            ['slug' => 'social.own', 'name' => 'Registrar Ficha de Emprendimiento y Vinculación', 'module' => 'Vinculación Social'],
            ['slug' => 'certificates.own', 'name' => 'Autogestionar e Imprimir Mis Certificados QR', 'module' => 'Certificados'],
            ['slug' => 'classroom.operate', 'name' => 'Operar Fases, Subfases, Materiales y Asistencias', 'module' => 'Aula Virtual'],
            ['slug' => 'evaluations.manage', 'name' => 'Gestionar Evaluaciones Automáticas y Feria Empresarial', 'module' => 'Evaluación y Feria'],
            ['slug' => 'certificates.manage', 'name' => 'Emitir y Subir Certificados Imprimibles', 'module' => 'Certificados'],
            ['slug' => 'curriculum.manage', 'name' => 'Gestionar Ofertas, Categorías, Periodos y Prerrequisitos', 'module' => 'Estructura Curricular'],
            ['slug' => 'social.manage', 'name' => 'Gestionar Convenios y Validar Vinculación Social', 'module' => 'Vinculación Social'],
            ['slug' => 'metrics.view', 'name' => 'Ver Métricas LMS, Demografía e Impacto Social', 'module' => 'Analítica y Reportes'],
            ['slug' => 'reports.export', 'name' => 'Generar y Exportar Reportes Estadísticos Reales', 'module' => 'Analítica y Reportes'],
            ['slug' => 'payments.manage', 'name' => 'Supervisar Transacciones e Ingresos de Pagos', 'module' => 'Pagos'],
            ['slug' => 'users.manage', 'name' => 'Administrar Usuarios, Fotos y Roles (CU1)', 'module' => 'Seguridad y Usuarios'],
            ['slug' => 'rbac.manage', 'name' => 'Configurar Matriz RBAC, Menú Dinámico y Sede', 'module' => 'Seguridad y Usuarios'],
            ['slug' => 'audit.view', 'name' => 'Inspeccionar Bitácora de Auditoría y Accesos', 'module' => 'Seguridad y Usuarios'],
        ];

        $perms = [];
        foreach ($permissionsData as $p) {
            $perms[$p['slug']] = Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // Asignación RBAC por rol
        $roles['admin']->permissions()->sync(array_values(array_map(fn ($m) => $m->id, $perms)));

        $roles['director']->permissions()->sync([
            $perms['classroom.learn']->id,
            $perms['curriculum.manage']->id,
            $perms['social.manage']->id,
            $perms['evaluations.manage']->id,
            $perms['certificates.manage']->id,
            $perms['metrics.view']->id,
            $perms['reports.export']->id,
            $perms['audit.view']->id,
            $perms['classroom.operate']->id,
        ]);

        $roles['instructor']->permissions()->sync([
            $perms['classroom.operate']->id,
            $perms['evaluations.manage']->id,
            $perms['certificates.manage']->id,
            $perms['metrics.view']->id,
            $perms['classroom.learn']->id,
        ]);

        $roles['participant']->permissions()->sync([
            $perms['classroom.learn']->id,
            $perms['social.own']->id,
            $perms['certificates.own']->id,
        ]);

        // 4. Navegación lateral filtrada automáticamente por permisos del rol activo
        MenuItem::query()->delete();
        $menus = [
            ['label' => 'Mi Panel', 'route_path' => '/mi-aula', 'icon' => 'dashboard', 'permission_slug' => 'classroom.learn', 'section' => 'workspace', 'sort_order' => 10],
            ['label' => 'Catálogo de Cursos', 'route_path' => '/cursos', 'icon' => 'book', 'permission_slug' => 'classroom.learn', 'section' => 'workspace', 'sort_order' => 20],
            ['label' => 'Mi Emprendimiento', 'route_path' => '/vinculacion', 'icon' => 'briefcase', 'permission_slug' => 'social.own', 'section' => 'workspace', 'sort_order' => 30],
            ['label' => 'Sede Institucional', 'route_path' => '/sede', 'icon' => 'map-pin', 'permission_slug' => 'classroom.learn', 'section' => 'workspace', 'sort_order' => 40],
            ['label' => 'Cursos y Fases', 'route_path' => '/gestion/cursos', 'icon' => 'layers', 'permission_slug' => 'classroom.operate', 'section' => 'management', 'sort_order' => 50],
            ['label' => 'Certificados QR', 'route_path' => '/gestion/certificados', 'icon' => 'award', 'permission_slug' => 'certificates.manage', 'section' => 'management', 'sort_order' => 60],
            ['label' => 'Vinculación Social', 'route_path' => '/gestion/social', 'icon' => 'handshake', 'permission_slug' => 'social.manage', 'section' => 'management', 'sort_order' => 70],
            ['label' => 'Reportes e Impacto', 'route_path' => '/gestion/reportes', 'icon' => 'chart', 'permission_slug' => 'metrics.view', 'section' => 'management', 'sort_order' => 80],
            ['label' => 'Usuarios y Roles', 'route_path' => '/gestion/usuarios', 'icon' => 'users', 'permission_slug' => 'users.manage', 'section' => 'management', 'sort_order' => 90],
            ['label' => 'Seguridad y Ajustes', 'route_path' => '/gestion/seguridad', 'icon' => 'shield', 'permission_slug' => 'rbac.manage', 'section' => 'management', 'sort_order' => 100],
        ];

        foreach ($menus as $m) {
            MenuItem::updateOrCreate(
                ['route_path' => $m['route_path']],
                array_merge($m, ['is_active' => true])
            );
        }

        // 5. Usuarios de los 4 Roles Oficiales (CU1)
        $admin = User::updateOrCreate(
            ['email' => 'a3ronmartinez@gmail.com'],
            [
                'name' => 'Aaron Martinez',
                'password' => 'password',
                'role' => 'admin',
                'role_id' => $roles['admin']->id,
                'id_document' => '12954604',
                'phone' => '70031990',
                'birth_date' => '2002-07-16',
                'city' => 'Santa Cruz de la Sierra',
                'education_level' => 'Universitario',
                'gender' => 'Masculino',
                'is_active' => true,
            ]
        );

        $director = User::updateOrCreate(
            ['email' => 'director@feder.edu.bo'],
            [
                'name' => 'Dra. Elena Rojas',
                'password' => 'password',
                'role' => 'director',
                'role_id' => $roles['director']->id,
                'id_document' => '4589210',
                'phone' => '71044520',
                'birth_date' => '1980-04-12',
                'city' => 'Santa Cruz de la Sierra',
                'education_level' => 'Postgrado',
                'gender' => 'Femenino',
                'is_active' => true,
            ]
        );

        $instructor = User::updateOrCreate(
            ['email' => 'docente@feder.edu.bo'],
            [
                'name' => 'Lic. Carlos Mendoza',
                'password' => 'password',
                'role' => 'instructor',
                'role_id' => $roles['instructor']->id,
                'id_document' => '5210394',
                'phone' => '72188901',
                'birth_date' => '1986-09-23',
                'city' => 'Santa Cruz de la Sierra',
                'education_level' => 'Licenciatura',
                'gender' => 'Masculino',
                'is_active' => true,
            ]
        );

        $participant1 = User::updateOrCreate(
            ['email' => 'estudiante@feder.edu.bo'],
            [
                'name' => 'María Fernanda Suárez',
                'password' => 'password',
                'role' => 'participant',
                'role_id' => $roles['participant']->id,
                'id_document' => '8392014',
                'phone' => '75011223',
                'birth_date' => '1993-05-18',
                'city' => 'Montero',
                'education_level' => 'Técnico Medio',
                'gender' => 'Femenino',
                'is_active' => true,
            ]
        );

        $participant2 = User::updateOrCreate(
            ['email' => 'emprendedor@feder.edu.bo'],
            [
                'name' => 'Roberto Choque Mamani',
                'password' => 'password',
                'role' => 'participant',
                'role_id' => $roles['participant']->id,
                'id_document' => '7410283',
                'phone' => '76390412',
                'birth_date' => '1978-11-03',
                'city' => 'El Torno',
                'education_level' => 'Secundaria',
                'gender' => 'Masculino',
                'is_active' => true,
            ]
        );

        // Normalizar usuarios existentes con rol 'student' al rol 'participant'
        User::where('role', 'student')->update([
            'role' => 'participant',
            'role_id' => $roles['participant']->id,
        ]);

        // 6. Organizaciones de Vinculación Social y Perfiles Emprendedores (CU2)
        $org1 = SocialOrganization::updateOrCreate(
            ['name' => 'Asociación de Mujeres Emprendedoras del Norte Integrado'],
            [
                'organization_type' => 'asociacion',
                'municipality' => 'Montero',
                'department' => 'Santa Cruz',
                'contact_name' => 'Sra. Juana Salvatierra',
                'contact_phone' => '71620394',
                'agreement_status' => 'activo',
                'beneficiaries_target' => 80,
                'notes' => 'Convenio de formación productiva y acceso a ferias municipales.',
            ]
        );

        $org2 = SocialOrganization::updateOrCreate(
            ['name' => 'Cámara Artesanal y Gastronómica El Torno'],
            [
                'organization_type' => 'comunidad',
                'municipality' => 'El Torno',
                'department' => 'Santa Cruz',
                'contact_name' => 'Sr. Mario Flores',
                'contact_phone' => '73109482',
                'agreement_status' => 'activo',
                'beneficiaries_target' => 60,
                'notes' => 'Capacitación en costos, formalización legal y ventas por WhatsApp.',
            ]
        );

        EntrepreneurProfile::updateOrCreate(
            ['user_id' => $participant1->id],
            [
                'social_organization_id' => $org1->id,
                'business_name' => 'Panadería y Repostería Artesanal Doña Fer',
                'sector' => 'Gastronomía y Alimentos',
                'business_stage' => 'en_marcha',
                'employees_count' => 3,
                'monthly_income_range' => '3.000 - 7.000 Bs.',
                'municipality' => 'Montero',
                'vulnerability_group' => 'Jefatura de hogar femenina',
                'social_impact_notes' => 'Proveedora local de desayunos y repostería tradicional.',
                'verified_by' => $director->id,
                'verified_at' => now()->subDays(4),
            ]
        );

        EntrepreneurProfile::updateOrCreate(
            ['user_id' => $participant2->id],
            [
                'social_organization_id' => $org2->id,
                'business_name' => 'Carpintería Metálica y Muebles Choque',
                'sector' => 'Manufactura y Talleres',
                'business_stage' => 'consolidacion',
                'employees_count' => 4,
                'monthly_income_range' => '7.000 - 15.000 Bs.',
                'municipality' => 'El Torno',
                'vulnerability_group' => 'Emprendimiento rural productivo',
                'social_impact_notes' => 'Genera empleo joven técnico en su municipio.',
                'verified_by' => $director->id,
                'verified_at' => now()->subDays(2),
            ]
        );

        // 7. Periodos Académicos y Categorías (CU3)
        $period = AcademicPeriod::updateOrCreate(
            ['code' => '2026-II'],
            [
                'name' => 'Convocatoria Semestral 2026-II',
                'start_date' => '2026-08-01',
                'end_date' => '2026-12-20',
                'is_active' => true,
            ]
        );

        $categoriesData = [
            ['name' => 'Gestión de Microempresas', 'slug' => 'gestion-microempresas', 'icon' => 'briefcase', 'description' => 'Bases prácticas para ordenar y hacer crecer tu negocio.'],
            ['name' => 'Finanzas Empresariales', 'slug' => 'finanzas-corporativas', 'icon' => 'coin', 'description' => 'Control de caja, costos reales, precios y ahorro empresarial.'],
            ['name' => 'Marketing Digital', 'slug' => 'marketing-digital', 'icon' => 'target', 'description' => 'Ventas prácticas por redes sociales, catálogo digital y atención al cliente.'],
            ['name' => 'Formalización y Legal', 'slug' => 'liderazgo-y-rrhh', 'icon' => 'shield', 'description' => 'NIT, licencias, contratos simples y protección legal del negocio.'],
            ['name' => 'Herramientas Digitales', 'slug' => 'ciencia-de-datos', 'icon' => 'brain', 'description' => 'Herramientas digitales sencillas para controlar inventario y ventas.'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::updateOrCreate(['slug' => $c['slug']], array_merge($c, ['is_active' => true]));
        }

        // 8. Ofertas Gratuitas de Cursos con las 4 Fases Estándar Editables de la Fundación
        $coursesSeed = [
            [
                'slug' => 'escuela-inicial-para-microempresarios',
                'category_slug' => 'gestion-microempresas',
                'title' => 'Escuela Inicial para Microempresarios',
                'hook' => 'Ordena tus cuentas, atrae más clientes y formaliza tu negocio paso a paso sin complicaciones.',
                'description' => 'Programa gratuito práctico diseñado especialmente para dueños de negocios, talleres, comercios y emprendimientos familiares que buscan resultados claros.',
                'outcome' => 'Tener tu plan de negocio, control de ganancias, catálogo digital y requisitos legales al día.',
                'level' => 'inicial',
                'capacity' => 35,
                'price' => 0.00,
                'installments_allowed' => 1,
                'modality' => 'hibrida',
                'classroom_location' => 'Aula Planta Baja · Sede FEDER Módulo 236',
                'hours_duration' => 60,
                'requires_fair_participation' => true,
                'cover_image' => 'assets/img/demo/course1.png',
                'color' => '#F7780F',
                'start_date' => now()->subDays(12)->format('Y-m-d'),
                'end_date' => now()->addDays(35)->format('Y-m-d'),
                'status' => 'published',
            ],
            [
                'slug' => 'finanzas-para-no-financieros',
                'category_slug' => 'finanzas-corporativas',
                'title' => 'Finanzas Prácticas para tu Negocio',
                'hook' => 'Separa el dinero de tu casa del dinero de tu negocio y calcula tu ganancia real cada semana.',
                'description' => 'Aprende con ejemplos cotidianos cómo fijar precios sin perder dinero, controlar deudas y ordenar la caja de tu emprendimiento.',
                'outcome' => 'Dominar tu registro de flujo de caja, costos unitarios y punto de equilibrio.',
                'level' => 'inicial',
                'capacity' => 30,
                'price' => 0.00,
                'installments_allowed' => 1,
                'modality' => 'presencial',
                'classroom_location' => 'Sala Taller 2 · Sede FEDER',
                'hours_duration' => 48,
                'requires_fair_participation' => false,
                'cover_image' => 'assets/img/demo/course2.png',
                'color' => '#13AC44',
                'start_date' => now()->subDays(7)->format('Y-m-d'),
                'end_date' => now()->addDays(28)->format('Y-m-d'),
                'status' => 'published',
            ],
            [
                'slug' => 'estrategias-de-ventas-digitales',
                'category_slug' => 'marketing-digital',
                'title' => 'Marketing Digital y Ventas en Redes Sociales',
                'hook' => 'Aprende a fotografiar tus productos, publicar ofertas claras y atender pedidos con rapidez.',
                'description' => 'Curso gratuito aplicado de principio a fin para promocionar tu negocio usando herramientas digitales sencillas desde tu teléfono móvil.',
                'outcome' => 'Tener tu catálogo digital listo y lanzar tu primera campaña comercial local.',
                'level' => 'intermedio',
                'capacity' => 30,
                'price' => 0.00,
                'installments_allowed' => 1,
                'modality' => 'virtual',
                'classroom_location' => 'Aula Virtual FEDER',
                'hours_duration' => 50,
                'requires_fair_participation' => true,
                'cover_image' => 'assets/img/demo/course3.png',
                'color' => '#334155',
                'start_date' => now()->subDays(3)->format('Y-m-d'),
                'end_date' => now()->addDays(40)->format('Y-m-d'),
                'status' => 'published',
            ],
        ];

        $createdCourses = [];
        foreach ($coursesSeed as $cs) {
            $cat = $categories[$cs['category_slug']];
            unset($cs['category_slug']);
            $cs['category_id'] = $cat->id;
            $cs['instructor_id'] = $instructor->id;
            $cs['period_id'] = $period->id;
            $cs['meet_url'] = 'https://meet.google.com/ueb-egjg-mvw';
            $cs['whatsapp_url'] = null;

            $course = Course::updateOrCreate(['slug' => $cs['slug']], $cs);
            $createdCourses[$cs['slug']] = $course;

            // Las 4 fases estándar pedidas por el cliente (100% editables posteriormente)
            if ($course->modules()->count() === 0) {
                $prevModuleId = null;
                foreach (Course::DEFAULT_PHASES as $idx => $phaseName) {
                    $mod = CourseModule::create([
                        'course_id' => $course->id,
                        'title' => $phaseName,
                        'description' => "Contenidos prácticos y actividades guiadas de {$phaseName}.",
                        'position' => $idx + 1,
                        'prerequisite_module_id' => $prevModuleId,
                        'weight_pct' => 25,
                    ]);
                    $prevModuleId = $mod->id;

                    // 2 Subfases/clases iniciales por fase con resumen para baja conectividad
                    for ($sub = 1; $sub <= 2; $sub++) {
                        $done = ($idx === 0 && $sub === 1);
                        $cls = CourseClass::create([
                            'module_id' => $mod->id,
                            'title' => "Subfase " . ($idx + 1) . ".{$sub}: Práctica de {$phaseName}",
                            'description' => "Sesión paso a paso enfocada en aplicar {$phaseName} directamente en tu emprendimiento.",
                            'position' => $sub,
                            'scheduled_at' => now()->addDays(($idx * 5) + $sub),
                            'attendance_opens_at' => now()->subHours(2),
                            'attendance_expires_at' => now()->addDays(3),
                            'summary_light_text' => "Resumen ligero (Modo Ahorro de Datos): 1) Identifica tu cliente principal. 2) Registra tus ingresos y gastos del día en una sola hoja. 3) Aplica lo aprendido hoy en tu taller o tienda.",
                            'is_completed' => $done,
                            'completed_at' => $done ? now()->subDays(2) : null,
                        ]);

                        if ($idx === 0 && $sub === 1) {
                            CourseMaterial::firstOrCreate(
                                ['course_id' => $course->id, 'title' => "Guía Rápida Imprimible - {$phaseName}"],
                                [
                                    'module_id' => $mod->id,
                                    'class_id' => $cls->id,
                                    'resource_type' => 'guia_ligera',
                                    'external_url' => 'https://www.oitcinterfor.org/recursos-mipymes',
                                    'file_size_bytes' => 95 * 1024,
                                    'bandwidth_tier' => 'ultra_ligero',
                                    'offline_summary' => 'Plantilla resumida de 1 página lista para leer sin consumir megas.',
                                    'uploaded_by' => $instructor->id,
                                ]
                            );
                        }
                    }

                    // Crear evaluación automática en la Fase 1
                    if ($idx === 0) {
                        $eval = ModuleEvaluation::firstOrCreate(
                            ['course_id' => $course->id, 'module_id' => $mod->id],
                            [
                                'title' => "Autoevaluación Práctica: {$phaseName}",
                                'instructions' => 'Responde estas preguntas sencillas para comprobar lo aprendido. Se califica al instante.',
                                'passing_score' => 60,
                                'max_attempts' => 3,
                                'is_published' => true,
                            ]
                        );

                        if ($eval->questions()->count() === 0) {
                            EvaluationQuestion::create([
                                'evaluation_id' => $eval->id,
                                'question_text' => '¿Por qué es importante separar el dinero familiar del dinero del negocio?',
                                'question_type' => 'multiple_choice',
                                'options' => [
                                    ['key' => 'A', 'text' => 'Para saber con exactitud si el negocio realmente deja ganancia y poder reinvertir.'],
                                    ['key' => 'B', 'text' => 'No es necesario separarlo si el negocio es pequeño.'],
                                    ['key' => 'C', 'text' => 'Solo sirve para empresas grandes con más de 50 empleados.'],
                                ],
                                'correct_option' => 'A',
                                'points' => 50,
                                'explanation' => 'Separar las cuentas evita descapitalizar tu negocio y te muestra tu ganancia verdadera.',
                                'position' => 1,
                            ]);

                            EvaluationQuestion::create([
                                'evaluation_id' => $eval->id,
                                'question_text' => 'Conocer a quién le vendes (tu cliente ideal) ayuda a ofrecer mejores productos y precios.',
                                'question_type' => 'true_false',
                                'options' => [
                                    ['key' => 'V', 'text' => 'Verdadero'],
                                    ['key' => 'F', 'text' => 'Falso'],
                                ],
                                'correct_option' => 'V',
                                'points' => 50,
                                'explanation' => 'Identificar qué necesita tu cliente te permite vender más sin desperdiciar mercadería.',
                                'position' => 2,
                            ]);
                        }
                    }
                }
                $course->recalculateProgress();
            }
        }

        // Configurar Prerrequisito Bloqueante de ejemplo (CU3): Ventas Digitales (intermedio) sugiere/requiere Escuela Inicial si se configura
        // Dejamos Ventas Digitales abierto y creamos una relación editable o en un curso avanzado si existe
        $advCourse = Course::where('slug', 'big-data-y-analitica-con-python')->first();
        if ($advCourse && isset($createdCourses['escuela-inicial-para-microempresarios'])) {
            $advCourse->update([
                'period_id' => $period->id,
                'price' => 0.00,
                'installments_allowed' => 1,
                'cover_image' => 'assets/img/demo/course2.png',
                'color' => '#F7780F',
            ]);
            CoursePrerequisite::firstOrCreate([
                'course_id' => $advCourse->id,
                'prerequisite_course_id' => $createdCourses['escuela-inicial-para-microempresarios']->id,
            ], [
                'is_mandatory' => true,
                'notes' => 'Requiere haber completado la Escuela Inicial para Microempresarios.',
            ]);
        }

        // 9. Matrículas Gratuitas, Foro, Evaluación, Feria, Tiempo de Uso y Certificado QR (Datos reales en BD)
        $mainCourse = $createdCourses['escuela-inicial-para-microempresarios'];
        $secondCourse = $createdCourses['finanzas-para-no-financieros'];

        $enr1 = Enrollment::updateOrCreate(
            ['user_id' => $participant1->id, 'course_id' => $mainCourse->id],
            ['status' => 'completed', 'enrolled_at' => now()->subDays(10), 'completed_at' => now()->subDay()]
        );

        $enr2 = Enrollment::updateOrCreate(
            ['user_id' => $participant2->id, 'course_id' => $mainCourse->id],
            ['status' => 'active', 'enrolled_at' => now()->subDays(8)]
        );

        $enr3 = Enrollment::updateOrCreate(
            ['user_id' => $participant1->id, 'course_id' => $secondCourse->id],
            ['status' => 'active', 'enrolled_at' => now()->subDays(4)]
        );

        PaymentTransaction::query()->delete();
        PaymentPlan::query()->delete();

        // Asistencia real registrada
        $firstClass = $mainCourse->classes()->first();
        if ($firstClass) {
            CourseAttendance::firstOrCreate(
                ['user_id' => $participant1->id, 'class_id' => $firstClass->id],
                ['status' => 'presente', 'recorded_by' => $instructor->id, 'created_at' => now()->subDays(5)]
            );
            CourseAttendance::firstOrCreate(
                ['user_id' => $participant2->id, 'class_id' => $firstClass->id],
                ['status' => 'presente', 'recorded_by' => $instructor->id, 'created_at' => now()->subDays(5)]
            );
        }

        // Hilo de Foro de Preguntas en la clase/curso (activa Trigger PostgreSQL trg_sync_forum_replies_count)
        $thread = ForumThread::firstOrCreate(
            ['course_id' => $mainCourse->id, 'title' => '¿Cómo calculo el costo de mi mano de obra en repostería?'],
            [
                'module_id' => $mainCourse->modules()->first()?->id,
                'class_id' => $firstClass?->id,
                'user_id' => $participant1->id,
                'body' => 'Buenas tardes profe Carlos, siempre cobro solo los ingredientes y el gas, pero no sé cuánto anotarme por mis horas de trabajo. ¿Cómo me recomienda hacerlo fácil?',
                'is_pinned' => true,
                'is_resolved' => true,
            ]
        );

        ForumReply::firstOrCreate(
            ['thread_id' => $thread->id, 'user_id' => $instructor->id],
            [
                'body' => '¡Excelente pregunta María Fernanda! Define un sueldo mensual meta para ti, divídelo entre los días y horas que horneas al mes, y suma ese valor por hora a cada pedido. En la guía ligera de la Fase 2 tienes la tablita lista.',
                'is_instructor_answer' => true,
            ]
        );

        // Intento de Evaluación Calificado, Feria y Certificado Digital con QR Verificable (CU5 y CU6)
        $firstEval = ModuleEvaluation::where('course_id', $mainCourse->id)->first();
        if ($firstEval) {
            EvaluationAttempt::firstOrCreate(
                ['evaluation_id' => $firstEval->id, 'user_id' => $participant1->id],
                [
                    'answers' => ['1' => 'A', '2' => 'V'],
                    'score' => 100,
                    'max_score' => 100,
                    'percentage' => 100,
                    'passed' => true,
                    'submitted_at' => now()->subDays(3),
                ]
            );
        }

        FairParticipation::updateOrCreate(
            ['course_id' => $mainCourse->id, 'user_id' => $participant1->id],
            [
                'enrollment_id' => $enr1->id,
                'project_title' => 'Línea de Panadería Integral sin Conservantes Doña Fer',
                'stand_code' => 'STAND-A04',
                'fair_date' => now()->subDays(2)->format('Y-m-d'),
                'fair_location' => 'Plaza Principal de Montero · Feria Productiva FEDER',
                'rubric_score' => 94.00,
                'participation_status' => 'destacado',
                'evaluator_feedback' => 'Excelente presentación de costos, degustación e imagen comercial.',
                'evaluated_by' => $instructor->id,
            ]
        );

        Certificate::updateOrCreate(
            ['course_id' => $mainCourse->id, 'user_id' => $participant1->id],
            [
                'verification_code' => 'FEDER-2026-MF001',
                'enrollment_id' => $enr1->id,
                'issued_by' => $director->id,
                'final_grade' => 96.00,
                'attendance_pct' => 100,
                'hours_completed' => 60,
                'status' => 'valid',
                'issued_at' => now()->subDay(),
            ]
        );

        // Sesiones reales de tiempo de uso en plataforma (activa Trigger trg_sync_user_active_seconds)
        UsageSession::query()->delete();
        UsageSession::create([
            'user_id' => $participant1->id,
            'course_id' => $mainCourse->id,
            'session_date' => now()->format('Y-m-d'),
            'seconds_spent' => 5400,
            'pings_count' => 90,
            'last_path' => '/mi-aula',
            'last_ping_at' => now(),
        ]);
        UsageSession::create([
            'user_id' => $participant2->id,
            'course_id' => $mainCourse->id,
            'session_date' => now()->format('Y-m-d'),
            'seconds_spent' => 3300,
            'pings_count' => 55,
            'last_path' => '/mi-aula',
            'last_ping_at' => now(),
        ]);
    }
}
