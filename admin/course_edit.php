<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if (!in_array($user['role'], ['admin', 'instructor'], true)) {
    redirect('dashboard.php');
}

$cid = (int) (input('id') ?: 0);
$course = $cid ? q_one("SELECT * FROM courses WHERE id=?", [$cid]) : null;

if ($course && $user['role'] !== 'admin' && (int) $course['instructor_id'] !== (int) $user['id']) {
    flash('warn', 'No puedes editar un curso que no te pertenece.');
    redirect('admin/courses.php');
}

if (is_post()) {
    csrf_verify();
    $title = trim(input('title'));
    $hook = trim(input('hook'));
    $desc = trim(input('description'));
    $outcome = trim(input('outcome'));
    $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($title));
    $capacity = (int) input('capacity');
    $category_id = (int) input('category_id');
    $start_date = input('start_date');
    $end_date = input('end_date');
    $status = input('status');
    $meet_url = trim(input('meet_url'));
    $whatsapp_url = trim(input('whatsapp_url'));
    $base64_image = $_POST['cover_image_base64'] ?? '';
    $remove_cover = !empty($_POST['remove_cover']);
    
    $cover_path = $course['cover_image'] ?? null;

    if ($remove_cover) {
        if ($cover_path && is_file(__DIR__ . '/../' . $cover_path)) {
            @unlink(__DIR__ . '/../' . $cover_path);
        }
        $cover_path = null;
    } elseif ($base64_image && preg_match('/^data:image\/(\w+);base64,/', $base64_image, $type)) {
        $data = substr($base64_image, strpos($base64_image, ',') + 1);
        $data = base64_decode($data);
        if ($data !== false) {
            $filename = 'cover_' . time() . '_' . rand(1000, 9999) . '.jpg';
            $dest = __DIR__ . '/../uploads/' . $filename;
            if (!is_dir(__DIR__ . '/../uploads')) mkdir(__DIR__ . '/../uploads', 0777, true);
            if (file_put_contents($dest, $data)) {
                if ($cover_path && is_file(__DIR__ . '/../' . $cover_path)) {
                    @unlink(__DIR__ . '/../' . $cover_path);
                }
                $cover_path = 'uploads/' . $filename;
            }
        }
    }

    if ($cid) {
        q('UPDATE courses SET title=?, hook=?, description=?, outcome=?, capacity=?, category_id=?, start_date=?, end_date=?, status=?, meet_url=?, whatsapp_url=?, slug=?, cover_image=? WHERE id=?',
          [$title, $hook, $desc, $outcome, $capacity, $category_id, $start_date, $end_date, $status, $meet_url, $whatsapp_url, $slug, $cover_path, $cid]);
        flash('success', 'Curso actualizado.');
        redirect('admin/course_edit.php?id=' . $cid);
    } else {
        q('INSERT INTO courses (category_id, instructor_id, title, slug, hook, description, outcome, capacity, start_date, end_date, status, meet_url, whatsapp_url, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
          [$category_id, $user['id'], $title, $slug, $hook, $desc, $outcome, $capacity, $start_date, $end_date, $status, $meet_url, $whatsapp_url, $cover_path]);
        
        $new_id = (int) db()->lastInsertId();
        
        $fases = ['Modelo de Negocio', 'Finanzas para la empresa', 'Marketing Digital', 'Legal'];
        foreach ($fases as $pos => $fase) {
            q('INSERT INTO modules (course_id, title, position) VALUES (?, ?, ?)', [$new_id, $fase, $pos + 1]);
        }
        
        flash('success', 'Curso creado con las 4 fases obligatorias.');
        redirect('admin/courses.php');
    }
}

$categories = q_all("SELECT * FROM categories ORDER BY name");

$page_title = $cid ? 'Editar Curso' : 'Nuevo Curso';
require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<main class="section">
  <div class="container container--narrow">
    <div class="block-title">
      <h2><?= $cid ? 'Editar Curso' : 'Nuevo Curso' ?></h2>
      <a href="<?= url('admin/courses.php') ?>" class="btn btn--soft">Volver</a>
    </div>
    
    <div class="card">
      <form method="post" action="" class="form-grid" id="course-form">
        <?= csrf_field() ?>
        
        <div class="field span-all" style="background:var(--bg); padding:1rem; border-radius:8px">
          <label>Portada del Curso (16:9)</label>
          <input type="file" id="cover_input" accept="image/*" class="input">
          <input type="hidden" name="cover_image_base64" id="cover_image_base64">
          
          <div style="margin-top:1.5rem; display:none; background:var(--surface); padding:1.5rem; border-radius:8px; border:1px solid var(--border); text-align:center" id="cropper-container">
            <p class="muted" style="margin-bottom:1rem; font-size:.85rem; font-weight:600">Ajusta la portada (Se guardará en 16:9)</p>
            <div style="max-width:600px; margin:0 auto; box-shadow:var(--shadow-md); border-radius:4px; overflow:hidden">
              <img id="cropper-img" src="" style="max-width:100%; display:block;">
            </div>
            <div style="margin-top:1.5rem;">
              <button type="button" class="btn btn--sm btn--soft" id="cancel-crop">Cancelar y Quitar Selección</button>
            </div>
          </div>
          <?php if ($course && $course['cover_image']): ?>
          <div id="current-cover" style="margin-top:1rem; width:200px; border-radius:8px; overflow:hidden">
            <p class="muted" style="font-size:0.8rem; margin-bottom:0.5rem">Portada Actual:</p>
            <img src="<?= url($course['cover_image']) ?>" style="width:100%; aspect-ratio:16/9; object-fit:cover;">
            <div style="margin-top: .5rem; text-align: left;">
              <label style="font-size: .85rem; display: flex; align-items: center; gap: .25rem; cursor: pointer; color: var(--error)">
                <input type="checkbox" name="remove_cover" value="1"> Eliminar portada
              </label>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <div class="field span-all">
          <label>Categoría</label>
          <select name="category_id" class="select" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($course['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field span-all">
          <label>Título</label>
          <input type="text" name="title" class="input" required value="<?= e($course['title'] ?? '') ?>">
        </div>
        <div class="field span-all">
          <label>Gancho (Hook)</label>
          <input type="text" name="hook" class="input" required value="<?= e($course['hook'] ?? '') ?>">
        </div>
        <div class="field span-all">
          <label>Descripción</label>
          <textarea name="description" class="textarea" required><?= e($course['description'] ?? '') ?></textarea>
        </div>
        <div class="field span-all">
          <label>Resultado / Objetivo (Outcome)</label>
          <input type="text" name="outcome" class="input" required value="<?= e($course['outcome'] ?? '') ?>">
        </div>
        <div class="field span-all">
          <label>Enlace de Clase Virtual (Meet/Zoom)</label>
          <input type="url" name="meet_url" class="input" value="<?= e($course['meet_url'] ?? '') ?>">
        </div>
        <div class="field span-all">
          <label>Enlace del Grupo de WhatsApp</label>
          <input type="url" name="whatsapp_url" class="input" value="<?= e($course['whatsapp_url'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Capacidad</label>
          <input type="number" name="capacity" class="input" required value="<?= $course['capacity'] ?? 30 ?>">
        </div>
        <div class="field">
          <label>Fecha de Inicio</label>
          <input type="date" name="start_date" class="input" required value="<?= $course['start_date'] ?? date('Y-m-d') ?>">
        </div>
        <div class="field">
          <label>Fecha de Cierre</label>
          <input type="date" name="end_date" class="input" required value="<?= $course['end_date'] ?? date('Y-m-d', strtotime('+30 days')) ?>">
        </div>
        <div class="field">
          <label>Estado</label>
          <select name="status" class="select">
            <option value="draft" <?= ($course['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Borrador</option>
            <option value="published" <?= ($course['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option>
          </select>
        </div>
        
        <div class="field span-all" style="margin-top:1.5rem">
          <button type="submit" class="btn btn--primary">Guardar Curso</button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('cover_input');
    const image = document.getElementById('cropper-img');
    const container = document.getElementById('cropper-container');
    const base64Input = document.getElementById('cover_image_base64');
    const currentCover = document.getElementById('current-cover');
    const form = document.getElementById('course-form');
    const cancelBtn = document.getElementById('cancel-crop');
    let cropper = null;

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = (event) => {
            image.src = event.target.result;
            container.style.display = 'block';
            if (currentCover) currentCover.style.display = 'none';

            if (cropper) cropper.destroy();
            cropper = new Cropper(image, {
                aspectRatio: 16 / 9,
                viewMode: 1,
                autoCropArea: 1,
            });
        };
        reader.readAsDataURL(file);
    });

    cancelBtn.addEventListener('click', () => {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        fileInput.value = '';
        base64Input.value = '';
        container.style.display = 'none';
        if (currentCover) currentCover.style.display = 'block';
    });

    form.addEventListener('submit', (e) => {
        if (cropper) {
            e.preventDefault();
            const canvas = cropper.getCroppedCanvas({ width: 800, height: 450 });
            base64Input.value = canvas.toDataURL('image/jpeg', 0.85);
            cropper.destroy();
            form.submit();
        }
    });
});
</script>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
