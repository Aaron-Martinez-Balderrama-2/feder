<?php
require __DIR__ . '/includes/bootstrap.php';

$q = trim(input('q'));
$cid = (int) input('category');
$status = trim(input('status'));

$sql = "SELECT c.*, cat.name as cat_name, v.taken, v.available 
        FROM courses c 
        LEFT JOIN categories cat ON c.category_id=cat.id 
        LEFT JOIN v_course_seats v ON v.course_id=c.id 
        WHERE c.status='published'";
$params = [];

if ($q) {
    $sql .= " AND c.title LIKE ?";
    $params[] = "%$q%";
}
if ($cid) {
    $sql .= " AND c.category_id=?";
    $params[] = $cid;
}
if ($status === 'open') {
    $sql .= " AND v.available > 0 AND UNIX_TIMESTAMP() <= (UNIX_TIMESTAMP(c.start_date) + (UNIX_TIMESTAMP(c.end_date) - UNIX_TIMESTAMP(c.start_date)) / 2)";
}

$sql .= " ORDER BY c.id DESC";
$pag = paginate($sql, $params, 6);
$courses = $pag['data'];
$categories = q_all("SELECT * FROM categories ORDER BY name");

$page_title = 'Catálogo de Cursos';
require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/header.php';
?>

<main class="section">
  <div class="container">
    <div class="block-title">
      <h2>Explora el catálogo</h2>
      <p class="muted">Cursos guiados por instructores con progreso estructurado.</p>
    </div>

    <form method="get" action="" class="cluster" style="margin-bottom:2rem;background:var(--paper);padding:1rem;border-radius:12px;border:1px solid var(--border)">
      <div class="field" style="flex:1;margin:0">
        <input type="text" name="q" class="input" placeholder="Buscar cursos..." value="<?= e($q) ?>">
      </div>
      <div class="field" style="margin:0">
        <select name="category" class="select" onchange="this.form.submit()">
          <option value="">Todas las categorías</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $cid === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="margin:0">
        <select name="status" class="select" onchange="this.form.submit()">
          <option value="">Cualquier disponibilidad</option>
          <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Inscripciones abiertas</option>
        </select>
      </div>
      <button type="submit" class="btn btn--primary"><?= icon('search') ?> Buscar</button>
    </form>

    <?php if ($courses): ?>
      <div class="grid-auto" style="--min:300px">
        <?php foreach ($courses as $c) { include __DIR__ . '/includes/partials/course_card.php'; } ?>
      </div>
      <?= $pag['html'] ?>
    <?php else: ?>
      <div class="card text-center" style="padding:4rem 1rem">
        <div style="color:var(--muted);margin-bottom:1rem"><?= icon('search', '', 48) ?></div>
        <h3 class="title-md">No encontramos cursos</h3>
        <p class="muted">Intenta con otros términos o elimina los filtros.</p>
        <a href="<?= url('catalog.php') ?>" class="btn btn--line" style="margin-top:1rem">Ver todo el catálogo</a>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>
