<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

if ($user['role'] !== 'student') {
    redirect('admin/index.php');
}
$uid = (int) $user['id'];
$enrollments = user_enrollments($uid);

$title = 'Mi panel';
$noindex = true;
require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/header.php';
?>
<main id="main" class="container dash">
  <div class="block-title" style="margin-bottom:2rem">
    <h2><?= greeting() ?>, <?= e(explode(' ', $user['name'])[0]) ?></h2>
    <p class="muted">Aquí están tus cursos guiados.</p>
  </div>

  <section aria-labelledby="mc">
    <div class="block-title">
      <h3 id="mc">Tus Cursos Inscritos</h3>
      <a class="btn btn--ghost btn--sm" href="<?= url('catalog.php') ?>"><?= icon('compass') ?> Explorar Catálogo</a>
    </div>
    
    <?php if (!$enrollments): ?>
      <div class="card text-center" style="padding:4rem 1.5rem">
        <div style="color:var(--muted);margin-bottom:1rem"><?= icon('book', '', 48) ?></div>
        <h4 class="title-md">Aún no tienes cursos</h4>
        <p class="muted">Inscríbete en un curso para empezar tus clases.</p>
        <a class="btn btn--line" href="<?= url('catalog.php') ?>" style="margin-top:1rem">Ver catálogo</a>
      </div>
    <?php else: ?>
      <div class="stack" style="--gap:1rem">
        <?php foreach ($enrollments as $en): $c = $en; ?>
          <a class="card card--hover" href="<?= url('course.php?slug=' . e($c['slug'])) ?>" style="padding:1.5rem;display:flex;gap:1.5rem;align-items:center;text-decoration:none">
            <div class="ring" data-p="<?= (int) $c['global_progress'] ?>" style="--size:64px;flex-shrink:0">
              <div><strong><?= (int) $c['global_progress'] ?>%</strong></div>
            </div>
            <div style="flex:1;min-width:0">
              <h4 class="title-md mb-0" style="color:var(--text)"><?= e($c['title']) ?></h4>
              <p class="muted mb-0" style="font-size:.9rem;margin-top:.25rem"><?= e($c['hook']) ?></p>
            </div>
            <div>
              <span class="btn btn--soft">Ir al curso <?= icon('arrow', 'ico-arrow') ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>
