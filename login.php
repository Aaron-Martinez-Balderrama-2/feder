<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged()) {
    redirect('dashboard.php');
}
$email = '';
$error = '';
if (is_post()) {
    csrf_verify();
    $email = mb_strtolower((string) input('email'));
    $pass = (string) ($_POST['password'] ?? '');
    if (login_throttled()) {
        $error = 'Demasiados intentos. Respira, toma un vaso de agua y prueba en unos minutos.';
    } else {
        $u = q_one('SELECT id,password_hash,role,name,is_active FROM users WHERE email=?', [$email]);
        if ($u && (int) $u['is_active'] === 1 && password_verify($pass, $u['password_hash'])) {
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
            }
            unset($_SESSION['_login_attempts']);
            login_user((int) $u['id']);
            flash('success', greeting() . ', ' . explode(' ', $u['name'])[0] . '. Sigamos donde lo dejaste.');
            $intended = $_SESSION['_intended'] ?? '';
            unset($_SESSION['_intended']);
            if ($intended && str_starts_with($intended, BASE_URL . '/') && !str_contains($intended, '/api/')) {
                redirect(substr($intended, strlen(BASE_URL) + 1));
            }
            redirect(in_array($u['role'], ['admin', 'instructor'], true) ? 'admin/index.php' : 'dashboard.php');
        }
        login_failed();
        $error = 'El correo o la contraseña no coinciden. Revisa e inténtalo de nuevo.';
    }
}

$title = 'Entrar';
$noindex = true;
require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/header.php';
?>
<main id="main" class="auth">
  <section class="auth-side" aria-hidden="true">
    <span class="eyebrow" style="color:#FFD9A8">Bienvenido de vuelta</span>
    <h2 style="margin-top:1rem">Avanza junto a tu cohorte.</h2>
    <p>La educación en solitario es difícil. Entra para continuar el programa junto a tu instructor y acceder a los nuevos materiales desbloqueados tras tu última clase.</p>
    <div class="quote">
      <p style="margin:0;color:#fff;font-family:var(--font-display);font-style:italic;font-size:1.2rem">“El aprendizaje más efectivo ocurre cuando hay estructura, acompañamiento y un camino claro.”</p>
      <small style="color:#A5A1C8">— Equipo Feder</small>
    </div>
  </section>

  <section class="auth-main">
    <div class="auth-card">
      <h1>Entrar</h1>
      <p class="muted">¿Nuevo aquí? <a href="<?= url('register.php') ?>">Crea tu cuenta en 30 segundos</a>.</p>

      <?php if ($error): ?><div class="notice notice--soft" role="alert" style="margin:1rem 0"><?= icon('sparkles') ?><span><?= e($error) ?></span></div><?php endif; ?>

      <form method="post" class="stack" style="--gap:1rem;margin-top:1.5rem" novalidate data-once>
        <?= csrf_field() ?>
        <div class="field">
          <label for="email">Correo</label>
          <div class="input-icon"><?= icon('mail') ?><input class="input" type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus></div>
        </div>
        <div class="field">
          <label for="password">Contraseña</label>
          <div class="input-icon" style="position:relative">
            <?= icon('key') ?>
            <input class="input" type="password" id="password" name="password" autocomplete="current-password" required style="padding-right:3.2rem">
            <button type="button" class="btn btn--ghost btn--icon" data-reveal-pass="password" aria-label="Mostrar contraseña" style="position:absolute;right:.2rem;top:50%;transform:translateY(-50%)"><?= icon('eye') ?></button>
          </div>
        </div>
        <button class="btn btn--primary btn--lg btn--block" type="submit">Entrar <?= icon('arrow', 'ico-arrow') ?></button>
      </form>

      <div class="demo-accounts">
        <strong>Cuentas de demostración</strong> <span class="muted">(clic para rellenar)</span>
        <button type="button" data-demo="estudiante@feder.com|Demo123!">Estudiante · estudiante@feder.com</button>
        <button type="button" data-demo="instructor@feder.com|Demo123!">Instructora · instructor@feder.com</button>
        <button type="button" data-demo="admin@feder.com|Admin123!">Administración · admin@feder.com</button>
      </div>
    </div>
  </section>
</main>
<?php $hideFooter = true; require __DIR__ . '/includes/layout/footer.php'; ?>
