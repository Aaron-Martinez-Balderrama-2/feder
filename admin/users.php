<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if ($user['role'] !== 'admin') {
    flash('warn', 'Solo los administradores pueden gestionar usuarios.');
    redirect('dashboard.php');
}

if (is_post() && isset($_POST['action'])) {
    csrf_verify();
    
    if ($_POST['action'] === 'update_role') {
        $uid_to_update = (int) input('user_id');
        $new_role = input('role');
        
        if ($uid_to_update === (int) $user['id']) {
            flash('error', 'No puedes cambiar tu propio rol.');
        } elseif (in_array($new_role, ['student', 'instructor', 'admin'])) {
            q('UPDATE users SET role=? WHERE id=?', [$new_role, $uid_to_update]);
            flash('success', 'Rol de usuario actualizado.');
        }
    }
    
    if ($_POST['action'] === 'delete_user') {
        $uid_to_delete = (int) input('user_id');
        if ($uid_to_delete !== (int) $user['id']) {
            // Delete associated data first? Let's rely on ON DELETE CASCADE if it exists, or manually delete what we know.
            q('DELETE FROM enrollments WHERE user_id=?', [$uid_to_delete]);
            q('DELETE FROM courses WHERE instructor_id=?', [$uid_to_delete]);
            q('DELETE FROM users WHERE id=?', [$uid_to_delete]);
            flash('success', 'Usuario eliminado correctamente.');
        } else {
            flash('error', 'No puedes eliminarte a ti mismo.');
        }
    }
    redirect('admin/users.php');
}

$pag = paginate('SELECT id, name, email, phone, id_document, role, created_at FROM users ORDER BY created_at DESC', [], 10);
$users = $pag['data'];

$page_title = 'Gestión de Usuarios - ' . APP_NAME;
require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/header.php';
?>

<main class="section">
  <div class="container">
    <div class="block-title">
      <h2>Usuarios</h2>
      <a href="<?= url('admin/index.php') ?>" class="btn btn--soft">Volver al Panel</a>
    </div>
    
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Carnet</th>
            <th>Rol Actual</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= e($u['name']) ?></td>
              <td><?= e($u['email']) ?></td>
              <td><?= $u['phone'] ? e($u['phone']) : '<span class="muted">—</span>' ?></td>
              <td><?= $u['id_document'] ? e($u['id_document']) : '<span class="muted">—</span>' ?></td>
              <td>
                <?php if ($u['role'] === 'admin'): ?>
                  <span class="chip chip--reward">Admin</span>
                <?php elseif ($u['role'] === 'instructor'): ?>
                  <span class="chip chip--focus">Instructor</span>
                <?php else: ?>
                  <span class="chip">Estudiante</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                  <div class="actions">
                    <a href="<?= url('admin/user_edit.php?id=' . $u['id']) ?>" class="btn btn--sm btn--soft">Editar</a>
                    <form method="post" action="" style="display:inline; margin:0" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario? Esto no se puede deshacer.')">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete_user">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" class="btn btn--sm btn--ghost" style="color:var(--error)">Eliminar</button>
                    </form>
                  </div>
                <?php else: ?>
                  <span class="muted">Tú (No editable)</span>
                <?php endif; ?>
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
