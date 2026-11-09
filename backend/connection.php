<?php
// errors go to the server log, never to the page (XAMPP shows them by default)
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
}
// Render (production) sets real environment variables; locally they come from .env
$env = array_filter(array_map('getenv', array_combine(
    $k = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_SSLMODE'], $k)), fn($v) => $v !== false);
if (!isset($env['DB_HOST'])) {
    $env = @parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW);
    if ($env === false) {
        http_response_code(500);
        exit('Falta el archivo .env (copia .env.example)');
    }
}

try {
    $pdo = new PDO("pgsql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};sslmode=" . ($env['DB_SSLMODE'] ?? 'prefer'), $env['DB_USER'], $env['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    exit('No se pudo conectar a la base de datos.');
}

// Escapes LIKE wildcards so a user typing "%" or "_" searches for them literally.
function likePattern(string $value): string
{
    return '%' . addcslashes($value, '%_\\') . '%';
}

// Prueba rápida: php connection.php
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    echo 'Conectado a ' . $pdo->query('SELECT version()')->fetchColumn() . PHP_EOL;
}
