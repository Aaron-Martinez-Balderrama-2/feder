<?php
declare(strict_types=1);

/* =====================================================================
   SERVICIOS DE DOMINIO
   ===================================================================== */

/* ---------------- Cupos ---------------- */
function course_seats(int $courseId): array
{
    $r = q_one('SELECT capacity, taken, available FROM v_course_seats WHERE course_id=?', [$courseId]);
    return $r ? array_map('intval', $r) : ['capacity' => 0, 'taken' => 0, 'available' => 0];
}

/**
 * Inscripción con control de cupos ACID.
 * SELECT ... FOR UPDATE bloquea la fila del curso: dos personas no pueden
 * tomar el último cupo al mismo tiempo.
 */
function enroll_course(int $uid, int $courseId): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $c = q_one('SELECT id,title,capacity,status,start_date,end_date FROM courses WHERE id=? FOR UPDATE', [$courseId]);
        if (!$c || $c['status'] !== 'published') {
            $pdo->rollBack();
            return ['ok' => false, 'code' => 'closed', 'msg' => 'Este curso no está disponible.'];
        }
        
        $start = strtotime($c['start_date']);
        $end = strtotime($c['end_date']);
        $now = strtotime(date('Y-m-d'));
        $halfway = $start + (($end - $start) / 2);
        
        if ($now > $halfway) {
            $pdo->rollBack();
            return ['ok' => false, 'code' => 'closed', 'msg' => 'El curso ya superó el 50% de su avance. Inscripciones cerradas.'];
        }
        $existing = q_one('SELECT id,status FROM enrollments WHERE user_id=? AND course_id=?', [$uid, $courseId]);
        if ($existing && $existing['status'] !== 'cancelled') {
            $pdo->rollBack();
            return ['ok' => true, 'code' => 'already', 'msg' => 'Ya estabas inscrito en «' . $c['title'] . '».'];
        }
        $taken = (int) q_val("SELECT COUNT(*) FROM enrollments WHERE course_id=? AND status IN ('active','completed')", [$courseId]);
        if ($taken >= (int) $c['capacity']) {
            $pdo->rollBack();
            return ['ok' => false, 'code' => 'full', 'msg' => 'Cupos agotados en «' . $c['title'] . '».'];
        }
        if ($existing) {
            q("UPDATE enrollments SET status='active', enrolled_at=NOW() WHERE id=?", [$existing['id']]);
        } else {
            q('INSERT INTO enrollments (user_id,course_id) VALUES (?,?)', [$uid, $courseId]);
        }
        $pdo->commit();
        return ['ok' => true, 'code' => 'enrolled', 'msg' => '¡Listo! Tienes tu lugar en «' . $c['title'] . '».'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[feder/enroll] ' . $e->getMessage());
        return ['ok' => false, 'code' => 'error', 'msg' => 'No pudimos completar la inscripción. Inténtalo otra vez.'];
    }
}


function is_enrolled(int $uid, int $courseId): bool
{
    return (bool) q_val("SELECT 1 FROM enrollments WHERE user_id=? AND course_id=? AND status IN ('active','completed')", [$uid, $courseId]);
}

/* ---------------- Estructura de curso ---------------- */
function course_outline(int $courseId): array
{
    $modules = q_all('SELECT id,title,position FROM modules WHERE course_id=? ORDER BY position,id', [$courseId]);
    $classes = q_all('SELECT c.id,c.module_id,c.title,c.is_completed,c.completed_at, c.attendance_expires_at
                      FROM course_classes c JOIN modules m ON m.id=c.module_id
                      WHERE m.course_id=? ORDER BY m.position,m.id,c.id', [$courseId]);
    $by = [];
    foreach ($classes as $c) {
        $by[$c['module_id']][] = $c;
    }
    foreach ($modules as &$m) {
        $m['classes'] = $by[$m['id']] ?? [];
    }
    return $modules;
}

function recalc_course_progress(int $courseId): int
{
    $total = (int) q_val('SELECT COUNT(*) FROM course_classes c JOIN modules m ON m.id=c.module_id WHERE m.course_id=?', [$courseId]);
    $done = (int) q_val('SELECT COUNT(*) FROM course_classes c JOIN modules m ON m.id=c.module_id WHERE m.course_id=? AND c.is_completed=1', [$courseId]);
    $pct = $total > 0 ? (int) round($done / $total * 100) : 0;
    q('UPDATE courses SET progress_pct=? WHERE id=?', [$pct, $courseId]);
    return $pct;
}

function user_enrollments(int $uid): array
{
    return q_all("SELECT e.*, c.title, c.slug, c.color, c.hook, c.duration_minutes, c.level, c.progress_pct AS global_progress
                  FROM enrollments e JOIN courses c ON c.id=e.course_id
                  WHERE e.user_id=? AND e.status IN ('active','completed')
                  ORDER BY e.status='completed', c.progress_pct DESC, e.enrolled_at DESC", [$uid]);
}



/* ---------------- Estadísticas públicas (prueba social) ---------------- */
function platform_stats(): array
{
    return [
        'students' => (int) q_val("SELECT COUNT(*) FROM users WHERE role='student'"),
        'courses'  => (int) q_val("SELECT COUNT(*) FROM courses WHERE status='published'"),
        'classes'  => (int) q_val("SELECT COUNT(*) FROM course_classes WHERE is_completed=1"),
    ];
}

function published_courses(array $filters = []): array
{
    $where = ["c.status='published'"];
    $params = [];
    if (!empty($filters['category'])) {
        $where[] = 'cat.slug=?';
        $params[] = $filters['category'];
    }
    if (!empty($filters['level'])) {
        $where[] = 'c.level=?';
        $params[] = $filters['level'];
    }
    if (!empty($filters['q'])) {
        $where[] = '(c.title LIKE ? OR c.hook LIKE ? OR c.description LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        array_push($params, $like, $like, $like);
    }
    $limit = isset($filters['limit']) ? ' LIMIT ' . (int) $filters['limit'] : '';
    return q_all('SELECT c.*, cat.name AS category, cat.slug AS category_slug, cat.icon AS category_icon,
                         s.taken, s.available, u.name AS instructor,
                         (SELECT COUNT(*) FROM course_classes cls JOIN modules m ON m.id=cls.module_id WHERE m.course_id=c.id) AS class_count
                  FROM courses c
                  LEFT JOIN categories cat ON cat.id=c.category_id
                  LEFT JOIN v_course_seats s ON s.course_id=c.id
                  LEFT JOIN users u ON u.id=c.instructor_id
                  WHERE ' . implode(' AND ', $where) . ' ORDER BY c.start_date DESC, c.id' . $limit, $params);
}
