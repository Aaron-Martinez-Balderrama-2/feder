<?php
/**
 * Variables opcionales antes de incluir:
 * $title, $desc, $bodyClass, $extraCss (array), $canonical, $jsonLd (array|null), $noindex (bool), $ogType
 */
$title     = isset($title) ? $title . ' · ' . APP_NAME : APP_NAME . ' · ' . APP_TAGLINE;
$desc      = $desc ?? 'Micro-cursos de 7 minutos diseñados con neurociencia del aprendizaje: lecciones enfocadas, repasos espaciados, desafíos sin miedo y certificados verificables.';
$bodyClass = $bodyClass ?? '';
$extraCss  = $extraCss ?? [];
$__script   = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$canonical = $canonical ?? abs_url($__script === 'index.php' ? '' : $__script);
$ogType    = $ogType ?? 'website';
?><!DOCTYPE html>
<html lang="<?= APP_LOCALE ?>" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if (!empty($noindex)): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="theme-color" content="#F7F4EE" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#121120" media="(prefers-color-scheme: dark)">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta property="og:type" content="<?= e($ogType) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:site_name" content="<?= APP_NAME ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?= url('assets/img/favicon.png') ?>" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&family=Fraunces:ital,opsz,wght@0,9..144,500..700;1,9..144,400..600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<script>try{var t=localStorage.getItem('feder-theme');if(t){document.documentElement.dataset.theme=t}else if(matchMedia('(prefers-color-scheme: dark)').matches){document.documentElement.dataset.theme='dark'}}catch(e){}</script>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<?php foreach ($extraCss as $css): ?>
<link rel="stylesheet" href="<?= asset('css/' . $css) ?>">
<?php endforeach; ?>
<?php if (!empty($jsonLd)): ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main">Saltar al contenido</a>
<?php $__flashes = flashes(); if ($__flashes): ?>
<div class="flash-stack" role="status" aria-live="polite">
  <?php foreach ($__flashes as $f): $ft = in_array($f['type'], ['success', 'warn', 'info'], true) ? $f['type'] : 'info'; ?>
  <div class="flash flash--<?= $ft ?>">
    <?= icon($ft === 'success' ? 'check' : ($ft === 'warn' ? 'sparkles' : 'spark')) ?>
    <p><?= e($f['msg']) ?></p>
    <button type="button" aria-label="Cerrar"><?= icon('x') ?></button>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
