<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$pdo = db();

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("TRUNCATE TABLE categories");
    $pdo->exec("TRUNCATE TABLE courses");
    $pdo->exec("TRUNCATE TABLE modules");
    $pdo->exec("TRUNCATE TABLE course_classes");
    $pdo->exec("TRUNCATE TABLE course_materials");
    $pdo->exec("TRUNCATE TABLE enrollments");
    // User wants MORE users, we won't truncate users, just add.

    echo "Tables truncated...\n";

    // 1. Agregar más instructores (5)
    $pass = password_hash('Demo123!', PASSWORD_DEFAULT);
    $instructoresIds = [];
    // Recopilar instructores actuales
    $currInst = q_all("SELECT id FROM users WHERE role='instructor'");
    foreach($currInst as $i) $instructoresIds[] = (int)$i['id'];

    $nombres = ['Carlos', 'Andrés', 'Sofía', 'María', 'Pedro', 'Laura', 'Javier', 'Elena', 'Ricardo', 'Carmen'];
    $apellidos = ['Gómez', 'Silva', 'Mendoza', 'Cruz', 'Torres', 'Paz', 'Ríos', 'Castro', 'Rojas', 'Vargas'];
    $avatars = ['aurora', 'ember', 'ocean', 'forest', 'dusk'];

    for ($i = 0; $i < 8; $i++) {
        $name = $nombres[array_rand($nombres)] . ' ' . $apellidos[array_rand($apellidos)];
        $email = 'inst_' . uniqid() . '@feder.com';
        $av = $avatars[array_rand($avatars)];
        $bdate = date('Y-m-d', strtotime('-' . rand(25, 50) . ' years'));
        q("INSERT INTO users (name,email,password_hash,role,avatar,phone,id_document,birth_date) VALUES (?,?,?,?,?,?,?,?)",
            [$name, $email, $pass, 'instructor', $av, '555-'.rand(1000,9999), rand(10000000,99999999), $bdate]);
        $instructoresIds[] = (int)$pdo->lastInsertId();
    }

    // 2. Agregar más estudiantes (40)
    $estudiantesIds = [];
    $currEst = q_all("SELECT id FROM users WHERE role='student'");
    foreach($currEst as $e) $estudiantesIds[] = (int)$e['id'];

    for ($i = 0; $i < 60; $i++) {
        $name = $nombres[array_rand($nombres)] . ' ' . $apellidos[array_rand($apellidos)];
        $email = 'est_' . uniqid() . '@feder.com';
        $av = $avatars[array_rand($avatars)];
        $bdate = date('Y-m-d', strtotime('-' . rand(18, 40) . ' years'));
        q("INSERT INTO users (name,email,password_hash,role,avatar,phone,id_document,birth_date) VALUES (?,?,?,?,?,?,?,?)",
            [$name, $email, $pass, 'student', $av, '555-'.rand(1000,9999), rand(10000000,99999999), $bdate]);
        $estudiantesIds[] = (int)$pdo->lastInsertId();
    }

    echo "Users generated...\n";

    // 3. Categorías
    $cats = [
        ['Programación Avanzada', 'programacion-avanzada', 'code'],
        ['Negocios y Emprendimiento', 'negocios', 'briefcase'],
        ['Diseño Gráfico', 'diseno', 'pen-tool'],
        ['Marketing Estratégico', 'marketing', 'target'],
        ['Ciencia de Datos', 'data', 'database'],
        ['Idiomas', 'idiomas', 'globe']
    ];
    $catIds = [];
    foreach ($cats as $c) {
        q("INSERT INTO categories (name,slug,icon) VALUES (?,?,?)", $c);
        $catIds[] = (int)$pdo->lastInsertId();
    }

    // 4. Cursos (Generar unos 15 cursos)
    $courseWords = ['Fundamentos de', 'Masterclass en', 'Estrategias de', 'Introducción a', 'Taller Práctico:', 'Dominando'];
    $courseTopics = ['Python', 'Marketing Digital', 'Finanzas Corporativas', 'Liderazgo', 'Diseño UX/UI', 'SQL y Bases de Datos', 'Inglés de Negocios', 'Ventas B2B'];
    $levels = ['inicial', 'intermedio', 'avanzado'];

    $coursesCreated = [];
    for ($i = 0; $i < 20; $i++) {
        $title = $courseWords[array_rand($courseWords)] . ' ' . $courseTopics[array_rand($courseTopics)] . ' ' . rand(1,99);
        $slug = slugify($title);
        $catId = $catIds[array_rand($catIds)];
        $instId = $instructoresIds[array_rand($instructoresIds)];
        $level = $levels[array_rand($levels)];
        $cap = rand(20, 50);
        
        // Simular inicio hace 1-3 semanas
        $daysAgo = rand(7, 25);
        $startDate = date('Y-m-d', strtotime("-$daysAgo days"));
        $endDate = date('Y-m-d', strtotime("+" . rand(10, 30) . " days"));

        $color = ['#4338CA', '#0F766E', '#B45309', '#7C3AED', '#15803D', '#BE123C'][array_rand([0,1,2,3,4,5])];

        q("INSERT INTO courses (category_id,instructor_id,title,slug,hook,description,outcome,level,duration_minutes,capacity,start_date,end_date,color,status)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
           [$catId, $instId, $title, $slug, 'Aprende rápido y seguro.', 'Descripción detallada del curso generado automáticamente para simular 3 semanas de datos. Esto es un bootcamp intensivo.', 'Dominar la herramienta al 100%.', $level, 0, $cap, $startDate, $endDate, $color, 'published']);
        
        $courseId = (int)$pdo->lastInsertId();
        $coursesCreated[] = ['id' => $courseId, 'start_date' => $startDate, 'capacity' => $cap];

        // 5. Módulos (3-5 por curso)
        $numMods = rand(3, 5);
        $classCount = 0;
        $completedClasses = 0;
        for ($m = 1; $m <= $numMods; $m++) {
            q("INSERT INTO modules (course_id,title,position) VALUES (?,?,?)", [$courseId, "Módulo $m: Conceptos Base", $m]);
            $modId = (int)$pdo->lastInsertId();

            // 6. Clases (2-4 por módulo)
            $numClasses = rand(2, 4);
            for ($c = 1; $c <= $numClasses; $c++) {
                $classCount++;
                // Lógica de avance: si el curso empezó hace 20 días, la clase 1 debió ser dictada hace 18, la clase 2 hace 15, etc.
                $daysForThisClass = $daysAgo - ($classCount * 2);
                $isCompleted = $daysForThisClass > 0 ? 1 : 0;
                $completedAt = $isCompleted ? date('Y-m-d H:i:s', strtotime("-$daysForThisClass days")) : null;
                
                if ($isCompleted) $completedClasses++;

                q("INSERT INTO course_classes (module_id,title,is_completed,completed_at) VALUES (?,?,?,?)",
                  [$modId, "Clase $classCount del Módulo", $isCompleted, $completedAt]);
                $classId = (int)$pdo->lastInsertId();

                // Material de clase
                if ($isCompleted && rand(0,1) == 1) {
                    q("INSERT INTO course_materials (course_id,class_id,title,file_path) VALUES (?,?,?,?)",
                      [$courseId, $classId, "Presentación C$classCount", "assets/pdf_dummy.pdf"]);
                }
            }
        }
        
        // Actualizar porcentaje
        $pct = $classCount > 0 ? (int)round(($completedClasses / $classCount) * 100) : 0;
        q("UPDATE courses SET progress_pct=? WHERE id=?", [$pct, $courseId]);

        // Material general
        q("INSERT INTO course_materials (course_id,class_id,title,file_path) VALUES (?,NULL,?,?)",
          [$courseId, "Sílabo del Curso", "assets/pdf_dummy.pdf"]);
    }

    echo "Courses, Modules, Classes and Materials generated...\n";

    // 7. Inscripciones
    foreach ($coursesCreated as $c) {
        $enrolled = rand(5, $c['capacity']); // Inscribir estudiantes al azar (sin pasarse del cupo)
        shuffle($estudiantesIds);
        $selected = array_slice($estudiantesIds, 0, $enrolled);

        foreach ($selected as $uid) {
            // Se inscribieron un poco antes o justo en el start_date
            $enrollDate = date('Y-m-d H:i:s', strtotime($c['start_date']) - rand(10000, 500000));
            q("INSERT INTO enrollments (user_id,course_id,status,enrolled_at) VALUES (?,?,?,?)",
              [$uid, $c['id'], 'active', $enrollDate]);
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    echo "Enrollments done. System ready with 3 weeks of simulated data.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
