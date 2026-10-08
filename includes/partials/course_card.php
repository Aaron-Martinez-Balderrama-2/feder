<?php
/** Tarjeta de curso. Requiere $c (fila de published_courses). */
$__cap = max(1, (int) $c['capacity']);
$__taken = (int) ($c['taken'] ?? 0);
$__avail = max(0, $__cap - $__taken);
$__pct = (int) round($__taken / $__cap * 100);
$__state = $__avail === 0 ? 'is-full' : ($__avail <= max(3, (int) ceil($__cap * 0.25)) ? 'is-low' : '');
$__classes = (int) ($c['class_count'] ?? 0);
?>
<a class="course-card reveal" href="<?= url('course.php?slug=' . urlencode($c['slug'])) ?>" style="--c: <?= e(safe_color($c['color'])) ?>; --d: <?= number_format(($i ?? 0) * 0.07, 2) ?>s">
  <?php $cover_style = $c['cover_image'] ? 'background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.8)), url(' . url($c['cover_image']) . ') center/cover;' : ''; ?>
  <div class="course-cover" style="<?= $cover_style ?>">
    <div class="cluster between" style="flex-wrap:nowrap;">
      <span class="cover-ico" style="flex-shrink:0"><?= icon($c['category_icon'] ?? 'spark') ?></span>
      <span class="chip" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%"><?= e($c['category'] ?? 'General') ?></span>
    </div>
    <div class="course-chunks" aria-label="<?= $__classes ?> clases">
      <?php for ($k = 0; $k < min(7, max(1, $__classes)); $k++): ?><i></i><?php endfor; ?>
    </div>
  </div>
  <div class="course-body">
    <h3><?= e($c['title']) ?></h3>
    <p class="course-hook"><?= e($c['hook']) ?></p>
    <div class="course-meta">
      <span><?= icon('layers') ?><?= $__classes ?> clases</span>
      <span><?= icon('calendar') ?><?= date_es($c['start_date']) ?></span>
      <span><?= icon('bolt') ?><?= e(ucfirst($c['level'])) ?></span>
    </div>
    <div class="course-foot">
      <div class="seats <?= $__state ?>">
        <div class="seats-label">
          <span>Cupos</span>
          <strong><?= $__avail === 0 ? 'Agotado' : ($__state === 'is-low' ? '¡Quedan ' . $__avail . '!' : $__avail . ' libres') ?></strong>
        </div>
        <div class="seats-bar"><span data-w="<?= $__pct ?>"></span></div>
      </div>
    </div>
  </div>
</a>
