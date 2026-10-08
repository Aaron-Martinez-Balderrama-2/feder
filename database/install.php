<?php
/**
 * Instalador de la base de datos "feder".
 * Uso CLI:      php database/install.php
 * Uso navegador: http://localhost/.../feder/database/install.php  (solo desde localhost)
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('Instalación permitida solo desde localhost.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require __DIR__ . '/../config/config.php';

$sql = file_get_contents(__DIR__ . '/feder.sql');
if ($sql === false) {
    exit("No se encontró feder.sql\n");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    $db->set_charset('utf8mb4');
    $db->multi_query($sql);
    $n = 0;
    do {
        $n++;
        if ($res = $db->store_result()) {
            $res->free();
        }
    } while ($db->more_results() && $db->next_result());

    $check = $db->query('SELECT COUNT(*) c FROM feder.lessons')->fetch_assoc();
    echo "OK · {$n} sentencias ejecutadas · {$check['c']} micro-lecciones cargadas.\n";
    echo "Acceso admin:      admin@feder.com / Admin123!\n";
    echo "Acceso instructor: instructor@feder.com / Demo123!\n";
    echo "Acceso estudiante: estudiante@feder.com / Demo123!\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
