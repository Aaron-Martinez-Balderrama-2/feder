<?php
require __DIR__ . '/includes/bootstrap.php';

$slug = trim(input('slug'));
if (!$slug) redirect('catalog.php');

$course = q_one('SELECT c.*, u.name as instructor_name, cat.name as category_name 
                 FROM courses c 
                 LEFT JOIN users u ON u.id=c.instructor_id 
                 LEFT JOIN categories cat ON cat.id=c.category_id 
                 WHERE c.slug=?', [$slug]);
                 
if (!$course) {
    flash('error', 'Curso no encontrado.');
    redirect('catalog.php');
}

$cid = (int) $course['id'];
$outline = course_outline($cid);
$seats = course_seats($cid);
$user = current_user();

$enrolled = $user ? is_enrolled((int) $user['id'], $cid) : false;

$user_attendances = [];
if ($enrolled) {
    $att = q_all('SELECT class_id FROM course_attendance ca JOIN course_classes c ON c.id=ca.class_id JOIN modules m ON m.id=c.module_id WHERE m.course_id=? AND ca.user_id=?', [$cid, $user['id']]);
    foreach ($att as $a) $user_attendances[$a['class_id']] = true;
    
    if (is_post() && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
        csrf_verify();
        $class_id = (int) input('class_id');
        $cls_status = q_one('SELECT attendance_expires_at FROM course_classes WHERE id=?', [$class_id]);
        if ($cls_status && $cls_status['attendance_expires_at'] && strtotime($cls_status['attendance_expires_at']) > time()) {
            q('INSERT IGNORE INTO course_attendance (user_id, class_id) VALUES (?, ?)', [$user['id'], $class_id]);
            flash('success', 'Asistencia registrada exitosamente.');
        } else {
            flash('error', 'El tiempo para registrar asistencia ha expirado o no está habilitado.');
        }
        redirect('course.php?slug=' . urlencode($slug));
    }
}
$start = strtotime($course['start_date']);
$end = strtotime($course['end_date']);
$halfway = $start + (($end - $start) / 2);
$closed = time() > $halfway;
$color = safe_color($course['color']);
$isLow = $seats['available'] > 0 && $seats['available'] <= max(3, (int) ceil($seats['capacity'] * .25));
$progress = (int) $course['progress_pct'];

$materials = q_all('SELECT * FROM course_materials WHERE course_id=?', [$cid]);
$materialsByClass = [];
foreach ($materials as $m) {
    if ($m['class_id']) {
        $materialsByClass[$m['class_id']][] = $m;
    } else {
        $materialsByClass['general'][] = $m;
    }
}

$page_title = $course['title'];
$page_desc = $course['hook'];
require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/header.php';
?>

<div class="course-header" style="--c:<?= $color ?>">
  <div class="container container--narrow text-center">
    <?php if ($course['category_name']): ?>
      <span class="eyebrow" style="color:var(--text-inv);opacity:.8"><?= e($course['category_name']) ?></span>
    <?php endif; ?>
    <h1><?= e($course['title']) ?></h1>
    <p class="lead" style="margin:1rem auto 0;color:var(--text-inv);opacity:.9;max-width:54ch"><?= e($course['hook']) ?></p>
  </div>
</div>

<main id="main" class="section">
  <div class="container course-layout">
    <div>
      <section style="margin-bottom:3rem">
        <h2>Acerca del curso</h2>
        <div style="line-height:1.7;color:var(--text);font-size:1.05rem">
          <?= nl2br(e($course['description'])) ?>
        </div>
        
        <div class="card" style="margin-top:2rem;background:var(--paper);border:none;padding:1.5rem">
          <h3 class="title-md" style="margin-bottom:.75rem">¿Qué lograrás?</h3>
          <p class="mb-0"><?= icon('target') ?> <?= e($course['outcome']) ?></p>
        </div>
      </section>

      <section>
        <h2 style="margin-bottom:1.5rem">Programa de Avance</h2>
        <div class="stack" style="--gap:1rem">
          <?php foreach ($outline as $i => $m): ?>
            <details class="module" <?= $i === 0 ? 'open' : '' ?>>
              <summary>
                <div class="summary-content">
                  <span class="n"><?= $i + 1 ?></span>
                  <strong class="title"><?= e($m['title']) ?></strong>
                  <span class="meta"><?= count($m['classes']) ?> clases</span>
                  <span class="chevron"><?= icon('chevron') ?></span>
                </div>
              </summary>
              <ul class="lesson-list">
                <?php foreach ($m['classes'] as $cls): ?>
                  <li>
                    <div class="lesson-row <?= $cls['is_completed'] ? 'is-done' : '' ?>">
                      <span class="state"><?= $cls['is_completed'] ? icon('check') : icon('lock') ?></span>
                      <span class="title"><?= e($cls['title']) ?></span>
                      <span class="dur"><?= $cls['is_completed'] ? 'Dictada el ' . date_es($cls['completed_at']) : 'Pendiente' ?></span>
                    </div>
                    <?php if ($enrolled): ?>
                        <?php if (isset($user_attendances[$cls['id']])): ?>
                            <div style="margin: .25rem 1rem .5rem 3.5rem; color:var(--reward); font-size:.8rem; font-weight:600; display:flex; align-items:center; gap:.25rem">
                                <?= icon('check') ?> Asistencia Registrada
                            </div>
                        <?php elseif ($cls['attendance_expires_at'] && strtotime($cls['attendance_expires_at']) > time()): ?>
                            <form method="post" action="" style="margin: .5rem 1rem .5rem 3.5rem" data-once>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="mark_attendance">
                                <input type="hidden" name="class_id" value="<?= $cls['id'] ?>">
                                <button type="submit" class="btn btn--sm btn--reward" style="border-radius:4px; padding:.25rem .75rem; display:inline-flex; align-items:center; gap:.25rem">
                                  <?= icon('user') ?> Marcar mi asistencia ahora
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($enrolled && !empty($materialsByClass[$cls['id']]) && $cls['is_completed']): ?>
                      <details style="margin: .5rem 1rem 1rem 3.5rem;">
                        <summary style="cursor: pointer; font-size: .85rem; font-weight: 600; color: var(--focus); display: inline-flex; align-items: center; gap: .4rem; user-select: none;">
                          <?= icon('layers') ?> Ver materiales adjuntos (<?= count($materialsByClass[$cls['id']]) ?>)
                        </summary>
                        <div class="cluster" style="padding-top: .75rem; margin-left: 1.5rem;">
                          <?php foreach ($materialsByClass[$cls['id']] as $mat): ?>
                            <a href="<?= url($mat['file_path']) ?>" target="_blank" class="chip chip--focus" style="font-size:.8rem;padding:.2rem .6rem"><?= e($mat['title']) ?></a>
                          <?php endforeach; ?>
                        </div>
                      </details>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
                <?php if (empty($m['classes'])): ?>
                    <li style="padding:1rem 2rem;color:var(--muted);font-size:.9rem">Sin clases programadas aún.</li>
                <?php endif; ?>
              </ul>
            </details>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <aside class="sticky-aside">
      <div class="card" style="padding:1.5rem">
        <?php if ($enrolled): ?>
          <span class="chip chip--growth"><?= icon('check') ?>Estás inscrito</span>
          <div style="margin:1.25rem 0">
            <div class="cluster between" style="font-weight:800;font-size:.9rem;margin-bottom:.5rem"><span>Avance del Curso</span><span><?= $progress ?>%</span></div>
            <div class="progress"><span data-w="<?= $progress ?>"></span></div>
            <p class="muted" style="font-size:.8rem;margin-top:.5rem">El avance es controlado por el instructor del curso.</p>
          </div>
          
          <div class="card card--hover" style="padding:1rem;margin-bottom:1.5rem;border-color:var(--focus)">
            <h4 style="margin-bottom:.5rem;font-size:.95rem">Enlaces Oficiales</h4>
            
            <?php if (!empty($course['meet_url'])): ?>
              <a href="<?= e($course['meet_url']) ?>" target="_blank" class="btn btn--block btn--soft" style="margin-bottom:.5rem"><?= icon('play') ?> Entrar a la clase en vivo</a>
            <?php else: ?>
              <div class="notice notice--soft" style="margin-bottom:.5rem">
                <?= icon('clock') ?> <span>Enlace de clase aún no configurado.</span>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($course['whatsapp_url'])): ?>
              <a href="<?= e($course['whatsapp_url']) ?>" target="_blank" class="btn btn--block btn--soft" style="color:var(--growth);margin-bottom:.25rem"><?= icon('message-square') ?> Unirse al grupo de WhatsApp</a>
            <?php endif; ?>
          </div>
          
          <?php if (!empty($materialsByClass['general'])): ?>
            <hr class="divider">
            <h4 style="margin-bottom:.75rem">Materiales Generales</h4>
            <div class="cluster">
              <?php foreach ($materialsByClass['general'] as $mat): ?>
                <a href="<?= url($mat['file_path']) ?>" target="_blank" class="chip chip--focus"><?= icon('layers') ?> <?= e($mat['title']) ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <div class="seats <?= $seats['available'] === 0 ? 'is-full' : ($isLow ? 'is-low' : '') ?>" style="margin-bottom:1.25rem">
            <div class="seats-label"><span><?= $seats['taken'] ?> de <?= $seats['capacity'] ?> cupos tomados</span><strong><?= $seats['available'] === 0 ? 'Agotado' : ($isLow ? '¡Quedan ' . $seats['available'] . '!' : $seats['available'] . ' libres') ?></strong></div>
            <div class="seats-bar"><span data-w="<?= $seats['capacity'] ? (int) round($seats['taken'] / $seats['capacity'] * 100) : 0 ?>"></span></div>
          </div>
          <?php if ($closed): ?>
            <div class="notice notice--soft"><?= icon('calendar') ?><span>Las inscripciones cerraron el <?= date_es($course['end_date']) ?>.</span></div>
          <?php elseif ($seats['available'] === 0): ?>
            <div class="notice notice--soft"><?= icon('users') ?><span>Este grupo está completo. Revisa otros cursos o rutas.</span></div>
          <?php elseif ($user): ?>
            <?php if (in_array($user['role'], ['admin', 'instructor'])): ?>
              <div class="notice notice--soft"><?= icon('shield') ?><span>Cuenta de gestión. No puedes inscribirte como alumno.</span></div>
            <?php else: ?>
              <form method="post" action="<?= url('enroll.php') ?>" data-once>
                <?= csrf_field() ?>
                <input type="hidden" name="course_id" value="<?= $cid ?>">
                <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('bolt') ?>Reservar mi cupo</button>
              </form>
            <?php endif; ?>
          <?php else: ?>
            <a class="btn btn--primary btn--block btn--lg" href="<?= url('register.php?next=' . urlencode('course.php?slug=' . $course['slug'])) ?>"><?= icon('bolt') ?>Crear cuenta y reservar</a>
            <p class="text-center muted" style="font-size:.88rem;margin:.75rem 0 0">¿Ya tienes cuenta? <a href="<?= url('login.php') ?>">Entra aquí</a></p>
          <?php endif; ?>
        <?php endif; ?>

        <hr class="divider">
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.75rem;font-size:.9rem;color:var(--muted)">
          <li style="display:flex;gap:.5rem;align-items:center"><?= icon('calendar') ?> <strong>Inicio:</strong> <?= date_es($course['start_date']) ?></li>
          <li style="display:flex;gap:.5rem;align-items:center"><?= icon('calendar') ?> <strong>Fin:</strong> <?= date_es($course['end_date']) ?></li>
          <li style="display:flex;gap:.5rem;align-items:center"><?= icon('user') ?> <strong>Instructor:</strong> <?= e($course['instructor_name'] ?? 'Equipo Feder') ?></li>
          <li style="display:flex;gap:.5rem;align-items:center"><?= icon('star') ?> <strong>Nivel:</strong> <?= ucfirst($course['level']) ?></li>
        </ul>
      </div>
    </aside>
  </div>
</main>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>
