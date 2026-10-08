<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if ($user['role'] !== 'admin') {
    flash('warn', 'Solo los administradores pueden editar usuarios.');
    redirect('dashboard.php');
}

$uid_edit = (int) (input('id') ?: 0);
$edit_user = $uid_edit ? q_one("SELECT * FROM users WHERE id=?", [$uid_edit]) : null;

if (!$edit_user) {
    flash('error', 'Usuario no encontrado.');
    redirect('admin/users.php');
}

if (is_post()) {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $id_document = trim($_POST['id_document'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $role = trim($_POST['role'] ?? '');
    
    if ($name && $email && in_array($role, ['student', 'instructor', 'admin'])) {
        $update_parts = ['name=?', 'email=?', 'role=?'];
        $params = [$name, $email, $role];
        
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

        $params[] = $uid_edit;
        
        $sql = 'UPDATE users SET ' . implode(', ', $update_parts) . ' WHERE id=?';
        try {
            q($sql, $params);
            flash('success', 'Usuario actualizado correctamente.');
            redirect('admin/users.php');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                flash('error', 'El correo ya está en uso por otro usuario.');
            } else {
                flash('error', 'Error de base de datos.');
            }
        }
    } else {
        flash('error', 'Faltan campos obligatorios.');
    }
}

$page_title = 'Editar Usuario';
require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/header.php';
?>

<main class="section">
  <div class="container container--narrow">
    <div class="block-title">
      <h2>Editar Usuario</h2>
      <a href="<?= url('admin/users.php') ?>" class="btn btn--soft">Volver</a>
    </div>
    
    <div class="card" style="padding:1.5rem">
      <form method="post" action="" class="form-grid">
        <?= csrf_field() ?>
        
        <div class="field span-all">
          <label for="name">Nombre Completo</label>
          <input type="text" id="name" name="name" class="input" value="<?= e($edit_user['name']) ?>" required>
        </div>
        
        <div class="field span-all">
          <label for="email">Correo Electrónico</label>
          <input type="email" id="email" name="email" class="input" value="<?= e($edit_user['email']) ?>" required>
        </div>
        
        <div class="field">
          <label for="role">Rol</label>
          <select id="role" name="role" class="select" required <?= $uid_edit === (int)$user['id'] ? 'disabled' : '' ?>>
            <option value="student" <?= $edit_user['role'] === 'student' ? 'selected' : '' ?>>Estudiante</option>
            <option value="instructor" <?= $edit_user['role'] === 'instructor' ? 'selected' : '' ?>>Instructor</option>
            <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
          </select>
          <?php if ($uid_edit === (int)$user['id']): ?>
            <input type="hidden" name="role" value="<?= e($edit_user['role']) ?>">
            <span class="muted" style="font-size:0.8rem">No puedes cambiar tu propio rol.</span>
          <?php endif; ?>
        </div>

        <div class="field">
          <label for="gender">Género</label>
          <select id="gender" name="gender" class="select">
            <option value="">-- Seleccionar --</option>
            <?php foreach (['Masculino', 'Femenino', 'Otro', 'Prefiero no decirlo'] as $g): ?>
              <option value="<?= $g ?>" <?= ($edit_user['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="id_document">Carnet de Identidad</label>
          <input type="text" id="id_document" name="id_document" class="input" value="<?= e($edit_user['id_document'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="birth_date">Fecha de Nacimiento</label>
          <input type="date" id="birth_date" name="birth_date" class="input" value="<?= e($edit_user['birth_date'] ?? '') ?>">
        </div>

        <div class="field span-all">
          <label for="phone">Teléfono / WhatsApp</label>
          <input type="tel" id="phone" name="phone" class="input" value="<?= e($edit_user['phone'] ?? '') ?>">
        </div>
        
        <div class="field span-all" style="margin-top:1.5rem">
          <button type="submit" class="btn btn--primary">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</main>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
