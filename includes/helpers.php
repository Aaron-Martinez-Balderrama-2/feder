<?php
declare(strict_types=1);

/* ---------------- Salida segura ---------------- */
function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function abs_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}

function asset(string $path): string
{
    $file = ROOT_PATH . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : time();
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, mixed $default = ''): mixed
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key, int $default = 0): int
{
    return (int) filter_var(input($key, $default), FILTER_VALIDATE_INT, ['options' => ['default' => $default]]);
}

/* ---------------- Mensajes flash ---------------- */
function flash(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/* ---------------- CSRF ---------------- */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            json_out(['ok' => false, 'error' => 'Sesión expirada. Recarga la página.'], 419);
        }
        http_response_code(419);
        flash('warn', 'Tu sesión expiró. Inténtalo de nuevo.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
}

/* ---------------- JSON ---------------- */
function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

/* ---------------- Contenido de lecciones ----------------
   Formato: bloques separados por una línea "---".
   Inline: **negrita**, `código`. Cada línea = un párrafo. */
function lesson_blocks(string $content): array
{
    $parts = preg_split('/\R-{3,}\R/u', str_replace("\r\n", "\n", $content)) ?: [];
    return array_values(array_filter(array_map('trim', $parts), fn($b) => $b !== ''));
}

function render_inline(string $text): string
{
    $html = e($text);
    $html = preg_replace('/`([^`]+)`/u', '<code>$1</code>', $html);
    $html = preg_replace('/\*\*([^*]+)\*\*/u', '<strong>$1</strong>', $html);
    return $html;
}

function render_block(string $block): string
{
    $out = '';
    foreach (preg_split('/\R/u', $block) as $line) {
        $line = rtrim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^`(.+)`$/u', trim($line), $m)) {
            $out .= '<pre class="code-line"><code>' . e(rtrim($m[1])) . '</code></pre>';
        } else {
            $out .= '<p>' . render_inline($line) . '</p>';
        }
    }
    return $out;
}



/* ---------------- Formatos ---------------- */
function money(float|string $v): string
{
    $v = (float) $v;
    return $v <= 0 ? 'Gratis' : '$' . number_format($v, 2, '.', ',');
}

function date_es(?string $date, bool $withYear = true): string
{
    if (!$date) {
        return '—';
    }
    $m = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($date);
    return date('j', $t) . ' ' . $m[(int) date('n', $t) - 1] . ($withYear ? ' ' . date('Y', $t) : '');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = strtr($text, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n']);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-') ?: 'item-' . bin2hex(random_bytes(3));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $i = mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1);
    return mb_strtoupper($i ?: '?');
}

function avatar_html(array $user, string $size = 'md'): string
{
    if (!empty($user['profile_picture'])) {
        return '<img src="' . e(url($user['profile_picture'])) . '" class="avatar avatar--' . e($size) . '" style="object-fit:cover" alt="">';
    }
    $av = preg_replace('/[^a-z]/', '', $user['avatar'] ?? 'aurora');
    return '<span class="avatar avatar--' . e($size) . ' av-' . e($av) . '" aria-hidden="true">' . e(initials($user['name'] ?? '?')) . '</span>';
}

const AVATARS = ['aurora' => 'Aurora', 'ember' => 'Brasa', 'ocean' => 'Océano', 'forest' => 'Bosque', 'dusk' => 'Ocaso'];

function greeting(): string
{
    $h = (int) date('G');
    return $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
}

function hex_alpha(string $hex, float $alpha): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        $hex = '4338CA';
    }
    [$r, $g, $b] = sscanf($hex, '%02x%02x%02x');
    return "rgba($r,$g,$b,$alpha)";
}

function safe_color(?string $hex): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $hex) ? (string) $hex : '#4338CA';
}

/* ---------------- Paginación ---------------- */
function pagination_html(int $total, int $per_page, int $current_page): string
{
    $total_pages = (int) ceil($total / $per_page);
    if ($total_pages <= 1) return '';
    
    $html = '<div class="pagination cluster" style="justify-content:center;margin-top:2rem;gap:.5rem">';
    
    $qs = $_GET;
    unset($qs['p']);
    $base_url = '?' . http_build_query($qs);
    $base_url .= (empty($qs) ? '' : '&') . 'p=';
    
    if ($current_page > 1) {
        $html .= '<a href="' . e($base_url . ($current_page - 1)) . '" class="btn btn--sm btn--ghost">Anterior</a>';
    }
    
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == 1 || $i == $total_pages || abs($i - $current_page) <= 2) {
            $class = $i === $current_page ? 'btn btn--sm btn--primary' : 'btn btn--sm btn--ghost';
            $html .= '<a href="' . e($base_url . $i) . '" class="' . $class . '">' . $i . '</a>';
        } elseif (abs($i - $current_page) == 3) {
            $html .= '<span class="muted" style="padding:0 .5rem">...</span>';
        }
    }
    
    if ($current_page < $total_pages) {
        $html .= '<a href="' . e($base_url . ($current_page + 1)) . '" class="btn btn--sm btn--ghost">Siguiente</a>';
    }
    
    $html .= '</div>';
    return $html;
}

function paginate(string $sql, array $params, int $per_page): array
{
    $total = (int) q_val("SELECT COUNT(*) FROM ($sql) as t", $params);
    $current_page = max(1, input_int('p', 1));
    $total_pages = max(1, (int) ceil($total / $per_page));
    $current_page = min($current_page, $total_pages);
    $offset = ($current_page - 1) * $per_page;
    
    $records = q_all($sql . " LIMIT $per_page OFFSET $offset", $params);
    
    return [
        'data' => $records,
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'html' => pagination_html($total, $per_page, $current_page)
    ];
}
