<?php
// Usage: php backend/create_user.php <username> admin
//        php backend/create_user.php <username> docente <teacher_code>
// Asks for the password interactively so it never ends up in the shell history.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/connection.php';

[, $username, $role, $teacherCode] = $argv + [null, null, null, null];
$roles = ['admin' => 'Administrador', 'docente' => 'Docente'];
if (!$username || !isset($roles[$role]) || ($role === 'docente') !== ($teacherCode !== null)) {
    fwrite(STDERR, "Uso: php backend/create_user.php <usuario> admin\n"
        . "     php backend/create_user.php <usuario> docente <codigo_docente>\n");
    exit(1);
}

$teacherId = null;
if ($teacherCode !== null) {
    $stmt = $pdo->prepare('SELECT docente_id FROM public.docente WHERE codigo_docente = ?');
    $stmt->execute([$teacherCode]);
    $teacherId = $stmt->fetchColumn() ?: null;
    if ($teacherId === null) {
        fwrite(STDERR, "No existe un docente con código $teacherCode.\n");
        exit(1);
    }
}

fwrite(STDOUT, 'Contraseña (mínimo 8 caracteres): ');
system('stty -echo 2>/dev/null');
$password = trim((string) fgets(STDIN));
system('stty echo 2>/dev/null');
fwrite(STDOUT, "\n");
if (strlen($password) < 8) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres.\n");
    exit(1);
}

try {
    $pdo->prepare('INSERT INTO public.usuario (nombre_usuario, password_hash, rol, docente_id) VALUES (?, ?, ?, ?)')
        ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $roles[$role], $teacherId]);
} catch (PDOException $e) {
    fwrite(STDERR, $e->getCode() === '23505'
        ? "Ya existe ese usuario o ese docente ya tiene cuenta.\n"
        : 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
echo "Usuario $username creado con rol {$roles[$role]}.\n";
