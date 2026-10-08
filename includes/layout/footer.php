<?php $extraJs = $extraJs ?? []; $hideFooter = $hideFooter ?? false; ?>
<?php if (!$hideFooter): ?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <a class="brand" href="<?= url('index.php') ?>">
          <span class="brand-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 4h12M6 4v16M6 11h8"/><circle cx="18" cy="18" r="2" fill="currentColor" stroke="none"/></svg></span>
          <span><?= APP_NAME ?></span>
        </a>
        <p class="muted" style="margin-top:1rem;max-width:36ch"><?= APP_TAGLINE ?></p>
      </div>
      <div>
        <h3>Aprender</h3>
        <ul>
          <li><a href="<?= url('catalog.php') ?>">Catálogo de Cursos</a></li>
        </ul>
      </div>
      <div>
        <h3>Confianza</h3>
        <ul>
          <li><a href="<?= url('index.php#metodo') ?>">Nuestro método</a></li>
          <li><a href="<?= url('sitemap.php') ?>">Mapa del sitio</a></li>
        </ul>
      </div>
      <div>
        <h3>Cuenta</h3>
        <ul>
          <?php if (is_logged()): ?>
            <li><a href="<?= url(in_array(current_user()['role'], ['admin','instructor']) ? 'admin/index.php' : 'dashboard.php') ?>">Mi panel</a></li>
            <li><a href="<?= url('profile.php') ?>">Perfil</a></li>
            <li><a href="<?= url('logout.php') ?>">Salir</a></li>
          <?php else: ?>
            <li><a href="<?= url('login.php') ?>">Entrar</a></li>
            <li><a href="<?= url('register.php') ?>">Crear cuenta</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= APP_NAME ?>.</span>
      <span>Formación integral · Progreso guiado · Certificación</span>
    </div>
  </div>
</footer>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?php foreach ($extraJs as $js): ?>
<script src="<?= asset('js/' . $js) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
