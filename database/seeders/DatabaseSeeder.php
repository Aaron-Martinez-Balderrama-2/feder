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
use App\Models\PaymentPlan;
use App\Models\PaymentTransaction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SocialOrganization;
use App\Models\UsageSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public const DEMO_MEET_URL = 'https://meet.google.com/kpb-ukps-qqj';
    public const DEMO_WHATSAPP_URL = 'https://chat.whatsapp.com/CVDu2PQ2Sc73vzCXGAKzKe';

    public function run(): void
    {
        // 0. Limpieza ordenada de datos académicos/demo previos para evitar cursos de prueba o registros vacíos
        Certificate::query()->delete();
        FairParticipation::query()->delete();
        EvaluationAttempt::query()->delete();
        EvaluationQuestion::query()->delete();
        ModuleEvaluation::query()->delete();
        CourseAttendance::query()->delete();
        ForumReply::query()->delete();
        ForumThread::query()->delete();
        CourseMaterial::query()->delete();
        CourseClass::query()->delete();
        CourseModule::query()->delete();
        CoursePrerequisite::query()->delete();
        PaymentTransaction::query()->delete();
        PaymentPlan::query()->delete();
        UsageSession::query()->delete();
        Enrollment::query()->delete();
        Course::query()->delete();
        EntrepreneurProfile::query()->delete();
        SocialOrganization::query()->delete();

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
                'description' => 'Microempresario o estudiante en formación, aula virtual, foros, vinculación y certificados.',
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

        // 5. Usuarios Oficiales (Los 4 del Login Rápido intactos + participantes adicionales para analítica realista)
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

        $instructor2 = User::updateOrCreate(
            ['email' => 'instructor2@feder.edu.bo'],
            [
                'name' => 'Ing. Valeria Justiniano',
                'password' => 'password',
                'role' => 'instructor',
                'role_id' => $roles['instructor']->id,
                'id_document' => '5893102',
                'phone' => '72644190',
                'birth_date' => '1988-06-15',
                'city' => 'Santa Cruz de la Sierra',
                'education_level' => 'Licenciatura',
                'gender' => 'Femenino',
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

        $participant3 = User::updateOrCreate(
            ['email' => 'lucia.vargas@feder.edu.bo'],
            [
                'name' => 'Lucía Vargas Morales',
                'password' => 'password',
                'role' => 'participant',
                'role_id' => $roles['participant']->id,
                'id_document' => '6482910',
                'phone' => '74920183',
                'birth_date' => '1997-03-14',
                'city' => 'Montero',
                'education_level' => 'Técnico Superior',
                'gender' => 'Femenino',
                'is_active' => true,
            ]
        );

        $participant4 = User::updateOrCreate(
            ['email' => 'diego.perez@feder.edu.bo'],
            [
                'name' => 'Diego Pérez Salvatierra',
                'password' => 'password',
                'role' => 'participant',
                'role_id' => $roles['participant']->id,
                'id_document' => '9103847',
                'phone' => '77319044',
                'birth_date' => '2000-08-22',
                'city' => 'Warnes',
                'education_level' => 'Bachiller',
                'gender' => 'Masculino',
                'is_active' => true,
            ]
        );

        $participant5 = User::updateOrCreate(
            ['email' => 'carmen.flores@feder.edu.bo'],
            [
                'name' => 'Carmen Rosa Flores Quispe',
                'password' => 'password',
                'role' => 'participant',
                'role_id' => $roles['participant']->id,
                'id_document' => '5390128',
                'phone' => '71580291',
                'birth_date' => '1971-02-09',
                'city' => 'Santa Cruz de la Sierra',
                'education_level' => 'Primaria Completa',
                'gender' => 'Femenino',
                'is_active' => true,
            ]
        );

        $participant6 = User::updateOrCreate(
            ['email' => 'paola.mendez@feder.edu.bo'],
            [
                'name' => 'Paola Andrea Méndez Roca',
                'password' => 'password',
                'role' => 'participant',
                'role_id' => $roles['participant']->id,
                'id_document' => '8842105',
                'phone' => '78044312',
                'birth_date' => '1992-10-30',
                'city' => 'Santa Cruz de la Sierra',
                'education_level' => 'Universitario',
                'gender' => 'Femenino',
                'is_active' => true,
            ]
        );

        User::where('role', 'student')->update([
            'role' => 'participant',
            'role_id' => $roles['participant']->id,
        ]);

        // 6. Organizaciones de Vinculación Social y Perfiles Productivos (CU2)
        $org1 = SocialOrganization::create([
            'name' => 'Asociación de Mujeres Emprendedoras del Norte Integrado',
            'organization_type' => 'asociacion',
            'municipality' => 'Montero',
            'department' => 'Santa Cruz',
            'contact_name' => 'Sra. Juana Salvatierra',
            'contact_phone' => '71620394',
            'agreement_status' => 'activo',
            'beneficiaries_target' => 80,
            'notes' => 'Convenio de formación productiva, liderazgo femenino y acceso a ferias municipales.',
        ]);

        $org2 = SocialOrganization::create([
            'name' => 'Cámara Artesanal y Gastronómica El Torno',
            'organization_type' => 'comunidad',
            'municipality' => 'El Torno',
            'department' => 'Santa Cruz',
            'contact_name' => 'Sr. Mario Flores',
            'contact_phone' => '73109482',
            'agreement_status' => 'activo',
            'beneficiaries_target' => 60,
            'notes' => 'Capacitación práctica en costos, formalización legal y ventas por WhatsApp.',
        ]);

        $org3 = SocialOrganization::create([
            'name' => 'Red de Textiles y Confección Plan 3000',
            'organization_type' => 'asociacion',
            'municipality' => 'Santa Cruz de la Sierra',
            'department' => 'Santa Cruz',
            'contact_name' => 'Lic. Roxana Chávez',
            'contact_phone' => '72194830',
            'agreement_status' => 'activo',
            'beneficiaries_target' => 90,
            'notes' => 'Acompañamiento a talleres de confección, tejidos y comercialización digital.',
        ]);

        $org4 = SocialOrganization::create([
            'name' => 'Cooperativa de Productores Agropecuarios Warnes',
            'organization_type' => 'municipio',
            'municipality' => 'Warnes',
            'department' => 'Santa Cruz',
            'contact_name' => 'Ing. Marco Antezana',
            'contact_phone' => '75320188',
            'agreement_status' => 'activo',
            'beneficiaries_target' => 50,
            'notes' => 'Alianza municipal para valor agregado agroindustrial y ventas directas.',
        ]);

        EntrepreneurProfile::create([
            'user_id' => $participant1->id,
            'social_organization_id' => $org1->id,
            'business_name' => 'Panadería y Repostería Artesanal Doña Fer',
            'sector' => 'Gastronomía y Alimentos',
            'business_stage' => 'en_marcha',
            'employees_count' => 3,
            'monthly_income_range' => '3.000 - 7.000 Bs.',
            'municipality' => 'Montero',
            'vulnerability_group' => 'Jefatura de hogar femenina',
            'social_impact_notes' => 'Proveedora local de desayunos saludables y repostería tradicional.',
            'verified_by' => $director->id,
            'verified_at' => now()->subDays(4),
        ]);

        EntrepreneurProfile::create([
            'user_id' => $participant2->id,
            'social_organization_id' => $org2->id,
            'business_name' => 'Carpintería Metálica y Muebles Choque',
            'sector' => 'Manufactura y Talleres',
            'business_stage' => 'consolidacion',
            'employees_count' => 4,
            'monthly_income_range' => '7.000 - 15.000 Bs.',
            'municipality' => 'El Torno',
            'vulnerability_group' => 'Emprendimiento rural productivo',
            'social_impact_notes' => 'Genera empleo técnico joven en su municipio.',
            'verified_by' => $director->id,
            'verified_at' => now()->subDays(2),
        ]);

        EntrepreneurProfile::create([
            'user_id' => $participant3->id,
            'social_organization_id' => $org1->id,
            'business_name' => 'Taller de Costura y Bordados El Norte',
            'sector' => 'Textil y Confección',
            'business_stage' => 'en_marcha',
            'employees_count' => 2,
            'monthly_income_range' => '3.000 - 7.000 Bs.',
            'municipality' => 'Montero',
            'vulnerability_group' => 'Mujeres emprendedoras',
            'social_impact_notes' => 'Confección de uniformes escolares y mandiles bordados.',
            'verified_by' => $director->id,
            'verified_at' => now()->subDays(3),
        ]);

        EntrepreneurProfile::create([
            'user_id' => $participant4->id,
            'social_organization_id' => $org4->id,
            'business_name' => 'Vivero y Producción Orgánica Santa Rita',
            'sector' => 'Agroindustria y Producción',
            'business_stage' => 'inicial',
            'employees_count' => 2,
            'monthly_income_range' => '1.500 - 4.000 Bs.',
            'municipality' => 'Warnes',
            'vulnerability_group' => 'Emprendedor joven rural',
            'social_impact_notes' => 'Plantines frutales y abono orgánico para productores locales.',
            'verified_by' => $director->id,
            'verified_at' => now()->subDay(),
        ]);

        EntrepreneurProfile::create([
            'user_id' => $participant5->id,
            'social_organization_id' => $org3->id,
            'business_name' => 'Tejidos Tradicionales Doña Carmen',
            'sector' => 'Textil y Confección',
            'business_stage' => 'en_marcha',
            'employees_count' => 2,
            'monthly_income_range' => '1.500 - 4.000 Bs.',
            'municipality' => 'Santa Cruz de la Sierra',
            'vulnerability_group' => 'Adulto Mayor productivo',
            'social_impact_notes' => 'Preserva técnicas artesanales y capacita a vecinas del distrito.',
            'verified_by' => $director->id,
            'verified_at' => now()->subDays(5),
        ]);

        // Perfil pendiente de validar (ideal para demostrar el botón "Validar" de CU2 en vivo)
        EntrepreneurProfile::create([
            'user_id' => $participant6->id,
            'social_organization_id' => $org3->id,
            'business_name' => 'Cerámica y Regalos Artesanales Méndez',
            'sector' => 'Comercio y Ventas',
            'business_stage' => 'inicial',
            'employees_count' => 1,
            'monthly_income_range' => '1.500 - 4.000 Bs.',
            'municipality' => 'Santa Cruz de la Sierra',
            'vulnerability_group' => 'Emprendimiento femenino urbano',
            'social_impact_notes' => 'Piezas utilitarias de cerámica con ventas por catálogo digital.',
            'verified_by' => null,
            'verified_at' => null,
        ]);

        // 7. Periodo Académico y Áreas Formativas (CU3)
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
            ['name' => 'Gestión de Microempresas', 'slug' => 'gestion-microempresas', 'icon' => 'briefcase', 'description' => 'Bases prácticas para ordenar, costear y hacer crecer tu negocio.'],
            ['name' => 'Finanzas Empresariales', 'slug' => 'finanzas-corporativas', 'icon' => 'coin', 'description' => 'Control de caja, precios reales, punto de equilibrio y ahorro.'],
            ['name' => 'Marketing Digital', 'slug' => 'marketing-digital', 'icon' => 'target', 'description' => 'Ventas por redes sociales, fotografía móvil y WhatsApp Business.'],
            ['name' => 'Formalización y Legal', 'slug' => 'liderazgo-y-rrhh', 'icon' => 'shield', 'description' => 'Trámites SEPREC, NIT en Impuestos Nacionales, licencias y contratos.'],
            ['name' => 'Herramientas Digitales', 'slug' => 'ciencia-de-datos', 'icon' => 'brain', 'description' => 'Tiendas virtuales, catálogos QR y control digital de inventario.'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::updateOrCreate(['slug' => $c['slug']], array_merge($c, ['is_active' => true]));
        }

        // Helper para tamaño real de PDFs locales ultraligeros (<110 KB)
        $pdfSize = function (string $relPath): int {
            $full = public_path($relPath);
            return file_exists($full) ? (int) filesize($full) : 96 * 1024;
        };

        // 8. Oferta Curricular Realista en PLURAL (6 Cursos Gratuitos completos con Meet, WhatsApp, Fases, Clases, PDFs y Videos)
        $coursesSpec = [
            [
                'slug' => 'escuela-inicial-para-microempresarios',
                'category_slug' => 'gestion-microempresas',
                'instructor_id' => $instructor->id,
                'title' => 'Escuelas Iniciales para Microempresarios',
                'hook' => 'Ordena tus cuentas, define tu propuesta de valor y prepara tu emprendimiento para crecer.',
                'description' => 'Programa integral gratuito para microempresarios, talleres artesanales y comercios locales de Santa Cruz y municipios aliados.',
                'outcome' => 'Contar con tu Lienzo Canvas validado, control de caja semanal, catálogo WhatsApp y hoja de ruta legal.',
                'level' => 'inicial',
                'capacity' => 35,
                'modality' => 'hibrida',
                'classroom_location' => 'Aula Planta Baja · Sede FEDER Módulo 236',
                'hours_duration' => 60,
                'requires_fair_participation' => true,
                'cover_image' => 'assets/img/covers/incubadora-negocios.jpg',
                'color' => '#F7780F',
                'start_date' => now()->subDays(14)->format('Y-m-d'),
                'end_date' => now()->addDays(40)->format('Y-m-d'),
                'phases' => [
                    [
                        'title' => 'Modelo de negocio',
                        'desc' => 'Diseño de propuesta de valor, segmento de clientes y Lienzo Canvas.',
                        'completed' => true,
                        'classes' => [
                            [
                                'title' => '1.1 Identificación de clientes y propuesta de valor',
                                'desc' => 'Cómo descubrir qué valora tu comprador en tu barrio o municipio.',
                                'summary' => 'Anota en tu cuaderno: 1) Quién te compra hoy, 2) Qué problema le resuelves mejor que la competencia, 3) Por qué vuelve a comprarte.',
                                'completed' => true,
                            ],
                            [
                                'title' => '1.2 Armado práctico del Lienzo Canvas en 1 página',
                                'desc' => 'Taller guiado con los 9 bloques aplicados a panaderías, talleres y tiendas.',
                                'summary' => 'Divide una hoja en 9 cuadros: Clientes, Propuesta, Canales, Relación, Ingresos, Recursos, Actividades, Socios y Costos.',
                                'completed' => true,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Guía Práctica FEDER: Lienzo Modelo Canvas (PDF Ligero)',
                                'resource_type' => 'guia_ligera',
                                'file_path' => 'uploads/materials/guia_modelo_canvas_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/guia_modelo_canvas_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Guía ilustrada de 1 página con los 9 bloques del Modelo Canvas y ejercicio práctico de 10 minutos.',
                            ],
                            [
                                'title' => 'Video Clase: Tutorial Business Model Canvas en Español',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://www.youtube.com/watch?v=YNHdgq5DGOE',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Clase visual de Emprender Simple explicando cada bloque del Canvas con ejemplos cotidianos.',
                            ],
                        ],
                        'eval' => [
                            'title' => 'Autoevaluación Fase 1: Modelo de Negocio',
                            'instructions' => 'Responde estas 2 preguntas breves para validar tu dominio del Lienzo Canvas.',
                            'questions' => [
                                [
                                    'text' => '¿Qué define mejor la Propuesta de Valor en el Lienzo Canvas?',
                                    'type' => 'multiple_choice',
                                    'options' => [
                                        ['key' => 'A', 'text' => 'La razón concreta por la que el cliente elige tu producto o servicio sobre otras opciones.'],
                                        ['key' => 'B', 'text' => 'La lista de multas e impuestos municipales del mes.'],
                                        ['key' => 'C', 'text' => 'El número de teléfono de tus proveedores.'],
                                    ],
                                    'correct' => 'A',
                                    'points' => 50,
                                    'explanation' => 'La propuesta de valor resume el beneficio real que entregas a tu segmento de clientes.',
                                ],
                                [
                                    'text' => 'Es recomendable probar tu producto con clientes reales cercanos antes de gastar mucho capital.',
                                    'type' => 'true_false',
                                    'options' => [['key' => 'V', 'text' => 'Verdadero'], ['key' => 'F', 'text' => 'Falso']],
                                    'correct' => 'V',
                                    'points' => 50,
                                    'explanation' => 'Validar temprano reduce riesgos y evita invertir en productos sin demanda.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Finanzas para la empresa',
                        'desc' => 'Separación de cuentas, costos unitarios, precios y flujo de caja.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '2.1 Separación de caja del negocio y finanzas del hogar',
                                'desc' => 'Cómo asignarte un sueldo fijo sin descapitalizar tu emprendimiento.',
                                'summary' => 'Regla clave: todo dinero que entra va a la caja del negocio; tú recibes un sueldo fijo semanal anotado como gasto.',
                                'completed' => false,
                            ],
                            [
                                'title' => '2.2 Flujo de caja semanal y cálculo de punto de equilibrio',
                                'desc' => 'Control diario de entradas, salidas y meta mínima mensual de ventas.',
                                'summary' => 'Fórmula: Punto de Equilibrio = Costos Fijos / (Precio Unitario - Costo Variable Unitario).',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Hoja Práctica FEDER: Flujo de Caja y Punto de Equilibrio (PDF)',
                                'resource_type' => 'plantilla',
                                'file_path' => 'uploads/materials/plantilla_flujo_caja_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/plantilla_flujo_caja_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Ejemplo resuelto en Bolivianos (Bs.) con tabla diaria de ingresos, egresos y fórmula de equilibrio.',
                            ],
                            [
                                'title' => 'Video Clase: Finanzas Básicas para Emprendedores',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://www.youtube.com/watch?v=kR-rpleu0hM',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Explicación práctica sobre control de costos, márgenes y liquidez semanal.',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Marketing Digital',
                        'desc' => 'Catálogo en WhatsApp Business, fotografía de productos y atención comercial.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '3.1 Perfil comercial y catálogo en WhatsApp Business',
                                'desc' => 'Configuración de horarios, dirección, etiquetas y respuestas rápidas.',
                                'summary' => 'Activa tu catálogo con fotos claras, descripción de 2 líneas y precio en Bs. para responder en menos de 1 minuto.',
                                'completed' => false,
                            ],
                            [
                                'title' => '3.2 Fotografía de productos desde el celular con luz natural',
                                'desc' => 'Cómo mostrar tus productos artesanales sin contratar fotógrafo.',
                                'summary' => 'Usa luz de ventana lateral, fondo limpio liso y toma siempre 1 foto general + 1 foto de detalle.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Guía Rápida FEDER: Ventas por WhatsApp Business (PDF)',
                                'resource_type' => 'guia_ligera',
                                'file_path' => 'uploads/materials/guia_whatsapp_catalogo_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/guia_whatsapp_catalogo_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => '4 pasos para configurar WhatsApp Business y guion de cierre de ventas listo para copiar.',
                            ],
                            [
                                'title' => 'Video Clase: Cómo Utilizar WhatsApp Business Paso a Paso',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://www.youtube.com/watch?v=RBu7Sa5AsBM',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Tutorial de HubSpot Español sobre catálogo, mensajes de bienvenida y etiquetas.',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Legal',
                        'desc' => 'Formalización progresiva: SEPREC, NIT, Licencia Municipal y preparación de Feria.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '4.1 Ruta de formalización: SEPREC, NIT y Licencia Municipal',
                                'desc' => 'Requisitos claros para pasar de la informalidad a emitir factura o respaldo formal.',
                                'summary' => 'Pasos en Bolivia: 1) Matrícula unipersonal SEPREC, 2) Padrón NIT en Impuestos Nacionales, 3) Licencia en la Alcaldía.',
                                'completed' => false,
                            ],
                            [
                                'title' => '4.2 Preparación de Stand y Pitch para la Feria Productiva',
                                'desc' => 'Presentación final de productos, costos visibles y cobro por QR ante el jurado.',
                                'summary' => 'Prepara tu pitch de 2 minutos: quién eres, qué produces, cuánto cuesta hacerlo, a cuánto lo vendes y cuántos empleos generas.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Checklist Formalización Unipersonal en Bolivia (PDF FEDER)',
                                'resource_type' => 'documento',
                                'file_path' => 'uploads/materials/checklist_formalizacion_bolivia_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/checklist_formalizacion_bolivia_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Lista de requisitos vigentes para SEPREC, NIT (SIN) y Licencia Municipal.',
                            ],
                            [
                                'title' => 'Cuaderno Oficial OIT: Plan de Negocios ISUN (PDF Externo)',
                                'resource_type' => 'documento',
                                'external_url' => 'https://www.oitcinterfor.org/sites/default/files/isunplan.pdf',
                                'file_size_bytes' => 814511,
                                'bandwidth_tier' => 'estandar',
                                'offline_summary' => 'Manual completo abierto de la Organización Internacional del Trabajo (OIT) para MIPYMES.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'finanzas-para-no-financieros',
                'category_slug' => 'finanzas-corporativas',
                'instructor_id' => $instructor->id,
                'title' => 'Finanzas Prácticas para Pequeños Negocios',
                'hook' => 'Domina tus costos reales, fija precios rentables y proyecta tu flujo de caja semanal.',
                'description' => 'Curso presencial/apoyado en aula virtual ideal para dueños de negocios que desean ordenar sus ingresos, costos y ahorro.',
                'outcome' => 'Controlar tus márgenes por producto y mantener flujo de caja positivo todo el mes.',
                'level' => 'inicial',
                'capacity' => 30,
                'modality' => 'presencial',
                'classroom_location' => 'Sala Taller 2 · Sede FEDER',
                'hours_duration' => 48,
                'requires_fair_participation' => false,
                'cover_image' => 'assets/img/covers/finanzas-negocio.jpg',
                'color' => '#13AC44',
                'start_date' => now()->subDays(8)->format('Y-m-d'),
                'end_date' => now()->addDays(32)->format('Y-m-d'),
                'phases' => [
                    [
                        'title' => 'Modelo de negocio',
                        'desc' => 'Mapa de productos más rentables y estructura base de ingresos.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '1.1 Inventario de productos estrella y márgenes iniciales',
                                'desc' => 'Identifica qué productos dejan ganancia real y cuáles solo rotan sin utilidad.',
                                'summary' => 'Lista tus 5 productos más vendidos y anota cuánto cuesta producir cada uno vs. su precio actual.',
                                'completed' => true,
                            ],
                            [
                                'title' => '1.2 Separación patrimonial y presupuesto mensual de taller',
                                'desc' => 'Cómo evitar mezclar compras familiares con capital de trabajo.',
                                'summary' => 'Usa dos cuadernos o dos billeteras separadas: una exclusiva para reposición de mercadería e insumos.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Manual Corto FEDER: Cómo Fijar Precios Sin Perder Dinero (PDF)',
                                'resource_type' => 'guia_ligera',
                                'file_path' => 'uploads/materials/guia_costos_precios_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/guia_costos_precios_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Diferencia práctica entre costo fijo, costo variable y margen de contribución.',
                            ],
                            [
                                'title' => 'Video Clase: Cómo Elaborar tu Plan de Negocios y Costos',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://www.youtube.com/watch?v=6v8BHThn8lM',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Sesión guiada de emprendimiento y proyección inicial de ingresos.',
                            ],
                        ],
                        'eval' => [
                            'title' => 'Autoevaluación Fase 1: Diagnóstico de Costos',
                            'instructions' => 'Aprueba esta autoevaluación para habilitar la Fase 2 (cuando el instructor complete la Fase 1).',
                            'questions' => [
                                [
                                    'text' => '¿Cuál es el error más frecuente al fijar precios en un microemprendimiento?',
                                    'type' => 'multiple_choice',
                                    'options' => [
                                        ['key' => 'A', 'text' => 'Multiplicar solo los ingredientes sin contar gas, transporte, mermas ni horas trabajadas.'],
                                        ['key' => 'B', 'text' => 'Calcular el costo unitario exacto de cada insumo.'],
                                        ['key' => 'C', 'text' => 'Separar un sueldo fijo para el propietario.'],
                                    ],
                                    'correct' => 'A',
                                    'points' => 50,
                                    'explanation' => 'Omitir costos indirectos y mano de obra hace creer que hay ganancia cuando en realidad se pierde capital.',
                                ],
                                [
                                    'text' => 'El alquiler del taller y el internet mensual son Costos Fijos porque se pagan aunque vendas poco.',
                                    'type' => 'true_false',
                                    'options' => [['key' => 'V', 'text' => 'Verdadero'], ['key' => 'F', 'text' => 'Falso']],
                                    'correct' => 'V',
                                    'points' => 50,
                                    'explanation' => 'Los costos fijos no dependen directamente de cuántas unidades produzcas en el mes.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Finanzas para la empresa',
                        'desc' => 'Punto de equilibrio, control diario de caja y manejo sano de créditos.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '2.1 Cálculo del Punto de Equilibrio paso a paso',
                                'desc' => 'Cuántas unidades necesitas vender al mes para cubrir todos tus gastos.',
                                'summary' => 'Divide los costos fijos mensuales entre la ganancia unitaria de tu producto principal.',
                                'completed' => false,
                            ],
                            [
                                'title' => '2.2 Flujo de caja semanal y fondo de reserva',
                                'desc' => 'Control de cobros al contado, QR y créditos a clientes sin quedarte sin liquidez.',
                                'summary' => 'Anota cada entrada y salida el mismo día y aparta el 10% de la utilidad neta para emergencias o renovación de equipo.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Plantilla FEDER: Flujo de Caja Semanal (PDF Ligero)',
                                'resource_type' => 'plantilla',
                                'file_path' => 'uploads/materials/plantilla_flujo_caja_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/plantilla_flujo_caja_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Cuadro listo para transcribir a tu cuaderno o hoja de cálculo.',
                            ],
                            [
                                'title' => 'Video Clase: Cálculo de Punto de Equilibrio (HubSpot)',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://www.youtube.com/watch?v=SecIZn6RegQ',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Fórmula y ejemplos paso a paso para saber desde qué venta empiezas a ganar.',
                            ],
                            [
                                'title' => 'Video Clase: Flujo de Caja en Excel para Emprendedores',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://www.youtube.com/watch?v=ARvtLzR1e80',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Guía visual para proyectar entradas y salidas mensuales.',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Marketing Digital',
                        'desc' => 'Promociones rentables sin regalar tu margen de ganancia.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '3.1 Armado de combos rentables y descuentos con margen seguro',
                                'desc' => 'Cómo hacer ofertas atractivas en fechas clave sin perder utilidad.',
                                'summary' => 'Nunca bajes precios por instinto: combina un producto de alto margen con otro de alta rotación.',
                                'completed' => false,
                            ],
                            [
                                'title' => '3.2 Seguimiento de ventas y pedidos por WhatsApp',
                                'desc' => 'Uso de etiquetas de pagado, por cobrar y cliente frecuente.',
                                'summary' => 'Clasifica cada chat con etiquetas para no olvidar entregas ni cobros pendientes.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [],
                    ],
                    [
                        'title' => 'Legal',
                        'desc' => 'Respaldo de comprobantes, obligaciones tributarias y recibos simples.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '4.1 Régimen Tributario Boliviano y emisión de respaldos',
                                'desc' => 'Diferencias prácticas entre Régimen Simplificado y Régimen General (NIT).',
                                'summary' => 'Verifica tu actividad y capital para inscribirte correctamente sin multas.',
                                'completed' => false,
                            ],
                            [
                                'title' => '4.2 Archivo ordenado de facturas de compra y ventas diarias',
                                'desc' => 'Cómo preparar tus respaldos para acceder a microcréditos productivos.',
                                'summary' => 'Guarda tus recibos y extractos en una carpeta mensual para demostrar ingresos ante entidades financieras.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [],
                    ],
                ],
            ],
            [
                'slug' => 'estrategias-de-ventas-digitales',
                'category_slug' => 'marketing-digital',
                'instructor_id' => $instructor->id,
                'title' => 'Marketing Digital y Ventas por WhatsApp Business',
                'hook' => 'Fotografía tus productos, configura tu catálogo digital y multiplica tus ventas locales.',
                'description' => 'Capacitación 100% práctica desde tu teléfono móvil para atraer clientes en redes sociales y cerrar pedidos por WhatsApp.',
                'outcome' => 'Tener funcionando tu catálogo profesional en WhatsApp Business y campaña orgánica semanal.',
                'level' => 'intermedio',
                'capacity' => 30,
                'modality' => 'virtual',
                'classroom_location' => 'Aula Virtual FEDER · Enlace Meet',
                'hours_duration' => 50,
                'requires_fair_participation' => true,
                'cover_image' => 'assets/img/covers/marketing-whatsapp.jpg',
                'color' => '#334155',
                'start_date' => now()->subDays(5)->format('Y-m-d'),
                'end_date' => now()->addDays(42)->format('Y-m-d'),
                'phases' => [
                    [
                        'title' => 'Modelo de negocio',
                        'desc' => 'Definición del cliente comprador digital y oferta ganadora.',
                        'completed' => true,
                        'classes' => [
                            [
                                'title' => '1.1 Perfil del comprador digital en tu zona',
                                'desc' => 'A qué hora compra tu público y qué dudas pregunta antes de pagar.',
                                'summary' => 'Anota las 5 preguntas que más se repiten en tus chats para responderlas directo en la descripción de tu producto.',
                                'completed' => true,
                            ],
                            [
                                'title' => '1.2 Diseño de ofertas y combos claros con precio visible',
                                'desc' => 'Por qué ocultar el precio hace perder ventas en redes sociales.',
                                'summary' => 'Publica siempre foto real, precio en Bs., formas de entrega y botón directo a tu WhatsApp.',
                                'completed' => true,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Guía Rápida FEDER: Catálogo y Cierre por WhatsApp (PDF)',
                                'resource_type' => 'guia_ligera',
                                'file_path' => 'uploads/materials/guia_whatsapp_catalogo_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/guia_whatsapp_catalogo_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Checklist visual de perfil comercial, fotografía natural y mensajes de respuesta rápida.',
                            ],
                            [
                                'title' => 'Video Clase: Cómo Usar WhatsApp Business para Vender',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://www.youtube.com/watch?v=XNdfscOaNuU',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Guía completa de HubSpot Español para convertir chats en pedidos reales.',
                            ],
                        ],
                        'eval' => [
                            'title' => 'Autoevaluación Fase 1: Estrategia de Oferta Digital',
                            'instructions' => 'Comprueba tu criterio comercial digital.',
                            'questions' => [
                                [
                                    'text' => '¿Qué elemento genera mayor confianza inmediata al escribir a un negocio en WhatsApp Business?',
                                    'type' => 'multiple_choice',
                                    'options' => [
                                        ['key' => 'A', 'text' => 'Perfil con foto clara del negocio, horario, ubicación y catálogo con precios en Bs.'],
                                        ['key' => 'B', 'text' => 'No poner foto ni precios para obligar a preguntar todo.'],
                                        ['key' => 'C', 'text' => 'Responder recién después de 3 días.'],
                                    ],
                                    'correct' => 'A',
                                    'points' => 50,
                                    'explanation' => 'La información clara desde el primer segundo reduce dudas y acelera el pedido.',
                                ],
                                [
                                    'text' => 'Los mensajes de respuesta rápida en WhatsApp Business ahorran tiempo y mejoran la atención al cliente.',
                                    'type' => 'true_false',
                                    'options' => [['key' => 'V', 'text' => 'Verdadero'], ['key' => 'F', 'text' => 'Falso']],
                                    'correct' => 'V',
                                    'points' => 50,
                                    'explanation' => 'Tener atajos para horarios, ubicación y QR evita demoras cuando llegan varios chats.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Finanzas para la empresa',
                        'desc' => 'Cálculo de costo de envío, comisiones QR y presupuesto publicitario.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '2.1 Costeo de envíos, delivery y empaques',
                                'desc' => 'Cómo cobrar o incluir entregas a domicilio sin perder ganancia.',
                                'summary' => 'Calcula empaque + bolsa + envío por zona antes de ofrecer "envío gratis".',
                                'completed' => false,
                            ],
                            [
                                'title' => '2.2 Presupuesto mínimo para promociones locales',
                                'desc' => 'Cuánto invertir según tus ventas meta de la semana.',
                                'summary' => 'Define un monto semanal fijo solo cuando tu catálogo y stock ya estén listos.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Video Clase: Cómo Vender Más con WhatsApp Business',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://www.youtube.com/watch?v=Gsm1sRTPPI4',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Consejos prácticos de seguimiento de clientes y cierre.',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Marketing Digital',
                        'desc' => 'Contenido orgánico diario, estados de WhatsApp y enlaces directos.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '3.1 Calendario semanal de publicaciones en Facebook y Estados',
                                'desc' => 'Qué publicar lunes, miércoles y viernes sin complicarte.',
                                'summary' => 'Alterna: 1) Proceso de producción en tu taller, 2) Testimonio de cliente, 3) Combo especial de fin de semana.',
                                'completed' => false,
                            ],
                            [
                                'title' => '3.2 Enlaces cortos wa.me y códigos QR para volantes y feria',
                                'desc' => 'Cómo llevar compradores físicos directo a tu chat de ventas.',
                                'summary' => 'Genera tu enlace wa.me/591XXXXXXXX e imprímelo en tus etiquetas o cartel de stand.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [],
                    ],
                    [
                        'title' => 'Legal',
                        'desc' => 'Políticas claras de cambios, devoluciones y derechos del consumidor.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '4.1 Acuerdos claros de reserva, seña y entrega en ventas digitales',
                                'desc' => 'Cómo prevenir malentendidos con compradores por internet.',
                                'summary' => 'Detalla por escrito en el chat: producto exacto, fecha de entrega, costo de envío y saldo pendiente.',
                                'completed' => false,
                            ],
                            [
                                'title' => '4.2 Presentación de catálogo digital en Feria FEDER',
                                'desc' => 'Integración de ventas físicas y catálogo QR para captar clientes.',
                                'summary' => 'Prepara tarjetas con QR hacia tu catálogo WhatsApp para que quienes visitan tu stand te vuelvan a comprar.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Manual FEDER: Preparación para Feria Productiva (PDF)',
                                'resource_type' => 'documento',
                                'file_path' => 'uploads/materials/guia_feria_empresarial_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/guia_feria_empresarial_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Criterios de evaluación de stand, precios visibles y pitch de 2 minutos.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'formalizacion-legal-de-emprendimientos',
                'category_slug' => 'liderazgo-y-rrhh',
                'instructor_id' => $instructor2->id,
                'title' => 'Formalización Legal y Contable de Emprendimientos',
                'hook' => 'Da el salto hacia la formalidad en Bolivia: SEPREC, NIT, Licencia Municipal y contratos simples.',
                'description' => 'Programa práctico de nivel intermedio para microempresarios egresados de Escuelas Iniciales que desean formalizar su empresa unipersonal o sociedad.',
                'outcome' => 'Tener armada tu carpeta completa para obtener Matrícula SEPREC, NIT y Licencia de Funcionamiento.',
                'level' => 'intermedio',
                'capacity' => 25,
                'modality' => 'hibrida',
                'classroom_location' => 'Sala Jurídica · Sede FEDER Módulo 236',
                'hours_duration' => 40,
                'requires_fair_participation' => false,
                'cover_image' => 'assets/img/covers/formalizacion-legal.jpg',
                'color' => '#EA6A0A',
                'start_date' => now()->subDays(2)->format('Y-m-d'),
                'end_date' => now()->addDays(45)->format('Y-m-d'),
                'prerequisite_slug' => 'escuela-inicial-para-microempresarios',
                'phases' => [
                    [
                        'title' => 'Modelo de negocio',
                        'desc' => 'Elección entre Empresa Unipersonal o S.R.L. según tu actividad.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '1.1 Empresa Unipersonal vs. Sociedad de Responsabilidad Limitada',
                                'desc' => 'Ventajas, costos iniciales y responsabilidades legales de cada figura.',
                                'summary' => 'Si emprendes solo o en familia directa, la Empresa Unipersonal es el camino más ágil y económico en Bolivia.',
                                'completed' => false,
                            ],
                            [
                                'title' => '1.2 Verificación de nombre comercial y registro en SEPREC',
                                'desc' => 'Pasos en plataforma virtual para reserva de nombre y matrícula de comercio.',
                                'summary' => 'Prepara 3 opciones de nombre antes de consultar homonimia y ten tu Ciudadanía Digital activa.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Checklist Formalización Unipersonal en Bolivia (PDF FEDER)',
                                'resource_type' => 'guia_ligera',
                                'file_path' => 'uploads/materials/checklist_formalizacion_bolivia_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/checklist_formalizacion_bolivia_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Resumen paso a paso de SEPREC, NIT, Licencia Municipal y ROE.',
                            ],
                            [
                                'title' => 'Guía Externa Bolivia Emprende: Pasos para Crear Empresa',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://boliviaemprende.com/guias/como-crear-una-empresa-en-bolivia-conozca-los-pasos',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Artículo actualizado con los pasos de constitución y registro en Bolivia.',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Finanzas para la empresa',
                        'desc' => 'Balance de apertura, regímenes tributarios y facturación SIAT.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '2.1 Regímenes tributarios en el Servicio de Impuestos Nacionales',
                                'desc' => 'Cuándo corresponde Régimen Simplificado y cuándo Régimen General (NIT).',
                                'summary' => 'Revisa tu capital invertido, precio unitario máximo y si tus clientes empresariales te piden factura.',
                                'completed' => false,
                            ],
                            [
                                'title' => '2.2 Orden documental de compras, gastos y libros básicos',
                                'desc' => 'Registro ordenado para evitar multas y facilitar declaraciones mensuales.',
                                'summary' => 'Conserva facturas con NIT a nombre de tu negocio relacionadas con tu rubro.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Portal Oficial SIN: Requisitos y Procedimiento para NIT',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://siatinfo.impuestos.gob.bo/index.php/requisitos-para-la-inscripcion/procedimientos-y-requisitos-para-obtener-el-nit-para-personas-juridicas',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Requisitos oficiales publicados por el Servicio de Impuestos Nacionales.',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Marketing Digital',
                        'desc' => 'Sello de confianza formal para vender a empresas e instituciones.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '3.1 Cómo presentar cotizaciones formales a empresas y municipios',
                                'desc' => 'Uso de tu NIT y proforma comercial para acceder a contratos mayores.',
                                'summary' => 'Incluye razón social, NIT, vigencia de oferta, plazo de entrega y cuenta bancaria del negocio.',
                                'completed' => false,
                            ],
                            [
                                'title' => '3.2 Etiquetado básico e identidad legal de tu marca',
                                'desc' => 'Datos mínimos que debe mostrar tu empaque y catálogo.',
                                'summary' => 'Nombre comercial, dirección/municipio, WhatsApp de contacto y fecha o lote si elaboras alimentos.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [],
                    ],
                    [
                        'title' => 'Legal',
                        'desc' => 'Licencia municipal, seguridad básica y contratación laboral ROE.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '4.1 Licencia de Funcionamiento Municipal e inspecciones',
                                'desc' => 'Requisitos de croquis, aviso de cobranza de luz y medidas de seguridad.',
                                'summary' => 'Ten extintor vigente, botiquín y croquis claro del local antes de iniciar el trámite en Alcaldía.',
                                'completed' => false,
                            ],
                            [
                                'title' => '4.2 Contratos simples con ayudantes y registro ROE',
                                'desc' => 'Obligaciones básicas cuando tu taller contrata personal fijo.',
                                'summary' => 'Formaliza horarios, remuneración y afiliación legal desde el primer trabajador dependiente.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [],
                    ],
                ],
            ],
            [
                'slug' => 'tiendas-virtuales-y-comercio-electronico',
                'category_slug' => 'ciencia-de-datos',
                'instructor_id' => $instructor->id,
                'title' => 'Tiendas Virtuales y Comercio Electrónico Local',
                'hook' => 'Escala tus ventas digitales integrando catálogos web, pagos por QR Simple y control de pedidos.',
                'description' => 'Curso avanzado práctico para negocios que ya dominan WhatsApp Business y desean profesionalizar su vitrina digital y despachos.',
                'outcome' => 'Operar tu tienda/catálogo web conectado a cobros QR y hoja digital de inventario.',
                'level' => 'avanzado',
                'capacity' => 25,
                'modality' => 'virtual',
                'classroom_location' => 'Aula Virtual FEDER · Enlace Meet',
                'hours_duration' => 56,
                'requires_fair_participation' => true,
                'cover_image' => 'assets/img/covers/ventas-digitales.jpg',
                'color' => '#0F172A',
                'start_date' => now()->subDays(1)->format('Y-m-d'),
                'end_date' => now()->addDays(48)->format('Y-m-d'),
                'prerequisite_slug' => 'estrategias-de-ventas-digitales',
                'phases' => [
                    [
                        'title' => 'Modelo de negocio',
                        'desc' => 'Arquitectura de ventas omnicanal para comercios bolivianos.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '1.1 Del catálogo en chat a una tienda web autoadministrable',
                                'desc' => 'Cuándo conviene dar el salto y cómo mantener precios sincronizados.',
                                'summary' => 'Organiza tus productos por categorías claras y stock real para evitar vender piezas agotadas.',
                                'completed' => false,
                            ],
                            [
                                'title' => '1.2 Experiencia de pedido en menos de 3 clics',
                                'desc' => 'Simplificación de carrito y confirmación automática por WhatsApp.',
                                'summary' => 'Menos campos en el formulario = más compras terminadas desde el celular.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Video Clase: Business Model Canvas Aplicado a Negocios Digitales',
                                'resource_type' => 'enlace',
                                'external_url' => 'https://www.youtube.com/watch?v=YoQLmCWA0Mk',
                                'file_size_bytes' => 0,
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Diseño estratégico para comercios físicos que abren canal online.',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Finanzas para la empresa',
                        'desc' => 'Conciliación de cobros QR Simple y control de stock.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '2.1 Conciliación diaria de transferencias QR y entregas',
                                'desc' => 'Cómo verificar comprobantes reales antes de despachar mercadería.',
                                'summary' => 'Confirma el abono directo en tu aplicación bancaria, nunca solo con captura reenviada.',
                                'completed' => false,
                            ],
                            [
                                'title' => '2.2 Control de inventario mínimo y alertas de reposición',
                                'desc' => 'Evita quiebres de stock en productos de alta rotación.',
                                'summary' => 'Define un punto mínimo de alerta para reponer insumos antes de quedarte en cero.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [],
                    ],
                    [
                        'title' => 'Marketing Digital',
                        'desc' => 'Embudo de anuncios Click-to-WhatsApp y fidelización.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '3.1 Segmentación geográfica por barrio y municipio',
                                'desc' => 'Cómo mostrar anuncios solo en zonas donde puedes entregar rápido.',
                                'summary' => 'Delimita un radio de 5 a 10 km alrededor de tu taller para optimizar cada boliviano invertido.',
                                'completed' => false,
                            ],
                            [
                                'title' => '3.2 Posventa y recompra de clientes frecuentes',
                                'desc' => 'Mensajes de seguimiento y beneficios para compradores recurrentes.',
                                'summary' => 'Escribir 7 días después para preguntar cómo llegó el producto duplica la recomendación boca a boca.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [],
                    ],
                    [
                        'title' => 'Legal',
                        'desc' => 'Términos de entrega, comprobantes y garantías en comercio electrónico.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '4.1 Condiciones claras de envío, cambios y garantía escrita',
                                'desc' => 'Respaldo profesional para construir reputación digital duradera.',
                                'summary' => 'Publica plazos de entrega por zona y política transparente de cambios por falla.',
                                'completed' => false,
                            ],
                            [
                                'title' => '4.2 Exhibición comercial en Feria Digital y Presencial FEDER',
                                'desc' => 'Integración de QR de catálogo en stands presenciales.',
                                'summary' => 'Lleva impresos tus códigos QR para captar pedidos posteriores al cierre de feria.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [],
                    ],
                ],
            ],
            [
                'slug' => 'talleres-productivos-costos-y-ferias',
                'category_slug' => 'gestion-microempresas',
                'instructor_id' => $instructor2->id,
                'title' => 'Talleres Productivos de Costos y Ferias Comerciales',
                'hook' => 'Prepara tus productos estrella, calcula su rentabilidad exacta y destaca en ferias municipales.',
                'description' => 'Taller intensivo gratuito enfocado en artesanos, panaderías, textiles y productores rurales que participan en ferias locales.',
                'outcome' => 'Presentar tu emprendimiento en feria con costos validados, imagen atractiva y ventas rentables.',
                'level' => 'inicial',
                'capacity' => 20,
                'modality' => 'presencial',
                'classroom_location' => 'Patio Productivo · Sede FEDER',
                'hours_duration' => 36,
                'requires_fair_participation' => true,
                'cover_image' => 'assets/img/covers/incubadora-negocios.jpg',
                'color' => '#12963D',
                'start_date' => now()->subDays(10)->format('Y-m-d'),
                'end_date' => now()->addDays(25)->format('Y-m-d'),
                'phases' => [
                    [
                        'title' => 'Modelo de negocio',
                        'desc' => 'Selección de producto estrella y empaque competitivo.',
                        'completed' => true,
                        'classes' => [
                            [
                                'title' => '1.1 Estandarización de calidad y presentación del producto',
                                'desc' => 'Peso uniforme, empaque limpio e información básica del productor.',
                                'summary' => 'Un empaque limpio y etiqueta clara permiten vender al mismo cliente semana tras semana.',
                                'completed' => true,
                            ],
                            [
                                'title' => '1.2 Preparación de muestra y degustación o prueba en stand',
                                'desc' => 'Cómo atraer visitantes sin regalar de más tu inventario.',
                                'summary' => 'Define un presupuesto exacto de muestras para atraer compradores reales al stand.',
                                'completed' => true,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Manual FEDER: Preparación para Feria Productiva (PDF)',
                                'resource_type' => 'guia_ligera',
                                'file_path' => 'uploads/materials/guia_feria_empresarial_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/guia_feria_empresarial_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Checklist completo de stand, precios visibles, QR de cobro y rúbrica del jurado.',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Finanzas para la empresa',
                        'desc' => 'Costeo de producción por lote y balance al cierre de feria.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '2.1 Costeo por lote y meta de ventas de feria',
                                'desc' => 'Cuántas unidades debes vender para recuperar insumos y transporte.',
                                'summary' => 'Anota tu inventario inicial al abrir el stand y arquea caja al cerrar la jornada.',
                                'completed' => false,
                            ],
                            [
                                'title' => '2.2 Registro de clientes y pedidos posteriores a la feria',
                                'desc' => 'Conversión de visitantes ocasionales en compradores mensuales.',
                                'summary' => 'Invita a cada comprador a guardar tu número o escanear tu catálogo WhatsApp.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [
                            [
                                'title' => 'Manual Corto FEDER: Costos Reales y Precios (PDF)',
                                'resource_type' => 'plantilla',
                                'file_path' => 'uploads/materials/guia_costos_precios_feder.pdf',
                                'file_size_bytes' => $pdfSize('uploads/materials/guia_costos_precios_feder.pdf'),
                                'bandwidth_tier' => 'ultra_ligero',
                                'offline_summary' => 'Guía de costos fijos, variables y cálculo de margen por lote.',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Marketing Digital',
                        'desc' => 'Difusión previa de tu stand y captación de contactos.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '3.1 Invitación previa en grupos locales y WhatsApp',
                                'desc' => 'Cómo anunciar tu número de stand y promociones del día.',
                                'summary' => 'Comparte ubicación exacta de la feria, número de stand y combo especial 48h antes.',
                                'completed' => false,
                            ],
                            [
                                'title' => '3.2 Fotos y videos cortos durante la feria para tus estados',
                                'desc' => 'Aprovecha la feria para generar contenido real de clientes satisfechos.',
                                'summary' => 'Toma 3 fotos del stand armado y comparte en tus estados para generar pedidos desde casa.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [],
                    ],
                    [
                        'title' => 'Legal',
                        'desc' => 'Permisos municipales de feria, higiene y trazabilidad.',
                        'completed' => false,
                        'classes' => [
                            [
                                'title' => '4.1 Normas municipales de higiene, manipulación y exhibición',
                                'desc' => 'Buenas prácticas para puestos de alimentos y artesanías.',
                                'summary' => 'Mantén limpieza impecable, precios visibles al público y comprobante de reserva de stand.',
                                'completed' => false,
                            ],
                            [
                                'title' => '4.2 Evaluación final ante el jurado FEDER',
                                'desc' => 'Presentación de resultados, impacto social y calificación de rúbrica.',
                                'summary' => 'Expón en 2 minutos tu costo, precio, margen y planes de empleo en tu comunidad.',
                                'completed' => false,
                            ],
                        ],
                        'materials' => [],
                    ],
                ],
            ],
        ];

        $createdCourses = [];
        $prereqLinks = [];

        foreach ($coursesSpec as $spec) {
            $cat = $categories[$spec['category_slug']];
            $phasesSpec = $spec['phases'];
            $prereqSlug = $spec['prerequisite_slug'] ?? null;
            unset($spec['category_slug'], $spec['phases'], $spec['prerequisite_slug']);

            $course = Course::create(array_merge($spec, [
                'category_id' => $cat->id,
                'period_id' => $period->id,
                'price' => 0.00,
                'installments_allowed' => 1,
                'meet_url' => self::DEMO_MEET_URL,
                'whatsapp_url' => self::DEMO_WHATSAPP_URL,
                'status' => 'published',
            ]));

            $createdCourses[$course->slug] = $course;
            if ($prereqSlug) {
                $prereqLinks[] = ['course_id' => $course->id, 'prereq_slug' => $prereqSlug];
            }

            $prevModuleId = null;
            foreach ($phasesSpec as $pIdx => $pData) {
                $mod = CourseModule::create([
                    'course_id' => $course->id,
                    'title' => $pData['title'],
                    'description' => $pData['desc'],
                    'position' => $pIdx + 1,
                    'prerequisite_module_id' => $prevModuleId,
                    'weight_pct' => 25,
                    'is_completed' => (bool) ($pData['completed'] ?? false),
                ]);
                $prevModuleId = $mod->id;

                $classesCreated = [];
                foreach ($pData['classes'] as $cIdx => $clsData) {
                    $cls = CourseClass::create([
                        'module_id' => $mod->id,
                        'title' => $clsData['title'],
                        'description' => $clsData['desc'],
                        'position' => $cIdx + 1,
                        'scheduled_at' => now()->addDays(($pIdx * 5) + $cIdx + 1),
                        'attendance_opens_at' => now()->subHours(2),
                        'attendance_expires_at' => now()->addDays(7),
                        'summary_light_text' => $clsData['summary'],
                        'is_completed' => (bool) ($clsData['completed'] ?? false),
                        'completed_at' => !empty($clsData['completed']) ? now()->subDays(2) : null,
                    ]);
                    $classesCreated[] = $cls;
                }

                foreach ($pData['materials'] as $mIdx => $matData) {
                    $targetCls = $classesCreated[$mIdx % max(1, count($classesCreated))] ?? null;
                    CourseMaterial::create([
                        'course_id' => $course->id,
                        'module_id' => $mod->id,
                        'class_id' => $targetCls?->id,
                        'title' => $matData['title'],
                        'resource_type' => $matData['resource_type'],
                        'file_path' => $matData['file_path'] ?? null,
                        'external_url' => $matData['external_url'] ?? null,
                        'file_size_bytes' => $matData['file_size_bytes'] ?? 0,
                        'bandwidth_tier' => $matData['bandwidth_tier'] ?? 'ultra_ligero',
                        'offline_summary' => $matData['offline_summary'] ?? null,
                        'uploaded_by' => $course->instructor_id,
                    ]);
                }

                if (!empty($pData['eval'])) {
                    $eval = ModuleEvaluation::create([
                        'course_id' => $course->id,
                        'module_id' => $mod->id,
                        'title' => $pData['eval']['title'],
                        'instructions' => $pData['eval']['instructions'],
                        'passing_score' => 60,
                        'max_attempts' => 3,
                        'is_published' => true,
                    ]);

                    foreach ($pData['eval']['questions'] as $qIdx => $q) {
                        EvaluationQuestion::create([
                            'evaluation_id' => $eval->id,
                            'question_text' => $q['text'],
                            'question_type' => $q['type'],
                            'options' => $q['options'],
                            'correct_option' => $q['correct'],
                            'points' => $q['points'],
                            'explanation' => $q['explanation'],
                            'position' => $qIdx + 1,
                        ]);
                    }
                }
            }

            $course->recalculateProgress();
        }

        // Configurar Prerrequisitos Bloqueantes Reales (CU3)
        foreach ($prereqLinks as $link) {
            $prereqCourse = $createdCourses[$link['prereq_slug']] ?? null;
            if ($prereqCourse) {
                CoursePrerequisite::create([
                    'course_id' => $link['course_id'],
                    'prerequisite_course_id' => $prereqCourse->id,
                    'is_mandatory' => true,
                    'notes' => "Requiere aprobar previamente «{$prereqCourse->title}».",
                ]);
            }
        }

        // 9. Matrículas Gratuitas, Asistencias, Foro, Evaluaciones, Feria, Certificados QR y Tiempo de Uso
        $c1 = $createdCourses['escuela-inicial-para-microempresarios'];
        $c2 = $createdCourses['finanzas-para-no-financieros'];
        $c3 = $createdCourses['estrategias-de-ventas-digitales'];
        $c6 = $createdCourses['talleres-productivos-costos-y-ferias'];

        $enr1 = Enrollment::create(['user_id' => $participant1->id, 'course_id' => $c1->id, 'status' => 'completed', 'enrolled_at' => now()->subDays(12), 'completed_at' => now()->subDay()]);
        Enrollment::create(['user_id' => $participant2->id, 'course_id' => $c1->id, 'status' => 'active', 'enrolled_at' => now()->subDays(10)]);
        $enr3 = Enrollment::create(['user_id' => $participant3->id, 'course_id' => $c1->id, 'status' => 'completed', 'enrolled_at' => now()->subDays(12), 'completed_at' => now()->subDays(2)]);
        Enrollment::create(['user_id' => $participant4->id, 'course_id' => $c1->id, 'status' => 'active', 'enrolled_at' => now()->subDays(9)]);
        Enrollment::create(['user_id' => $participant5->id, 'course_id' => $c1->id, 'status' => 'active', 'enrolled_at' => now()->subDays(8)]);

        Enrollment::create(['user_id' => $participant1->id, 'course_id' => $c2->id, 'status' => 'active', 'enrolled_at' => now()->subDays(5)]);
        Enrollment::create(['user_id' => $participant2->id, 'course_id' => $c2->id, 'status' => 'active', 'enrolled_at' => now()->subDays(4)]);
        $enrC2P5 = Enrollment::create(['user_id' => $participant5->id, 'course_id' => $c2->id, 'status' => 'completed', 'enrolled_at' => now()->subDays(7), 'completed_at' => now()->subDay()]);

        Enrollment::create(['user_id' => $participant2->id, 'course_id' => $c3->id, 'status' => 'active', 'enrolled_at' => now()->subDays(4)]);
        Enrollment::create(['user_id' => $participant3->id, 'course_id' => $c3->id, 'status' => 'active', 'enrolled_at' => now()->subDays(3)]);
        Enrollment::create(['user_id' => $participant4->id, 'course_id' => $c3->id, 'status' => 'active', 'enrolled_at' => now()->subDays(3)]);
        Enrollment::create(['user_id' => $participant6->id, 'course_id' => $c3->id, 'status' => 'active', 'enrolled_at' => now()->subDays(2)]);

        Enrollment::create(['user_id' => $participant4->id, 'course_id' => $c6->id, 'status' => 'active', 'enrolled_at' => now()->subDays(6)]);
        Enrollment::create(['user_id' => $participant5->id, 'course_id' => $c6->id, 'status' => 'active', 'enrolled_at' => now()->subDays(5)]);
        Enrollment::create(['user_id' => $participant6->id, 'course_id' => $c6->id, 'status' => 'active', 'enrolled_at' => now()->subDays(4)]);

        // Asistencias reales en múltiples cursos
        $c1FirstCls = $c1->classes()->first();
        if ($c1FirstCls) {
            foreach ([$participant1, $participant2, $participant3, $participant4, $participant5] as $stu) {
                CourseAttendance::create([
                    'user_id' => $stu->id,
                    'class_id' => $c1FirstCls->id,
                    'status' => 'presente',
                    'recorded_by' => $instructor->id,
                    'created_at' => now()->subDays(6),
                    'updated_at' => now()->subDays(6),
                ]);
            }
        }
        $c2FirstCls = $c2->classes()->first();
        if ($c2FirstCls) {
            foreach ([$participant2, $participant5] as $stu) {
                CourseAttendance::create([
                    'user_id' => $stu->id,
                    'class_id' => $c2FirstCls->id,
                    'status' => 'presente',
                    'recorded_by' => $instructor->id,
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(3),
                ]);
            }
        }
        $c3FirstCls = $c3->classes()->first();
        if ($c3FirstCls) {
            foreach ([$participant2, $participant3, $participant4] as $stu) {
                CourseAttendance::create([
                    'user_id' => $stu->id,
                    'class_id' => $c3FirstCls->id,
                    'status' => 'presente',
                    'recorded_by' => $instructor->id,
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ]);
            }
        }

        // Hilos de Foro reales en distintos cursos
        $t1 = ForumThread::create([
            'course_id' => $c1->id,
            'module_id' => $c1->modules()->first()?->id,
            'class_id' => $c1FirstCls?->id,
            'user_id' => $participant1->id,
            'title' => '¿Cómo calculo el costo de mi mano de obra en repostería?',
            'body' => 'Buenas tardes profe Carlos, siempre cobro solo los ingredientes y el gas, pero no sé cuánto anotarme por mis horas de trabajo. ¿Cómo me recomienda hacerlo fácil?',
            'is_pinned' => true,
            'is_resolved' => true,
        ]);
        ForumReply::create([
            'thread_id' => $t1->id,
            'user_id' => $instructor->id,
            'body' => '¡Excelente pregunta María Fernanda! Define un sueldo mensual meta para ti, divídelo entre los días y horas que horneas al mes, y suma ese valor por hora a cada pedido. Revisa la guía PDF de la Fase 2.',
            'is_instructor_answer' => true,
        ]);

        $t2 = ForumThread::create([
            'course_id' => $c2->id,
            'module_id' => $c2->modules()->first()?->id,
            'class_id' => $c2FirstCls?->id,
            'user_id' => $participant2->id,
            'title' => '¿Cómo incluyo el desgaste de mi soldadora en el costo del mueble?',
            'body' => 'Para muebles metálicos uso electrodos, discos de corte y energía eléctrica trifásica. ¿Lo cargo como costo fijo o variable?',
            'is_pinned' => false,
            'is_resolved' => true,
        ]);
        ForumReply::create([
            'thread_id' => $t2->id,
            'user_id' => $instructor->id,
            'body' => 'Buen punto Roberto: los electrodos y discos van al costo variable por mueble; mientras que la reserva mensual para mantenimiento de tu soldadora entra como costo fijo mensual del taller.',
            'is_instructor_answer' => true,
        ]);

        $t3 = ForumThread::create([
            'course_id' => $c3->id,
            'module_id' => $c3->modules()->first()?->id,
            'class_id' => $c3FirstCls?->id,
            'user_id' => $participant6->id,
            'title' => 'Consulta sobre tamaños recomendados para catálogo de WhatsApp',
            'body' => 'Cuando subo fotos de mis piezas de cerámica, ¿es mejor formato cuadrado o vertical para que no recorte los bordes?',
            'is_pinned' => false,
            'is_resolved' => false,
        ]);

        // Evaluaciones rendidas de muestra
        $c1Eval = ModuleEvaluation::where('course_id', $c1->id)->first();
        if ($c1Eval) {
            foreach ([$participant1, $participant3] as $stu) {
                EvaluationAttempt::create([
                    'evaluation_id' => $c1Eval->id,
                    'user_id' => $stu->id,
                    'answers' => ['1' => 'A', '2' => 'V'],
                    'score' => 100,
                    'max_score' => 100,
                    'percentage' => 100,
                    'passed' => true,
                    'submitted_at' => now()->subDays(3),
                ]);
            }
        }

        // Participaciones en Feria Productiva Empresarial (CU5)
        FairParticipation::create([
            'course_id' => $c1->id,
            'user_id' => $participant1->id,
            'enrollment_id' => $enr1->id,
            'project_title' => 'Línea de Panadería Integral sin Conservantes Doña Fer',
            'stand_code' => 'STAND-A04',
            'fair_date' => now()->subDays(2)->format('Y-m-d'),
            'fair_location' => 'Plaza Principal de Montero · Feria Productiva FEDER',
            'rubric_score' => 94.00,
            'participation_status' => 'destacado',
            'evaluator_feedback' => 'Excelente presentación de costos, degustación e imagen comercial.',
            'evaluated_by' => $instructor->id,
        ]);

        FairParticipation::create([
            'course_id' => $c1->id,
            'user_id' => $participant3->id,
            'enrollment_id' => $enr3->id,
            'project_title' => 'Colección de Mandiles y Uniformes Bordados El Norte',
            'stand_code' => 'STAND-A07',
            'fair_date' => now()->subDays(2)->format('Y-m-d'),
            'fair_location' => 'Plaza Principal de Montero · Feria Productiva FEDER',
            'rubric_score' => 91.00,
            'participation_status' => 'participo',
            'evaluator_feedback' => 'Muy buen acabado textil y catálogo QR listo en stand.',
            'evaluated_by' => $instructor->id,
        ]);

        // Certificados Oficiales con QR verificable públicamente (CU6)
        Certificate::create([
            'course_id' => $c1->id,
            'user_id' => $participant1->id,
            'verification_code' => 'FEDER-2026-MF001',
            'enrollment_id' => $enr1->id,
            'issued_by' => $director->id,
            'final_grade' => 96.00,
            'attendance_pct' => 100,
            'hours_completed' => 60,
            'status' => 'valid',
            'issued_at' => now()->subDay(),
        ]);

        Certificate::create([
            'course_id' => $c1->id,
            'user_id' => $participant3->id,
            'verification_code' => 'FEDER-2026-LV002',
            'enrollment_id' => $enr3->id,
            'issued_by' => $director->id,
            'final_grade' => 92.00,
            'attendance_pct' => 95,
            'hours_completed' => 60,
            'status' => 'valid',
            'issued_at' => now()->subDays(2),
        ]);

        Certificate::create([
            'course_id' => $c2->id,
            'user_id' => $participant5->id,
            'verification_code' => 'FEDER-2026-CF003',
            'enrollment_id' => $enrC2P5->id,
            'issued_by' => $director->id,
            'final_grade' => 89.00,
            'attendance_pct' => 92,
            'hours_completed' => 48,
            'status' => 'valid',
            'issued_at' => now()->subDay(),
        ]);

        // Sesiones reales de tiempo activo en plataforma y aulas (dispara Trigger trg_sync_user_active_seconds en PostgreSQL)
        $sessions = [
            [$participant1->id, $c1->id, 5400, 90, '/aula/' . $c1->id],
            [$participant1->id, $c2->id, 1800, 30, '/aula/' . $c2->id],
            [$participant2->id, $c1->id, 3300, 55, '/aula/' . $c1->id],
            [$participant2->id, $c3->id, 2100, 35, '/aula/' . $c3->id],
            [$participant3->id, $c1->id, 4800, 80, '/aula/' . $c1->id],
            [$participant4->id, $c3->id, 2700, 45, '/aula/' . $c3->id],
            [$participant5->id, $c2->id, 4200, 70, '/aula/' . $c2->id],
            [$participant6->id, $c3->id, 1500, 25, '/aula/' . $c3->id],
        ];
        foreach ($sessions as [$uid, $cid, $secs, $pings, $path]) {
            UsageSession::create([
                'user_id' => $uid,
                'course_id' => $cid,
                'session_date' => now()->format('Y-m-d'),
                'seconds_spent' => $secs,
                'pings_count' => $pings,
                'last_path' => $path,
                'last_ping_at' => now(),
            ]);
        }

        // Respaldo de total_active_seconds en SQLite (donde no existen triggers PL/pgSQL)
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'pgsql') {
            foreach ([$participant1, $participant2, $participant3, $participant4, $participant5, $participant6] as $u) {
                $sum = (int) UsageSession::where('user_id', $u->id)->sum('seconds_spent');
                $u->update(['total_active_seconds' => $sum]);
            }
        }
    }
}
