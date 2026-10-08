<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

if ($user['role'] !== 'admin') {
    flash('warn', 'Solo los administradores pueden gestionar categorías.');
    redirect('dashboard.php');
}

// Agregar Categoría
if (is_post() && input('action') === 'add_category') {
    csrf_verify();
    $name = trim(input('name'));
    $icon = trim(input('icon')) ?: 'spark';
    $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($name));
    
    if ($name) {
        try {
            q('INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)', [$name, $slug, $icon]);
            flash('success', 'Categoría añadida correctamente.');
        } catch (Throwable $e) {
            flash('error', 'Error al crear la categoría. Probablemente el nombre (slug) ya existe.');
        }
    }
    redirect('admin/categories.php');
}

// Eliminar Categoría
if (is_post() && input('action') === 'delete_category') {
    csrf_verify();
    $id = (int) input('id');
    try {
        q('DELETE FROM categories WHERE id=?', [$id]);
        flash('success', 'Categoría eliminada.');
    } catch (Throwable $e) {
        flash('error', 'No se puede eliminar la categoría. Probablemente tiene cursos asignados.');
    }
    redirect('admin/categories.php');
}

$categories = q_all("SELECT * FROM categories ORDER BY name");

$page_title = 'Gestión de Categorías - ' . APP_NAME;
require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/header.php';
?>

<main class="section">
  <div class="container container--narrow">
    <div class="block-title">
      <h2>Categorías</h2>
      <a href="<?= url('admin/index.php') ?>" class="btn btn--soft">Volver al Panel</a>
    </div>
    
    <div class="grid-2" style="gap:2rem">
      <!-- Lista de Categorías -->
      <div class="card" style="padding:1.5rem">
        <h3 class="title-md" style="margin-bottom:1rem">Categorías Actuales</h3>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:1rem">
          <?php foreach ($categories as $cat): ?>
            <li class="cluster between" style="background:var(--bg);padding:1rem;border-radius:8px;flex-wrap:nowrap;">
              <div class="cluster" style="flex-wrap:nowrap;">
                <span style="color:var(--focus);flex-shrink:0;"><?= icon($cat['icon']) ?></span>
                <strong><?= e($cat['name']) ?></strong>
              </div>
              <form method="post" action="" onsubmit="return confirm('¿Eliminar esta categoría? (Los cursos fallarán si no se reasignan)')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                <button type="submit" class="btn btn--sm btn--ghost" style="color:var(--error)" aria-label="Eliminar"><?= icon('trash') ?></button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      
      <!-- Añadir Categoría -->
      <div class="card" style="padding:1.5rem">
        <h3 class="title-md" style="margin-bottom:1rem">Añadir Categoría</h3>
        <form method="post" action="">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_category">
          <div class="field">
            <label>Nombre de la Categoría</label>
            <input type="text" name="name" class="input" required>
          </div>
          <div class="field">
            <label>Ícono representativo</label>
            <?php 
            $all_icons = [
                'spark'=>'Chispa', 'code'=>'Código', 'coin'=>'Moneda', 'search'=>'Buscar', 'brain'=>'Cerebro', 
                'bolt'=>'Rayo', 'flag'=>'Bandera', 'flame'=>'Fuego', 'award'=>'Trofeo', 'target'=>'Objetivo', 
                'medal'=>'Medalla', 'clock'=>'Reloj', 'users'=>'Usuarios', 'arrow'=>'Flecha', 'arrow-left'=>'Flecha Izq', 
                'check'=>'Check', 'x'=>'Cerrar', 'play'=>'Play', 'book'=>'Libro', 'layers'=>'Capas', 
                'home'=>'Inicio', 'grid'=>'Cuadrícula', 'user'=>'Usuario', 'logout'=>'Salir', 'menu'=>'Menú', 
                'sun'=>'Sol', 'moon'=>'Luna', 'lock'=>'Candado', 'shield'=>'Escudo', 'chart'=>'Gráfico', 
                'refresh'=>'Refrescar', 'plus'=>'Más', 'edit'=>'Editar', 'trash'=>'Basurero', 'eye'=>'Ojo', 
                'star'=>'Estrella', 'calendar'=>'Calendario', 'package'=>'Paquete', 'compass'=>'Brújula', 
                'focus'=>'Enfoque', 'heart'=>'Corazón', 'download'=>'Descargar', 'printer'=>'Impresora', 
                'chevron'=>'Chevron', 'settings'=>'Ajustes', 'list'=>'Lista', 'sparkles'=>'Destellos', 
                'mail'=>'Correo', 'key'=>'Llave', 'message'=>'Mensaje'
            ];
            $favs = ['spark', 'book', 'bolt', 'compass', 'target', 'code', 'coin', 'shield', 'chart', 'users', 'flame', 'award', 'star', 'heart'];
            ?>
            <div class="custom-select-wrapper" style="position:relative">
              <input type="hidden" name="icon" id="icon-input" value="spark">
              <button type="button" id="icon-toggle" class="input" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;text-align:left;background:var(--bg)">
                <span id="icon-preview"><?= icon('spark') ?></span>
                <span id="icon-label">Chispa</span>
                <span style="margin-left:auto;color:var(--muted)"><?= icon('chevron') ?></span>
              </button>
              
              <div id="icon-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:var(--paper);border:1px solid var(--border);border-radius:8px;margin-top:.25rem;box-shadow:0 10px 25px rgba(0,0,0,0.1);z-index:100;max-height:300px;display:none;flex-direction:column;overflow:hidden">
                <div style="padding:.75rem;border-bottom:1px solid var(--border);background:var(--bg)">
                  <input type="text" id="icon-search" class="input" placeholder="Buscar ícono por nombre..." autocomplete="off" style="padding:.5rem;font-size:.9rem">
                </div>
                <div style="overflow-y:auto;padding:.5rem" id="icon-list">
                  <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);padding:.5rem .75rem;margin-top:.5rem">Favoritos</div>
                  <?php foreach ($favs as $k): ?>
                    <div class="icon-option" data-val="<?= $k ?>" data-name="<?= strtolower($all_icons[$k]) ?>" style="display:flex;align-items:center;gap:.75rem;padding:.5rem .75rem;cursor:pointer;border-radius:6px">
                      <span style="color:var(--focus);width:24px"><?= icon($k) ?></span> <span><?= $all_icons[$k] ?></span>
                    </div>
                  <?php endforeach; ?>
                  
                  <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);padding:.5rem .75rem;margin-top:.5rem;border-top:1px solid var(--border);padding-top:1rem">Todos los íconos</div>
                  <?php 
                  asort($all_icons);
                  foreach ($all_icons as $k => $label): 
                    if (in_array($k, $favs)) continue;
                  ?>
                    <div class="icon-option" data-val="<?= $k ?>" data-name="<?= strtolower($label) ?>" style="display:flex;align-items:center;gap:.75rem;padding:.5rem .75rem;cursor:pointer;border-radius:6px">
                      <span style="color:var(--muted);width:24px"><?= icon($k) ?></span> <span><?= $label ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <style>
              .icon-option:hover { background:var(--surface); }
              .icon-option[data-hidden="true"] { display:none!important; }
            </style>
            <script>
              const toggle = document.getElementById('icon-toggle');
              const dropdown = document.getElementById('icon-dropdown');
              const input = document.getElementById('icon-input');
              const search = document.getElementById('icon-search');
              const preview = document.getElementById('icon-preview');
              const label = document.getElementById('icon-label');
              const options = document.querySelectorAll('.icon-option');

              toggle.addEventListener('click', (e) => {
                e.preventDefault();
                dropdown.style.display = dropdown.style.display === 'none' ? 'flex' : 'none';
                if (dropdown.style.display === 'flex') search.focus();
              });

              document.addEventListener('click', (e) => {
                if (!e.target.closest('.custom-select-wrapper')) {
                  dropdown.style.display = 'none';
                }
              });

              search.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase().trim();
                options.forEach(opt => {
                  const match = opt.getAttribute('data-name').includes(term);
                  opt.setAttribute('data-hidden', match ? 'false' : 'true');
                });
              });

              options.forEach(opt => {
                opt.addEventListener('click', () => {
                  input.value = opt.getAttribute('data-val');
                  label.textContent = opt.querySelector('span:last-child').textContent;
                  preview.innerHTML = opt.querySelector('span:first-child').innerHTML;
                  dropdown.style.display = 'none';
                  search.value = '';
                  options.forEach(o => o.setAttribute('data-hidden', 'false'));
                });
              });
            </script>
          </div>
          <div style="margin-top:1.5rem">
            <button type="submit" class="btn btn--primary">Crear Categoría</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
