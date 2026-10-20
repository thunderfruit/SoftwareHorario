<?php
require_once dirname(__DIR__) . '/layout.php';
require_once dirname(__DIR__, 2) . '/backend/teachers.php';
requireRole('Administrador');

$catalogs = teacherCatalogs($pdo);
$filters = [
    'code' => trim($_GET['code'] ?? ''),
    'last_name' => trim($_GET['last_name'] ?? ''),
    'document' => trim($_GET['document'] ?? ''),
    'faculty_id' => intParam($_GET, 'faculty_id'),
    'department_id' => intParam($_GET, 'department_id'),
    'category_id' => intParam($_GET, 'category_id'),
    'modality_id' => intParam($_GET, 'modality_id'),
];
$teachers = searchTeachers($pdo, $filters);
$filtered = array_filter($filters, fn ($v) => $v !== '' && $v !== null) !== [];

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
    <label>Facultad
        <select name="faculty_id"><?= selectOptions($catalogs['faculties'], 'facultad_id', 'nombre', $filters['faculty_id'], 'Todas') ?></select>
    </label>
    <label>Departamento
        <select name="department_id">
            <option value="">Todos</option>
            <?php foreach (groupBy($catalogs['departments'], 'facultad') as $faculty => $departments): ?>
                <optgroup label="<?= e($faculty) ?>">
                    <?= selectOptions($departments, 'departamento_id', 'nombre', $filters['department_id']) ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Categoría
        <select name="category_id"><?= selectOptions($catalogs['categories'], 'categoria_id', 'nombre', $filters['category_id'], 'Todas') ?></select>
    </label>
    <label>Modalidad
        <select name="modality_id"><?= selectOptions($catalogs['modalities'], 'modalidad_id', 'nombre', $filters['modality_id'], 'Todas') ?></select>
    </label>
    <div class="actions">
        <button type="submit">Buscar</button>
        <a class="button secondary" href="?">Mostrar todos</a>
        <a class="button accent" href="teacher.php">Nuevo docente</a>
    </div>
</form>

<p class="count"><?= count($teachers) ?> docente(s) encontrado(s)</p>

<?php if (!$teachers): ?>
    <p class="empty">No se encontraron docentes con esos criterios.</p>
<?php else: ?>
    <?php pageTools(); ?>
    <div class="tree">
        <?php foreach (groupBy($teachers, 'facultad') as $faculty => $facultyTeachers): ?>
            <details class="node"<?= $filtered ? ' open' : '' ?>>
                <summary><?= $faculty === '' ? 'Sin facultad' : 'Facultad de ' . e($faculty) ?>
                    <span class="count"><?= count($facultyTeachers) ?> docente(s)</span></summary>
                <?php foreach (groupBy($facultyTeachers, 'departamento') as $department => $departmentTeachers): ?>
                    <details class="node"<?= $filtered ? ' open' : '' ?>>
                        <summary><?= e($department === '' ? null : $department, 'Sin departamento') ?>
                            <span class="count"><?= count($departmentTeachers) ?> docente(s)</span></summary>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                <tr>
                                    <th>Código</th><th>Apellidos</th><th>Nombres</th><th>Categoría</th><th>Modalidad</th>
                                    <th>Horas</th><th>Documento</th><th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($departmentTeachers as $teacher): ?>
                                    <tr>
                                        <td><?= e($teacher['codigo_docente']) ?></td>
                                        <td><?= e($teacher['apellidos']) ?></td>
                                        <td><?= e($teacher['nombres']) ?></td>
                                        <td><?= e($teacher['categoria']) ?></td>
                                        <td><?= e($teacher['modalidad']) ?></td>
                                        <td><?= e($teacher['horas_asignadas'] === null ? null : (string) $teacher['horas_asignadas']) ?></td>
                                        <td><?= e($teacher['numero_documento'] === null ? null : $teacher['tipo_documento'] . ' ' . $teacher['numero_documento']) ?></td>
                                        <td><a href="teacher.php?id=<?= (int) $teacher['docente_id'] ?>">Editar</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php pageFooter(); ?>
