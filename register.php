<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged()) {
    redirect('dashboard.php');
}
$next = (string) input('next');
$next = preg_match('/^[a-z_]+\.php(\?[\w=&%\-\.#]*)?(#[\w\-]+)?$/i', $next) ? $next : '';
$old = ['name' => '', 'email' => '', 'id_document' => '', 'phone' => '', 'birth_date' => '', 'avatar' => 'aurora'];
$errors = [];

if (is_post()) {
    csrf_verify();
    $old = [
        'name'   => mb_substr((string) input('name'), 0, 120),
        'email'  => mb_strtolower(mb_substr((string) input('email'), 0, 160)),
        'id_document' => mb_substr((string) input('id_document'), 0, 30),
        'phone'  => mb_substr((string) input('phone'), 0, 30),
        'birth_date' => input('birth_date'),
        'avatar' => array_key_exists(input('avatar'), AVATARS) ? input('avatar') : 'aurora',
    ];
    $pass = (string) ($_POST['password'] ?? '');

    if (mb_strlen($old['name']) < 2) $errors['name'] = 'Escribe tu nombre completo.';
    if (mb_strlen($old['id_document']) < 5) $errors['id_document'] = 'Ingresa un carnet válido.';
    if (mb_strlen($old['phone']) < 5) $errors['phone'] = 'Ingresa un teléfono válido.';
    
    $bdate = strtotime((string) $old['birth_date']);
    if (!$bdate) {
        $errors['birth_date'] = 'Ingresa una fecha de nacimiento.';
    } else {
        $age = (time() - $bdate) / (365.25 * 24 * 60 * 60);
        if ($age < 18) {
            $errors['birth_date'] = 'Debes tener al menos 18 años para participar.';
        }
    }

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Ese correo no parece válido.';
    elseif (q_val('SELECT 1 FROM users WHERE email=?', [$old['email']])) $errors['email'] = 'Ya existe una cuenta con ese correo. ¿Quieres entrar?';
    if (strlen($pass) < 8) $errors['password'] = 'Usa al menos 8 caracteres.';

    if (!$errors) {
        q('INSERT INTO users (name,email,password_hash,avatar,id_document,phone,birth_date) VALUES (?,?,?,?,?,?,?)',
            [$old['name'], $old['email'], password_hash($pass, PASSWORD_DEFAULT), $old['avatar'], $old['id_document'], $old['phone'], date('Y-m-d', $bdate)]);
        login_user((int) db()->lastInsertId());
        flash('success', '¡Bienvenido, ' . explode(' ', $old['name'])[0] . '! Tu panel de clases está listo.');
        redirect($next ?: 'catalog.php');
    }
}

$title = 'Crear cuenta';
$noindex = true;
require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/header.php';
?>
<main id="main" class="auth">
  <section class="auth-side" aria-hidden="true">
    <span class="eyebrow" style="color:#FFD9A8">Educación Estructurada</span>
    <h2 style="margin-top:1rem">Avanza junto a tu cohorte y tu instructor.</h2>
    <p>Inscríbete en nuestros cursos guiados, asiste a las clases virtuales y obtén acceso exclusivo al material a medida que avanzas en el programa.</p>
    <div class="mind-steps" style="margin-top:2rem">
      <div class="mind-step" style="--i:0;background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.1)"><span class="n">1</span><div><strong style="color:#fff">Clases Síncronas</strong><small style="color:#A5A1C8">Guiado por expertos</small></div></div>
      <div class="mind-step" style="--i:1;background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.1)"><span class="n">2</span><div><strong style="color:#fff">Material Desbloqueable</strong><small style="color:#A5A1C8">Paso a paso</small></div></div>
      <div class="mind-step" style="--i:2;background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.1)"><span class="n">3</span><div><strong style="color:#fff">Progreso Real</strong><small style="color:#A5A1C8">Medible y seguro</small></div></div>
    </div>
  </section>

  <section class="auth-main">
    <div class="auth-card">
      <h1>Crea tu cuenta</h1>
      <p class="muted">¿Ya tienes una? <a href="<?= url('login.php') ?>">Entra aquí</a>.</p>

      <form method="post" class="stack" style="--gap:1.1rem;margin-top:1.5rem" novalidate data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="field">
          <label for="name">Nombre Completo</label>
          <div class="input-icon"><?= icon('user') ?><input class="input" id="name" name="name" value="<?= e($old['name']) ?>" autocomplete="name" required autofocus></div>
          <?php if (isset($errors['name'])): ?><span class="hint" style="color:var(--soft)"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.1rem;">
          <div class="field">
            <label for="id_document">Carnet de Identidad</label>
            <div class="input-icon"><?= icon('credit-card') ?><input class="input" id="id_document" name="id_document" value="<?= e($old['id_document']) ?>" required></div>
            <?php if (isset($errors['id_document'])): ?><span class="hint" style="color:var(--soft)"><?= e($errors['id_document']) ?></span><?php endif; ?>
          </div>
          <div class="field">
            <label for="birth_date">Fecha de Nacimiento</label>
            <div class="input-icon"><?= icon('calendar') ?><input class="input" type="date" id="birth_date" name="birth_date" value="<?= e($old['birth_date']) ?>" required></div>
            <?php if (isset($errors['birth_date'])): ?><span class="hint" style="color:var(--soft)"><?= e($errors['birth_date']) ?></span><?php endif; ?>
          </div>
        </div>

        <div class="field">
          <label for="phone">Teléfono / WhatsApp</label>
          <div class="input-icon"><?= icon('phone') ?><input class="input" type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" required></div>
          <?php if (isset($errors['phone'])): ?><span class="hint" style="color:var(--soft)"><?= e($errors['phone']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label for="email">Correo</label>
          <div class="input-icon"><?= icon('mail') ?><input class="input" type="email" id="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required></div>
          <?php if (isset($errors['email'])): ?><span class="hint" style="color:var(--soft)"><?= e($errors['email']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label for="password">Contraseña</label>
          <div class="input-icon"><?= icon('key') ?><input class="input" type="password" id="password" name="password" minlength="8" autocomplete="new-password" required></div>
          <span class="hint" style="<?= isset($errors['password']) ? 'color:var(--soft)' : '' ?>"><?= e($errors['password'] ?? 'Mínimo 8 caracteres.') ?></span>
        </div>
        <fieldset class="field" style="border:0;padding:0;margin:0">
          <legend class="label" style="margin-bottom:.5rem">Elige tu color de avatar</legend>
          <div class="choice-grid" style="grid-template-columns:repeat(5,minmax(0,1fr))">
            <?php foreach (AVATARS as $k => $label): ?>
              <label class="choice"><input type="radio" name="avatar" value="<?= $k ?>" <?= $old['avatar'] === $k ? 'checked' : '' ?>><span><span class="avatar avatar--sm av-<?= $k ?>"></span><small><?= e($label) ?></small></span></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <button class="btn btn--primary btn--lg btn--block" type="submit">Crear cuenta <?= icon('arrow', 'ico-arrow') ?></button>
      </form>
    </div>
  </section>
</main>
<?php $hideFooter = true; require __DIR__ . '/includes/layout/footer.php'; ?>
