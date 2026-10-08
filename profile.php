<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

// Refresh user data to get gender and profile_picture
$user = q_one('SELECT * FROM users WHERE id=?', [$uid]);

if (is_post()) {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $id_document = trim($_POST['id_document'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    
    if ($name) {
        $update_parts = ['name=?'];
        $params = [$name];
        
        if (in_array($gender, ['Masculino', 'Femenino', 'Otro', 'Prefiero no decirlo'])) {
            $update_parts[] = 'gender=?';
            $params[] = $gender;
        }

        $update_parts[] = 'id_document=?';
        $params[] = $id_document ?: null;

        $update_parts[] = 'phone=?';
        $params[] = $phone ?: null;

        $update_parts[] = 'birth_date=?';
        $params[] = $birth_date ?: null;

        // Handle profile picture removal or upload
        $remove_picture = !empty($_POST['remove_picture']);
        $base64_image = $_POST['profile_picture_base64'] ?? '';
        
        if ($remove_picture) {
            if ($user['profile_picture'] && is_file(__DIR__ . '/' . $user['profile_picture'])) {
                @unlink(__DIR__ . '/' . $user['profile_picture']);
            }
            $update_parts[] = 'profile_picture=?';
            $params[] = null;
        } elseif ($base64_image && preg_match('/^data:image\/(\w+);base64,/', $base64_image, $type)) {
            $data = substr($base64_image, strpos($base64_image, ',') + 1);
            $data = base64_decode($data);
            if ($data !== false) {
                $filename = 'user_' . $uid . '_' . time() . '.jpg';
                $dest = __DIR__ . '/uploads/' . $filename;
                
                if (!is_dir(__DIR__ . '/uploads')) {
                    mkdir(__DIR__ . '/uploads', 0777, true);
                }
                
                if (file_put_contents($dest, $data)) {
                    // Delete old picture if exists
                    if ($user['profile_picture'] && is_file(__DIR__ . '/' . $user['profile_picture'])) {
                        @unlink(__DIR__ . '/' . $user['profile_picture']);
                    }
                    $update_parts[] = 'profile_picture=?';
                    $params[] = 'uploads/' . $filename;
                }
            }
        }

        $params[] = $uid;
        $sql = 'UPDATE users SET ' . implode(', ', $update_parts) . ' WHERE id=?';
        q($sql, $params);

        flash('success', 'Perfil actualizado correctamente.');
        redirect('profile.php');
    }
}

$page_title = 'Mi Perfil';
require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<main class="section">
  <div class="container container--narrow">
    <div class="card" style="padding:1.5rem">
      <div class="block-title">
        <h2>Mi Perfil</h2>
      </div>
      <form method="post" action="<?= url('profile.php') ?>" class="stack" id="profile-form">
        <?= csrf_field() ?>
        
        <div class="cluster" style="margin-bottom:1.5rem; gap:1.5rem; align-items:flex-start">
          <div id="current-avatar">
            <?php if ($user['profile_picture']): ?>
              <img src="<?= url($user['profile_picture']) ?>" alt="Avatar" style="width:80px;height:80px;border-radius:50%;object-fit:cover;box-shadow:var(--shadow-sm)">
              <div style="margin-top: .5rem; text-align: center;">
                <label style="font-size: .8rem; display: flex; align-items: center; justify-content: center; gap: .25rem; cursor: pointer; color: var(--error)">
                  <input type="checkbox" name="remove_picture" value="1"> Quitar
                </label>
              </div>
            <?php else: ?>
              <?= avatar_html($user, 'lg') ?>
            <?php endif; ?>
          </div>
          <div class="field" style="flex:1; margin:0;">
            <label>Foto de Perfil</label>
            <input type="file" id="profile_input" accept="image/*" class="input">
            <input type="hidden" name="profile_picture_base64" id="profile_picture_base64">
            <span class="muted" style="font-size:0.8rem;display:block;margin-top:.5rem">Selecciona una imagen y ajústala para que sea tu avatar.</span>
            
            <div style="margin-top:1.5rem; display:none; background:var(--surface); padding:1.5rem; border-radius:8px; border:1px solid var(--border); text-align:center" id="cropper-container">
              <p class="muted" style="margin-bottom:1rem; font-size:.85rem; font-weight:600">Ajusta tu foto (Se guardará como cuadrado)</p>
              <div style="max-width:300px; margin:0 auto; box-shadow:var(--shadow-md); border-radius:4px; overflow:hidden">
                <img id="cropper-img" src="" style="max-width:100%; display:block;">
              </div>
              <div style="margin-top:1.5rem;">
                <button type="button" class="btn btn--sm btn--soft" id="cancel-crop">Cancelar y Quitar Selección</button>
              </div>
            </div>
          </div>
        </div>

        <div class="field">
          <label for="name">Nombre</label>
          <input type="text" id="name" name="name" class="input" value="<?= e($user['name']) ?>" required>
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="field" style="margin:0">
            <label for="id_document">Carnet de Identidad</label>
            <input type="text" id="id_document" name="id_document" class="input" value="<?= e($user['id_document'] ?? '') ?>">
          </div>
          <div class="field" style="margin:0">
            <label for="birth_date">Fecha de Nacimiento</label>
            <input type="date" id="birth_date" name="birth_date" class="input" value="<?= e($user['birth_date'] ?? '') ?>">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="field" style="margin:0">
            <label for="phone">Teléfono / WhatsApp</label>
            <input type="tel" id="phone" name="phone" class="input" value="<?= e($user['phone'] ?? '') ?>">
          </div>
          <div class="field" style="margin:0">
            <label for="gender">Género</label>
            <select id="gender" name="gender" class="select">
              <option value="">-- Seleccionar --</option>
              <?php foreach (['Masculino', 'Femenino', 'Otro', 'Prefiero no decirlo'] as $g): ?>
                <option value="<?= $g ?>" <?= ($user['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="email">Correo (No modificable)</label>
          <input type="email" id="email" class="input" value="<?= e($user['email']) ?>" readonly disabled>
        </div>
        
        <div style="margin-top:1.5rem;">
          <button type="submit" class="btn btn--primary">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('profile_input');
    const image = document.getElementById('cropper-img');
    const container = document.getElementById('cropper-container');
    const base64Input = document.getElementById('profile_picture_base64');
    const currentAvatar = document.getElementById('current-avatar');
    const form = document.getElementById('profile-form');
    const cancelBtn = document.getElementById('cancel-crop');
    let cropper = null;

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = (event) => {
            image.src = event.target.result;
            container.style.display = 'block';
            if (currentAvatar) currentAvatar.style.display = 'none';

            if (cropper) cropper.destroy();
            cropper = new Cropper(image, {
                aspectRatio: 1,
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
        if (currentAvatar) currentAvatar.style.display = 'block';
    });

    form.addEventListener('submit', (e) => {
        if (cropper) {
            e.preventDefault();
            const canvas = cropper.getCroppedCanvas({ width: 400, height: 400 });
            base64Input.value = canvas.toDataURL('image/jpeg', 0.85);
            cropper.destroy();
            form.submit();
        }
    });
});
</script>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>
