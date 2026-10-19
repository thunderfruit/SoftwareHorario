<?php
require_once dirname(__DIR__) . '/layout.php';
require_once dirname(__DIR__, 2) . '/backend/teachers.php';
requireRole('Administrador');

$filters = [
    'code' => trim($_GET['code'] ?? ''),
    'last_name' => trim($_GET['last_name'] ?? ''),
    'document' => trim($_GET['document'] ?? ''),
];
$teachers = searchTeachers($pdo, $filters['code'], $filters['last_name'], $filters['document']);

pageHeader('Docentes');
?>
<form class="filters" method="get">
    <label>Código
        <input type="text" name="code" value="<?= e($filters['code'], '') ?>">
    </label>
    <label>Apellidos
        <input type="text" name="last_name" value="<?= e($filters['last_name'], '') ?>">
    </label>
    <label>N.º documento
        <input type="text" name="document" value="<?= e($filters['document'], '') ?>">
    </label>
    <div class="actions">
        <button type="submit">Buscar</button>
        <a class="button secondary" href="?">Mostrar todos</a>
        <a class="button accent" href="teacher.php">Nuevo docente</a>
    </div>
</form>

<p class="count"><?= count($teachers) ?> docente(s) encontrado(s)</p>

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Código</th><th>Apellidos</th><th>Nombres</th><th>Categoría</th><th>Modalidad</th>
            <th>Horas</th><th>Facultad</th><th>Departamento</th><th>Documento</th><th></th>
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
                <td><a href="teacher.php?id=<?= (int) $teacher['docente_id'] ?>">Editar</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$teachers): ?>
            <tr><td colspan="10" class="empty">No se encontraron docentes con esos criterios.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php pageFooter(); ?>
