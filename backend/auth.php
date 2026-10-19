<?php
require_once __DIR__ . '/connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

// shortcut: no limit on failed attempts; add a per-IP counter if the system is exposed to the internet
function login(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare('SELECT usuario_id, nombre_usuario, password_hash, rol, docente_id
                           FROM public.usuario WHERE nombre_usuario = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    return true;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function csrfToken(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function verifyCsrf(): void
{
    if (!hash_equals(csrfToken(), $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Solicitud inválida. Recarga la página e inténtalo de nuevo.');
    }
}

function flash(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function takeFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// Maps PostgreSQL constraint errors to a message for the user.
function dbErrorMessage(PDOException $e): string
{
    error_log($e->getMessage());
    return match ($e->getCode()) {
        '23505' => 'Ya existe un registro con esos datos.',
        '23503' => 'No se puede completar: el registro está en uso o hace referencia a otro que no existe.',
        '23514', '22P02', '22001' => 'Algún dato no tiene un formato válido.',
        '23P01' => 'El horario choca con otro bloque.',
        default => 'Ocurrió un error al guardar. Inténtalo de nuevo.',
    };
}
