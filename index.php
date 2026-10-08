<?php
require __DIR__ . '/includes/bootstrap.php';

$stats    = platform_stats();
$courses  = published_courses(['limit' => 6]);
$user = current_user();

$title = 'Educación Estructurada y Guiada por Expertos';
$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'EducationalOrganization', 'name' => APP_NAME, 'url' => abs_url(), 'description' => APP_TAGLINE],
        ['@type' => 'WebSite', 'name' => APP_NAME, 'url' => abs_url(),
         'potentialAction' => ['@type' => 'SearchAction', 'target' => abs_url('catalog.php?q={q}'), 'query-input' => 'required name=q']],
    ],
];
require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/header.php';
?>
<main id="main">

  <!-- ============ HERO ============ -->
  <section class="hero">
    <div class="container hero-grid">
      <div>
        <span class="hero-question reveal">Educación con estructura y seguimiento real.</span>
        <h1 class="title-xl reveal" style="--d:.08s;margin-top:1.25rem">Cursos guiados por <span class="hl">instructores</span> expertos.</h1>
        <p class="lead reveal" style="--d:.16s">Avanza paso a paso junto a tu cohorte. Clases síncronas, materiales desbloqueados en el momento correcto y asistencia controlada para garantizar tu éxito.</p>
        <div class="hero-cta reveal" style="--d:.24s">
          <a class="btn btn--primary btn--lg" href="<?= url($user ? 'dashboard.php' : 'register.php') ?>">
            <?= $user ? 'Ir a mi panel' : 'Inscríbete gratis' ?> <?= icon('arrow', 'ico-arrow') ?>
          </a>
          <a class="btn btn--ghost btn--lg" href="<?= url('catalog.php') ?>"><?= icon('compass') ?>Ver cursos</a>
        </div>
        <div class="hero-proof reveal" style="--d:.32s">
          <div class="proof-item"><strong data-count="<?= $stats['students'] ?>">0</strong><span>estudiantes activos</span></div>
          <div class="proof-item"><strong data-count="<?= $stats['courses'] ?>">0</strong><span>cursos impartidos</span></div>
        </div>
      </div>

      <!-- Visual -->
      <aside class="mind reveal" style="--d:.2s" aria-label="Cómo funciona un curso">
        <div class="mind-head">
          <h2>Tu Ruta de Aprendizaje</h2>
        </div>
        <div class="mind-steps" style="margin-top:1rem">
          <div class="mind-step" style="--i:0"><span class="n">1</span><div><strong>Inscripción</strong><small>Únete a tu cohorte antes de la fecha límite</small></div></div>
          <div class="mind-step" style="--i:1"><span class="n">2</span><div><strong>Clases en vivo</strong><small>El instructor dicta la clase y marca asistencia</small></div></div>
          <div class="mind-step" style="--i:2"><span class="n">3</span><div><strong>Desbloqueo de recursos</strong><small>Los materiales se liberan conforme avanzas</small></div></div>
        </div>
      </aside>
    </div>
  </section>

  <!-- ============ MÉTODO ============ -->
  <section class="section" id="metodo">
    <div class="container">
      <div class="section-head reveal">
        <span class="eyebrow">Nuestra Metodología</span>
        <h2 class="title-lg">Las 4 fases del conocimiento integral.</h2>
        <p class="lead">Todo curso en Feder está cimentado obligatoriamente sobre 4 pilares fundamentales para asegurar que domines el panorama completo del proyecto.</p>
      </div>
      <div class="grid-auto method" style="--min:250px">
        <?php
        $method = [
            ['compass', 'Modelo de Negocio', 'Comprende la estructura, el mercado objetivo y cómo generar valor real.'],
            ['coin', 'Finanzas', 'Aprende a estructurar costos, proyecciones y viabilidad económica.'],
            ['search', 'Marketing Digital', 'Estrategias de posicionamiento, captación de usuarios y ventas online.'],
            ['shield', 'Legal', 'Regulaciones, contratos y cumplimiento normativo para blindar el proyecto.'],
        ];
        foreach ($method as $k => [$ic, $t, $d]): ?>
          <article class="card card--hover method-card reveal" style="--d:<?= $k * 0.08 ?>s">
            <span class="ico-wrap"><?= icon($ic) ?></span>
            <h3 class="card-title"><?= e($t) ?></h3>
            <p class="muted mb-0"><?= e($d) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============ CURSOS ============ -->
  <section class="section">
    <div class="container">
      <div class="cluster between reveal" style="margin-bottom:2rem;align-items:flex-end">
        <div class="section-head mb-0">
          <span class="eyebrow">Cursos abiertos</span>
          <h2 class="title-lg mb-0">Encuentra tu próximo desafío.</h2>
        </div>
        <a class="btn btn--line" href="<?= url('catalog.php') ?>">Ver catálogo <?= icon('arrow', 'ico-arrow') ?></a>
      </div>
      <div class="grid-auto" style="--min:300px">
        <?php foreach ($courses as $i => $c) { include __DIR__ . '/includes/partials/course_card.php'; } ?>
      </div>
    </div>
  </section>

  <!-- ============ CTA FINAL ============ -->
  <section class="section">
    <div class="container">
      <div class="cta-band reveal">
        <div style="position:relative;z-index:1;max-width:640px">
          <span class="eyebrow" style="color:#FFD9A8">Educación Activa</span>
          <h2 class="title-lg" style="margin-top:.75rem">No estudies solo. Estudia con guía.</h2>
          <p class="lead" style="color:#C7C3E6">Inscríbete hoy, asiste a tus clases virtuales, y desbloquea el conocimiento estructurado que impulsará tu carrera.</p>
          <div class="cluster" style="margin-top:1.5rem">
            <a class="btn btn--reward btn--lg" href="<?= url($user ? 'catalog.php' : 'register.php') ?>"><?= icon('play') ?>Comenzar mi camino</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>
