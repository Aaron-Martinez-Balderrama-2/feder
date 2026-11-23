<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseClass;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Ajustes globales de sede
        Setting::setVal('office_address', 'Av. Central 123, Edificio Feder, Piso 4, Ciudad Mente');
        Setting::setVal('office_map_embed', '-17.784875,-63.181283');

        // 2. Usuarios Base Demostrativos
        $admin = User::firstOrCreate(
            ['email' => 'a3ronmartinez@gmail.com'],
            [
                'name' => 'Aaron Martinez',
                'password' => 'password',
                'role' => 'admin',
                'id_document' => '10000001',
                'phone' => '70000001',
                'avatar' => 'aurora',
                'gender' => 'Prefiero no decirlo',
                'is_active' => true,
            ]
        );

        $instructor = User::firstOrCreate(
            ['email' => 'docente@feder.edu.bo'],
            [
                'name' => 'Prof. Carlos Mendoza',
                'password' => 'password',
                'role' => 'instructor',
                'id_document' => '20000002',
                'phone' => '70000002',
                'avatar' => 'ember',
                'gender' => 'Masculino',
                'is_active' => true,
            ]
        );

        $student = User::firstOrCreate(
            ['email' => 'estudiante@feder.edu.bo'],
            [
                'name' => 'Estudiante Demo',
                'password' => 'password',
                'role' => 'student',
                'id_document' => '30000003',
                'phone' => '70000003',
                'birth_date' => '2000-01-15',
                'avatar' => 'ocean',
                'gender' => 'Femenino',
                'is_active' => true,
            ]
        );

        // 3. Categorías Oficiales
        $categoriesData = [
            ['name' => 'Marketing Digital', 'slug' => 'marketing-digital', 'icon' => 'target'],
            ['name' => 'Finanzas Corporativas', 'slug' => 'finanzas-corporativas', 'icon' => 'coin'],
            ['name' => 'Liderazgo y RRHH', 'slug' => 'liderazgo-y-rrhh', 'icon' => 'users'],
            ['name' => 'Ciencia de Datos', 'slug' => 'ciencia-de-datos', 'icon' => 'brain'],
            ['name' => 'Negocios Internacionales', 'slug' => 'negocios-internacionales', 'icon' => 'compass'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::firstOrCreate(['slug' => $c['slug']], $c);
        }

        // 4. Cursos Piloto con las 4 Fases Obligatorias
        $coursesData = [
            [
                'category_slug' => 'marketing-digital',
                'title' => 'Estrategias de Ventas Digitales',
                'slug' => 'estrategias-de-ventas-digitales',
                'hook' => 'Convierte prospectos en clientes leales mediante funnels estructurados.',
                'description' => 'Un bootcamp integral de 4 fases para dominar la prospección, negociación, conversión y fidelización digital en el entorno B2B y B2C.',
                'outcome' => 'Diseñar y ejecutar un embudo de ventas digital rentable y medible.',
                'level' => 'intermedio',
                'capacity' => 35,
                'start_date' => now()->subDays(10)->format('Y-m-d'),
                'end_date' => now()->addDays(20)->format('Y-m-d'),
                'color' => '#4338CA',
                'meet_url' => 'https://meet.google.com/ueb-egjg-mvw',
                'whatsapp_url' => 'https://chat.whatsapp.com/CVDu2PQ2Sc73vzCXGAKzKe',
                'status' => 'published',
            ],
            [
                'category_slug' => 'finanzas-corporativas',
                'title' => 'Finanzas para No Financieros',
                'slug' => 'finanzas-para-no-financieros',
                'hook' => 'Comprende el flujo de caja, balance y rentabilidad sin tecnicismos oscuros.',
                'description' => 'Aprende a interpretar estados financieros, estructurar costos directos e indirectos y proyectar la viabilidad de cualquier unidad de negocio.',
                'outcome' => 'Tomar decisiones estratégicas fundamentadas en indicadores financieros clave.',
                'level' => 'inicial',
                'capacity' => 30,
                'start_date' => now()->subDays(5)->format('Y-m-d'),
                'end_date' => now()->addDays(25)->format('Y-m-d'),
                'color' => '#0F766E',
                'meet_url' => 'https://meet.google.com/ueb-egjg-mvw',
                'whatsapp_url' => 'https://chat.whatsapp.com/CVDu2PQ2Sc73vzCXGAKzKe',
                'status' => 'published',
            ],
            [
                'category_slug' => 'ciencia-de-datos',
                'title' => 'Big Data y Analítica con Python',
                'slug' => 'big-data-y-analitica-con-python',
                'hook' => 'Extrae patrones accionables de millones de filas de datos.',
                'description' => 'Aprende procesamiento masivo, limpieza de datos, visualización estadística y modelos predictivos para resolver retos empresariales reales.',
                'outcome' => 'Construir dashboards y modelos de analítica de datos en Python.',
                'level' => 'avanzado',
                'capacity' => 25,
                'start_date' => now()->subDays(2)->format('Y-m-d'),
                'end_date' => now()->addDays(28)->format('Y-m-d'),
                'color' => '#7C3AED',
                'meet_url' => 'https://meet.google.com/ueb-egjg-mvw',
                'whatsapp_url' => 'https://chat.whatsapp.com/CVDu2PQ2Sc73vzCXGAKzKe',
                'status' => 'published',
            ],
        ];

        $mandatoryPhases = [
            'Fase 1: Modelo de Negocio',
            'Fase 2: Finanzas para la empresa',
            'Fase 3: Marketing Digital',
            'Fase 4: Legal y Cumplimiento Normativo',
        ];

        foreach ($coursesData as $c) {
            $cat = $categories[$c['category_slug']];
            unset($c['category_slug']);
            $c['category_id'] = $cat->id;
            $c['instructor_id'] = $instructor->id;

            $course = Course::firstOrCreate(['slug' => $c['slug']], $c);

            // Crear las 4 fases fijas si no existen
            if ($course->modules()->count() === 0) {
                foreach ($mandatoryPhases as $idx => $phaseTitle) {
                    $mod = CourseModule::create([
                        'course_id' => $course->id,
                        'title' => $phaseTitle,
                        'position' => $idx + 1,
                    ]);

                    // Crear 2 clases por fase
                    for ($cl = 1; $cl <= 2; $cl++) {
                        $isComp = ($idx === 0); // La fase 1 ya fue dictada
                        CourseClass::create([
                            'module_id' => $mod->id,
                            'title' => "Clase $cl de {$phaseTitle}",
                            'is_completed' => $isComp,
                            'completed_at' => $isComp ? now()->subDays(8 - $cl) : null,
                            'attendance_expires_at' => null,
                        ]);
                    }
                }
                $course->recalculateProgress();
            }
        }
    }
}
