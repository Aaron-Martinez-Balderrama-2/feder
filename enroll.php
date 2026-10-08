<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

if ($user['role'] !== 'student') {
    flash('warn', 'Los administradores o instructores no pueden inscribirse a cursos como alumnos.');
    redirect('admin/index.php');
}
if (!is_post()) {
    redirect('catalog.php');
}
csrf_verify();
$uid = (int) $user['id'];

if ($pid = input_int('package_id')) {
    $results = enroll_package($uid, $pid);
    $ok = array_filter($results, fn($r) => $r['code'] === 'enrolled');
    $full = array_filter($results, fn($r) => $r['code'] === 'full' || $r['code'] === 'closed');
    if ($ok) {
        flash('success', '¡Ruta activada! Te inscribimos en ' . count($ok) . ' curso' . (count($ok) > 1 ? 's' : '') . '.');
    } elseif (!$full) {
        flash('info', 'Ya estabas inscrito en todos los cursos de esta ruta.');
    }
    foreach ($full as $r) {
        flash('warn', $r['msg']);
    }
    redirect('dashboard.php');
}

$cid = input_int('course_id');
$r = enroll_course($uid, $cid);
flash($r['ok'] ? ($r['code'] === 'enrolled' ? 'success' : 'info') : 'warn', $r['msg']);

$slug = q_val('SELECT slug FROM courses WHERE id=?', [$cid]);
redirect($slug ? 'course.php?slug=' . urlencode($slug) : 'catalog.php');
