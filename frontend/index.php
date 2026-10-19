<?php
require_once dirname(__DIR__) . '/backend/teachers.php';

$filters = [
    'code' => trim($_GET['code'] ?? ''),
    'last_name' => trim($_GET['last_name'] ?? ''),
    'document' => trim($_GET['document'] ?? ''),
];

$error = null;
try {
    $teachers = searchTeachers($pdo, $filters['code'], $filters['last_name'], $filters['document']);
} catch (PDOException $e) {
    $teachers = [];
    error_log($e->getMessage());
    $error = 'No se pudo consultar los docentes.';
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '—', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docentes</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<main>
    <h1>Gestión de Docentes</h1>

    <form class="search" method="get">
        <label>Código
            <input type="text" name="code" value="<?= e($filters['code']) ?>">
        </label>
        <label>Apellidos
            <input type="text" name="last_name" value="<?= e($filters['last_name']) ?>">
        </label>
        <label>N.º documento
            <input type="text" name="document" value="<?= e($filters['document']) ?>">
        </label>
        <div class="actions">
            <button type="submit">Buscar</button>
            <a class="button secondary" href="?">Mostrar todos</a>
        </div>
    </form>

    <?php if ($error): ?>
        <p class="error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <p class="count"><?= count($teachers) ?> docente(s) encontrado(s)</p>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Código</th>
                <th>Apellidos</th>
                <th>Nombres</th>
                <th>Categoría</th>
                <th>Modalidad</th>
                <th>Horas</th>
                <th>Facultad</th>
                <th>Departamento</th>
                <th>Documento</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($teachers as $teacher): ?>
                <tr>
                    <td><?= e($teacher['codigo_docente']) ?></td>
                    <td><?= e($teacher['apellidos']) ?></td>
                    <td><?= e($teacher['nombres']) ?></td>
                    <td><?= e($teacher['categoria']) ?></td>
                    <td><?= e($teacher['modalidad']) ?></td>
                    <td><?= e($teacher['horas_asignadas'] === null ? null : (string) $teacher['horas_asignadas']) ?></td>
                    <td><?= e($teacher['facultad']) ?></td>
                    <td><?= e($teacher['departamento']) ?></td>
                    <td><?= e($teacher['numero_documento'] === null ? null : $teacher['tipo_documento'] . ' ' . $teacher['numero_documento']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$teachers && !$error): ?>
                <tr><td colspan="9" class="empty">No se encontraron docentes con esos criterios.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
