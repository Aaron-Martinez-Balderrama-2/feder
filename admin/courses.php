<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if (!in_array($user['role'], ['admin', 'instructor'], true)) {
    redirect('dashboard.php');
}

$page_title = 'Gestión de Cursos - ' . APP_NAME;
if ($user['role'] === 'admin') {
    $pag = paginate("SELECT * FROM courses ORDER BY created_at DESC", [], 10);
} else {
    $pag = paginate("SELECT * FROM courses WHERE instructor_id=? ORDER BY created_at DESC", [$user['id']], 10);
}
$courses = $pag['data'];

require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/header.php';
?>

<main class="section">
  <div class="container">
    <div class="block-title">
      <h2>Gestión de Cursos</h2>
      <div>
        <a href="<?= url('admin/course_edit.php') ?>" class="btn btn--primary">Nuevo Curso</a>
        <a href="<?= url('admin/index.php') ?>" class="btn btn--soft">Volver</a>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Título</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $c): ?>
          <tr>
            <td><?= e($c['title']) ?></td>
            <td><span class="chip"><?= e($c['status']) ?></span></td>
            <td>
              <div class="actions">
                <a href="<?= url('course.php?slug=' . $c['slug']) ?>" class="btn btn--sm btn--ghost" target="_blank">Ver</a>
                <a href="<?= url('admin/course_students.php?course_id=' . $c['id']) ?>" class="btn btn--sm btn--ghost">Alumnos</a>
                <a href="<?= url('admin/course_classes.php?course_id=' . $c['id']) ?>" class="btn btn--sm btn--ghost">Clases</a>
                <a href="<?= url('admin/materials.php?course_id=' . $c['id']) ?>" class="btn btn--sm btn--ghost">Materiales</a>
                <a href="<?= url('admin/course_edit.php?id=' . $c['id']) ?>" class="btn btn--sm btn--soft">Editar</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= $pag['html'] ?>
  </div>
</main>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
