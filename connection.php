<?php
$env = @parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW);
if ($env === false) {
    http_response_code(500);
    exit('Falta el archivo .env (copia .env.example)');
}

try {
    $pdo = new PDO("pgsql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']}", $env['DB_USER'], $env['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Error de conexión: ' . $e->getMessage());
}

// Prueba rápida: php connection.php
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    echo 'Conectado a ' . $pdo->query('SELECT version()')->fetchColumn() . PHP_EOL;
}
