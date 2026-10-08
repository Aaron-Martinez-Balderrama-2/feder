<?php
/** Navegación principal. Máx. 4 destinos visibles (Ley de Hick: menos opciones, decisión más rápida). */
$__u = current_user();
$__page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$__act = fn(string ...$p) => in_array($__page, $p, true) ? ' is-active' : '';
?>
<header class="site-header">
  <div class="container">
    <a class="brand" href="<?= url($__u ? (in_array($__u['role'], ['admin','instructor']) ? 'admin/index.php' : 'dashboard.php') : 'index.php') ?>" aria-label="<?= APP_NAME ?> inicio">
      <img src="<?= url('assets/img/favicon.png') ?>" alt="Logo" style="height: 32px; width: 32px; object-fit: contain;">
      <span style="font-weight: 700; font-family: var(--font-heading); font-size: 1.25rem;"><?= APP_NAME ?></span>
    </a>

    <button class="btn btn--ghost btn--icon nav-toggle" type="button" aria-controls="site-nav" aria-expanded="false" aria-label="Abrir menú"><?= icon('menu') ?></button>

    <nav class="nav" id="site-nav" aria-label="Principal">
      <?php if ($__u): ?>
        <?php if ($__u['role'] === 'student'): ?>
          <a class="<?= $__act('dashboard.php') ?>" href="<?= url('dashboard.php') ?>"><?= icon('home') ?>Mi panel</a>
        <?php endif; ?>
        <a class="<?= $__act('catalog.php', 'course.php') ?>" href="<?= url('catalog.php') ?>"><?= icon('compass') ?>Explorar</a>
        <?php if (in_array($__u['role'], ['admin', 'instructor'], true)): ?>
          <a href="<?= url('admin/index.php') ?>"><?= icon('chart') ?>Gestión</a>
        <?php endif; ?>
        <div class="nav-actions">
          <button class="btn btn--ghost btn--icon theme-toggle" type="button" data-theme-toggle aria-label="Cambiar tema"><?= icon('sun', 'i-sun') ?><?= icon('moon', 'i-moon') ?></button>
          <a class="user-pill" href="<?= url('profile.php') ?>"><?= avatar_html($__u, 'sm') ?><span><?= e(explode(' ', $__u['name'])[0]) ?></span></a>
          <a class="btn btn--ghost btn--icon" href="<?= url('logout.php') ?>" aria-label="Cerrar sesión" title="Cerrar sesión"><?= icon('logout') ?></a>
        </div>
      <?php else: ?>
        <a class="<?= $__act('catalog.php', 'course.php') ?>" href="<?= url('catalog.php') ?>"><?= icon('compass') ?>Cursos</a>
        <div class="nav-actions">
          <button class="btn btn--ghost btn--icon theme-toggle" type="button" data-theme-toggle aria-label="Cambiar tema"><?= icon('sun', 'i-sun') ?><?= icon('moon', 'i-moon') ?></button>
          <a class="btn btn--line btn--sm" href="<?= url('login.php') ?>">Entrar</a>
          <a class="btn btn--primary btn--sm" href="<?= url('register.php') ?>">Empieza gratis</a>
        </div>
      <?php endif; ?>
    </nav>
  </div>
</header>
