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
    flash('warn', 'No puedes editar materiales de este curso.');
    redirect('admin/courses.php');
}

if (is_post() && isset($_POST['action']) && $_POST['action'] === 'add') {
    csrf_verify();
    $title = trim(input('title'));
    $class_id = input('class_id') ? (int) input('class_id') : null;
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        
        $allowed_exts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'mp3', 'mp4', 'png', 'jpg', 'jpeg', 'txt', 'csv'];
        if (!in_array($ext, $allowed_exts, true)) {
            flash('error', 'Tipo de archivo no permitido. Extensiones válidas: ' . implode(', ', $allowed_exts));
            redirect('admin/materials.php?course_id=' . $cid);
            exit;
        }

        $filename = 'mat_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest = __DIR__ . '/../uploads/' . $filename;
        
        if (!is_dir(__DIR__ . '/../uploads')) {
            mkdir(__DIR__ . '/../uploads', 0777, true);
        }
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            q('INSERT INTO course_materials (course_id, class_id, title, file_path) VALUES (?, ?, ?, ?)', [$cid, $class_id, $title, 'uploads/' . $filename]);
            flash('success', 'Material subido correctamente.');
        } else {
            flash('error', 'Error al guardar el archivo.');
        }
    } else {
        flash('error', 'Por favor selecciona un archivo.');
    }
    redirect('admin/materials.php?course_id=' . $cid);
}

if (is_post() && isset($_POST['action']) && $_POST['action'] === 'delete') {
    csrf_verify();
    $mid = (int) input('material_id');
    $mat = q_one('SELECT * FROM course_materials WHERE id=? AND course_id=?', [$mid, $cid]);
    if ($mat) {
        @unlink(__DIR__ . '/../' . $mat['file_path']);
        q('DELETE FROM course_materials WHERE id=?', [$mid]);
        flash('success', 'Material eliminado.');
    }
    redirect('admin/materials.php?course_id=' . $cid);
}

$pag = paginate('SELECT m.*, c.title as class_name FROM course_materials m LEFT JOIN course_classes c ON c.id=m.class_id WHERE m.course_id=? ORDER BY m.id DESC', [$cid], 10);
$materials = $pag['data'];
$classes = q_all('SELECT c.id, c.title, m.title as module_name FROM course_classes c JOIN modules m ON m.id=c.module_id WHERE m.course_id=? ORDER BY m.position, c.id', [$cid]);

$page_title = 'Materiales - ' . $course['title'];
require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/header.php';
?>

<main class="section">
  <div class="container">
    <div class="block-title">
      <h2>Materiales: <?= e($course['title']) ?></h2>
      <a href="<?= url('admin/courses.php') ?>" class="btn btn--soft">Volver</a>
    </div>
    
    <div class="card" style="margin-bottom:2rem">
      <h3 style="margin-bottom:1rem">Subir Nuevo Material</h3>
      <form method="post" action="" enctype="multipart/form-data" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        
        <div class="cluster">
          <div class="field" style="flex:1;margin:0">
            <input type="text" name="title" class="input" placeholder="Nombre del archivo (ej. PDF Módulo 1)" required>
          </div>
          <div class="field" style="margin:0">
            <input type="file" name="file" class="input" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.mp3,.mp4,.png,.jpg,.jpeg,.txt,.csv" required>
          </div>
        </div>
        
        <div class="cluster">
          <div class="field" style="flex:1;margin:0">
            <select name="class_id" class="select">
              <option value="">-- Material General (Desbloqueado siempre) --</option>
              <?php foreach ($classes as $cls): ?>
                <option value="<?= $cls['id'] ?>"><?= e($cls['module_name'] . ' > ' . $cls['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn--primary">Subir Material</button>
        </div>
      </form>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Título</th>
            <th>Vincular a Clase</th>
            <th>Archivo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($materials as $m): ?>
            <tr>
              <td><?= e($m['title']) ?></td>
              <td>
                <?php if ($m['class_id']): ?>
                  <span class="chip chip--focus"><?= e($m['class_name']) ?></span>
                <?php else: ?>
                  <span class="muted">General</span>
                <?php endif; ?>
              </td>
              <td><a href="<?= url($m['file_path']) ?>" target="_blank" class="chip chip--growth">Ver</a></td>
              <td>
                <div class="actions">
                  <form method="post" action="" style="display:inline; margin:0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="material_id" value="<?= $m['id'] ?>">
                    <button type="submit" class="btn btn--sm btn--ghost" style="color:var(--error)" onclick="return confirm('¿Seguro que deseas eliminar este material?')">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$materials): ?>
            <tr><td colspan="4" class="text-center muted">No hay materiales subidos.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?= $pag['html'] ?>
  </div>
</main>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
