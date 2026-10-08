<?php
declare(strict_types=1);

/* ---------------- Base de datos ---------------- */
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'feder';
const DB_USER = 'root';
const DB_PASS = '';

/* ---------------- Aplicación ---------------- */
const APP_NAME    = 'Feder';
const APP_TAGLINE = 'Aprende en 7 minutos. Recuérdalo para siempre.';
const APP_LOCALE  = 'es';

/* Ejecutable de Python (ajusta la ruta completa si Apache no lo encuentra en PATH,
   ej: 'C:\\Users\\TuUsuario\\AppData\\Local\\Programs\\Python\\Python314\\python.exe') */
const PYTHON_BIN = 'python';

/* Rutas físicas */
define('ROOT_PATH', dirname(__DIR__));
define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');
define('PYTHON_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'python');

date_default_timezone_set('America/La_Paz');

/* URL base autodetectada (funciona en cualquier subcarpeta de htdocs) */
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $appRoot = str_replace('\\', '/', (string) realpath(ROOT_PATH));
    $base = '';
    if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
        $base = substr($appRoot, strlen($docRoot));
    }
    define('BASE_URL', rtrim($base, '/'));
}
