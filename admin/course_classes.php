<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if (!in_array($user['role'], ['admin', 'instructor'], true)) {
    redirect('dashboard.php');
}

$cid = (int) input('course_id');
$course = q_one("SELECT * FROM courses WHERE id=?", [$cid]);

if (!$course) {
    flash('error', 'Curso no encontrado.');
    redirect('admin/courses.php');
}

if ($user['role'] !== 'admin' && (int) $course['instructor_id'] !== (int) $user['id']) {
    flash('warn', 'No puedes editar este curso.');
    redirect('admin/courses.php');
}

if (is_post() && isset($_POST['action'])) {
    csrf_verify();
    
    if ($_POST['action'] === 'add_class') {
        $mid = (int) input('module_id');
        $title = trim(input('title'));
        if ($mid && $title) {
            q('INSERT INTO course_classes (module_id, title) VALUES (?, ?)', [$mid, $title]);
            recalc_course_progress($cid);
            flash('success', 'Clase agregada.');
        }
    }
    
    if ($_POST['action'] === 'toggle_complete') {
        $class_id = (int) input('class_id');
        $cls = q_one('SELECT * FROM course_classes WHERE id=?', [$class_id]);
        if ($cls) {
            $new_val = $cls['is_completed'] ? 0 : 1;
            $completed_at = $new_val ? date('Y-m-d H:i:s') : null;
            q('UPDATE course_classes SET is_completed=?, completed_at=? WHERE id=?', [$new_val, $completed_at, $class_id]);
            recalc_course_progress($cid);
            flash('success', $new_val ? 'Clase marcada como dictada. Los materiales de esta clase se han desbloqueado.' : 'Clase desmarcada.');
        }
    }
    
    if ($_POST['action'] === 'enable_attendance') {
        $class_id = (int) input('class_id');
        $minutes = (int) input('minutes') ?: 15;
        q('UPDATE course_classes SET attendance_expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id=?', [$minutes, $class_id]);
        flash('success', "Asistencia habilitada por $minutes minutos.");
    }
    
    if ($_POST['action'] === 'delete_class') {
        $class_id = (int) input('class_id');
        q('DELETE FROM course_classes WHERE id=?', [$class_id]);
        recalc_course_progress($cid);
        flash('success', 'Clase eliminada.');
    }
    
    redirect('admin/course_classes.php?course_id=' . $cid);
}

$modules = course_outline($cid);

$page_title = 'Puntos de Avance (Clases) - ' . $course['title'];
require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/header.php';
?>

<main class="section">
  <div class="container">
    <div class="block-title">
      <h2>Puntos de Avance: <?= e($course['title']) ?></h2>
      <div>
        <a href="<?= url('admin/materials.php?course_id=' . $cid) ?>" class="btn btn--ghost">Materiales</a>
        <a href="<?= url('admin/courses.php') ?>" class="btn btn--soft">Volver</a>
      </div>
    </div>
    
    <div class="card" style="margin-bottom:2rem;padding:1.5rem">
      <div class="cluster between" style="margin-bottom:1rem">
        <h3 class="mb-0">Avance Global del Curso</h3>
        <span class="chip chip--focus"><?= (int)$course['progress_pct'] ?>%</span>
      </div>
      <div class="progress"><span data-w="<?= (int)$course['progress_pct'] ?>"></span></div>
      <p class="muted" style="font-size:.85rem;margin-top:.75rem">Al marcar una clase como dictada, este porcentaje aumenta automáticamente para todos los alumnos.</p>
    </div>

    <?php foreach ($modules as $m): ?>
      <div style="margin-bottom:1.5rem">
        <div class="cluster between" style="border-bottom:1px solid var(--border);padding-bottom:1rem;margin-bottom:1rem">
          <h3 class="title-md mb-0">Fase: <?= e($m['title']) ?></h3>
        </div>
        
        <div class="table-wrap" style="margin-bottom:1.5rem">
          <table class="table">
            <thead>
              <tr>
                <th style="width:50px">Estado</th>
                <th>Título de la Clase</th>
                <th>Dictada el</th>
                <th style="text-align:right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($m['classes'] as $cls): ?>
                <tr>
                  <td style="text-align:center">
                    <?php if ($cls['is_completed']): ?>
                      <span style="color:var(--reward)"><?= icon('check') ?></span>
                    <?php else: ?>
                      <span style="color:var(--muted)"><?= icon('lock') ?></span>
                    <?php endif; ?>
                  </td>
                  <td style="<?= $cls['is_completed'] ? 'text-decoration:line-through;color:var(--muted)' : '' ?>">
                    <?= e($cls['title']) ?>
                  </td>
                  <td class="muted" style="font-size:.85rem">
                    <?= $cls['is_completed'] ? date_es($cls['completed_at']) : '-' ?>
                    <?php if ($cls['attendance_expires_at'] && strtotime($cls['attendance_expires_at']) > time()): ?>
                      <br><span style="color:var(--focus);font-size:0.75rem;font-weight:600">Asistencia ACTIVA</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:right">
                    <div class="actions" style="justify-content: flex-end">
                      <form method="post" action="" style="display:inline; margin:0" data-once>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle_complete">
                        <input type="hidden" name="class_id" value="<?= $cls['id'] ?>">
                        <?php if ($cls['is_completed']): ?>
                          <button type="submit" class="btn btn--sm btn--ghost">Revertir</button>
                        <?php else: ?>
                          <button type="submit" class="btn btn--sm btn--reward">Marcar Dictada</button>
                        <?php endif; ?>
                      </form>
                      <?php if (!$cls['is_completed']): ?>
                      <form method="post" action="" style="display:inline; margin:0" data-once>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="enable_attendance">
                        <input type="hidden" name="class_id" value="<?= $cls['id'] ?>">
                        <select name="minutes" class="select select--sm" style="width: auto; display: inline-block; padding: .1rem .2rem; height: auto; border:none; background:var(--bg)">
                          <option value="15">15m</option>
                          <option value="30">30m</option>
                          <option value="60">1h</option>
                        </select>
                        <button type="submit" class="btn btn--sm btn--soft">Asistencia</button>
                      </form>
                      <?php endif; ?>
                      <form method="post" action="" style="display:inline; margin:0" data-once onsubmit="return confirm('¿Eliminar clase?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_class">
                        <input type="hidden" name="class_id" value="<?= $cls['id'] ?>">
                        <button type="submit" class="btn btn--sm btn--ghost" style="color:var(--error)">Eliminar</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php if (empty($m['classes'])): ?>
              <tr><td colspan="4" class="text-center muted" style="padding:1.5rem">No has agregado clases a esta fase.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        
        <form method="post" action="" class="cluster" style="background:var(--bg);padding:1rem;border-radius:8px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_class">
          <input type="hidden" name="module_id" value="<?= $m['id'] ?>">
          <input type="text" name="title" class="input" placeholder="Nombre del punto de avance (ej. Clase 1: Intro)" required style="flex:1">
          <button type="submit" class="btn btn--soft">Añadir Clase</button>
        </form>
      </div>
    <?php endforeach; ?>

  </div>
</main>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
