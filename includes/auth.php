<?php
declare(strict_types=1);

function current_user(bool $refresh = false): ?array
{
    static $user = null;
    if (empty($_SESSION['uid'])) {
        return null;
    }
    if ($user === null || $refresh) {
        $user = q_one('SELECT id,name,email,role,avatar,profile_picture,created_at
                       FROM users WHERE id = ? AND is_active = 1', [(int) $_SESSION['uid']]);
        if (!$user) {
            unset($_SESSION['uid']);
            return null;
        }
    }
    return $user;
}

function uid(): int
{
    return (int) ($_SESSION['uid'] ?? 0);
}

function is_logged(): bool
{
    return current_user() !== null;
}

function has_role(string ...$roles): bool
{
    $u = current_user();
    return $u !== null && in_array($u['role'], $roles, true);
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            json_out(['ok' => false, 'error' => 'Inicia sesión para continuar.'], 401);
        }
        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('info', 'Inicia sesión para continuar donde lo dejaste.');
        redirect('login.php');
    }
    return $u;
}

function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        flash('warn', 'Esa sección no está disponible para tu cuenta.');
        redirect('dashboard.php');
    }
    return $u;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Limitador simple de intentos de login por sesión (anti fuerza bruta). */
function login_throttled(): bool
{
    $a = $_SESSION['_login_attempts'] ?? ['n' => 0, 't' => time()];
    if (time() - $a['t'] > 600) {
        $a = ['n' => 0, 't' => time()];
    }
    $_SESSION['_login_attempts'] = $a;
    return $a['n'] >= 6;
}

function login_failed(): void
{
    $a = $_SESSION['_login_attempts'] ?? ['n' => 0, 't' => time()];
    $a['n']++;
    $_SESSION['_login_attempts'] = $a;
}
