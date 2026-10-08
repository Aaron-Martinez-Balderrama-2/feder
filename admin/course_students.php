<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if (!in_array($user['role'], ['admin', 'instructor'], true)) {
    redirect('dashboard.php');
}

$course_id = (int) input('course_id');
$course = q_one("SELECT * FROM courses WHERE id=?", [$course_id]);

if (!$course) {
    flash('error', 'Curso no encontrado.');
    redirect('admin/courses.php');
}

if ($user['role'] === 'instructor' && (int)$course['instructor_id'] !== (int)$user['id']) {
    flash('error', 'No tienes permiso para ver este curso.');
    redirect('admin/courses.php');
}

$pag = paginate("
    SELECT u.id, u.name, u.email, u.phone, u.id_document, e.enrolled_at, e.status
    FROM enrollments e
    JOIN users u ON u.id = e.user_id
    WHERE e.course_id=? AND e.status IN ('active','completed')
    ORDER BY e.enrolled_at DESC
", [$course_id], 15);

$students = $pag['data'];

$page_title = 'Alumnos Inscritos - ' . APP_NAME;
require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/header.php';
?>

<main class="section">
  <div class="container">
    <div class="block-title">
      <h2>Alumnos en: <?= e($course['title']) ?></h2>
      <a href="<?= url('admin/courses.php') ?>" class="btn btn--soft">Volver a Cursos</a>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Carnet</th>
            <th>Inscripción</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): ?>
            <tr>
              <td><?= e($s['name']) ?></td>
              <td><?= e($s['email']) ?></td>
              <td><?= $s['phone'] ? e($s['phone']) : '<span class="muted">—</span>' ?></td>
              <td><?= $s['id_document'] ? e($s['id_document']) : '<span class="muted">—</span>' ?></td>
              <td><?= date_es($s['enrolled_at']) ?></td>
              <td>
                <div class="actions">
                  <?php if ($s['phone']): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $s['phone']) ?>" target="_blank" class="btn btn--sm btn--soft" style="color:var(--growth)"><?= icon('message-square') ?> WhatsApp</a>
                  <?php else: ?>
                    <span class="muted">No disponible</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($students)): ?>
            <tr><td colspan="6" class="text-center muted" style="padding:2rem">Aún no hay alumnos inscritos.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?= $pag['html'] ?>
  </div>
</main>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
