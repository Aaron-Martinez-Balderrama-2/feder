<?php
require __DIR__ . '/includes/bootstrap.php';

$settings_rows = q_all("SELECT * FROM settings");
$settings = [];
foreach ($settings_rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$address = $settings['office_address'] ?? 'Central No Registrada';
$map_embed = $settings['office_map_embed'] ?? '';

$page_title = 'Mapa del Sitio';
require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/header.php';
?>

<main class="section">
  <div class="container container--narrow">
    <div class="block-title">
      <h2>Mapa del Sitio</h2>
      <p class="lead">Navega rápidamente por la plataforma o visítanos en nuestras oficinas.</p>
    </div>

    <div class="grid-2" style="gap:2rem">
      <div class="card" style="padding:2rem">
        <h3 class="title-md" style="margin-bottom:1.5rem">Estructura Web</h3>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:1rem">
          <li><strong>Público</strong>
            <ul style="margin-top:.5rem;padding-left:1.5rem;color:var(--muted)">
              <li><a href="<?= url('index.php') ?>">Inicio</a></li>
              <li><a href="<?= url('catalog.php') ?>">Catálogo de Cursos</a></li>
              <li><a href="<?= url('index.php#metodo') ?>">El Método Feder</a></li>
            </ul>
          </li>
          <li><strong>Estudiantes</strong>
            <ul style="margin-top:.5rem;padding-left:1.5rem;color:var(--muted)">
              <li><a href="<?= url('dashboard.php') ?>">Mi Panel de Progreso</a></li>
              <li><a href="<?= url('profile.php') ?>">Editar Perfil</a></li>
            </ul>
          </li>
          <?php if (is_logged() && in_array(current_user()['role'], ['admin', 'instructor'], true)): ?>
          <li><strong>Instructores y Administradores</strong>
            <ul style="margin-top:.5rem;padding-left:1.5rem;color:var(--muted)">
              <li><a href="<?= url('admin/index.php') ?>">Panel de Control</a></li>
              <li><a href="<?= url('admin/courses.php') ?>">Gestión de Cursos</a></li>
            </ul>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="card" style="padding:2rem">
        <h3 class="title-md" style="margin-bottom:.5rem">Nuestra Sede</h3>
        <p class="muted" style="margin-bottom:1.5rem"><?= icon('map-pin') ?> <?= e($address) ?></p>
        
        <?php 
        $cur_coords = $map_embed;
        if (strpos($cur_coords, '<iframe') !== false || !$cur_coords) {
            $cur_coords = null; 
        }
        ?>
        <?php if ($cur_coords): ?>
          <div id="public-map" style="height: 350px; border-radius:12px; border:1px solid var(--border); z-index: 1;"></div>
          <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
          <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
          <script>
            document.addEventListener('DOMContentLoaded', function() {
                var val = "<?= e($cur_coords) ?>".split(',');
                var lat = val.length === 2 ? parseFloat(val[0]) : -16.489689;
                var lng = val.length === 2 ? parseFloat(val[1]) : -68.119293;
                
                var map = L.map('public-map').setView([lat, lng], 16);
                
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                L.marker([lat, lng]).addTo(map)
                    .bindPopup("<div style='text-align:center'><b>Nuestra Sede</b><br><?= e($address) ?></div>").openPopup();
            });
          </script>
        <?php else: ?>
          <div class="notice notice--soft">Mapa no configurado. El administrador debe fijar la ubicación en los ajustes globales.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>
