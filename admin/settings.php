<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if ($user['role'] !== 'admin') {
    flash('warn', 'Solo los administradores pueden acceder a esta sección.');
    redirect('dashboard.php');
}

if (is_post()) {
    csrf_verify();
    $address = trim(input('office_address'));
    $coords = trim(input('office_map_coords'));
    
    q('UPDATE settings SET setting_value=? WHERE setting_key="office_address"', [$address]);
    q('UPDATE settings SET setting_value=? WHERE setting_key="office_map_embed"', [$coords]);
    flash('success', 'Configuración actualizada correctamente.');
    redirect('admin/settings.php');
}

$settings_rows = q_all("SELECT * FROM settings");
$settings = [];
foreach ($settings_rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$page_title = 'Configuración de la Plataforma';
require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/header.php';
?>

<main class="section">
  <div class="container container--narrow">
    <div class="block-title">
      <h2>Ajustes Globales</h2>
      <a href="<?= url('admin/index.php') ?>" class="btn btn--soft">Volver al Panel</a>
    </div>

    <div class="card">
      <form method="post" action="" class="form-grid">
        <?= csrf_field() ?>
        
        <h3 class="span-all" style="margin-bottom:.5rem">Sede Principal</h3>
        
        <div class="field span-all">
          <label>Dirección Física (Texto)</label>
          <input type="text" name="office_address" class="input" required value="<?= e($settings['office_address'] ?? '') ?>">
          <p class="muted" style="font-size:.85rem;margin-top:.25rem">Se mostrará en el mapa del sitio y otros lugares públicos.</p>
        </div>
        
        <div class="field span-all">
          <label>Ubicación en el Mapa</label>
          <?php 
          // Extract current coords or default to La Paz, Bolivia
          $cur_coords = $settings['office_map_embed'] ?? '-16.489689,-68.119293';
          if (strpos($cur_coords, '<iframe') !== false) {
              $cur_coords = '-16.489689,-68.119293'; // Wipe old iframes
          }
          ?>
          <input type="hidden" name="office_map_coords" id="office_map_coords" value="<?= e($cur_coords) ?>">
          <div id="settings-map" style="height: 350px; border-radius: 8px; border: 1px solid var(--border); z-index: 1;"></div>
          <p class="muted" style="font-size:.85rem;margin-top:.5rem">Haz clic en el mapa o arrastra el marcador rojo para fijar tu ubicación exacta.</p>
        </div>
        
        <div class="field span-all" style="margin-top:1.5rem">
          <button type="submit" class="btn btn--primary">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</main>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var coordsInput = document.getElementById('office_map_coords');
    var val = coordsInput.value.split(',');
    var lat = val.length === 2 ? parseFloat(val[0]) : -16.489689;
    var lng = val.length === 2 ? parseFloat(val[1]) : -68.119293;
    
    if (isNaN(lat) || isNaN(lng)) { lat = -16.489689; lng = -68.119293; }

    var map = L.map('settings-map').setView([lat, lng], 15);
    
    // Light map style suitable for clean interfaces
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    var marker = L.marker([lat, lng], {draggable: true}).addTo(map);

    function updateCoords(latlng) {
        coordsInput.value = latlng.lat.toFixed(6) + ',' + latlng.lng.toFixed(6);
    }

    marker.on('dragend', function(e) {
        updateCoords(marker.getLatLng());
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateCoords(e.latlng);
    });
});
</script>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
