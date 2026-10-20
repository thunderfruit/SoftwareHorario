<?php
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__) . '/backend/schedule.php';
require_once dirname(__DIR__) . '/backend/teachers.php';

$schoolsByCampus = groupBy(listSchoolsByCampus($pdo), 'sede_id');
$departmentsByFaculty = groupBy(listDepartmentsWithTeachers($pdo), 'facultad_id');
$campuses = listCampuses($pdo);

pageHeader('Estructura');
?>
<p class="hint">Cada escuela pertenece a una sede y a una facultad. Los docentes pertenecen al departamento académico de su facultad,
    que atiende a las escuelas de todas sus sedes.</p>
<?php pageTools(); ?>
<div class="tree">
    <details class="node" open>
        <summary>Universidad Nacional de Trujillo <span class="count"><?= count($campuses) ?> sede(s)</span></summary>
        <?php foreach ($campuses as $campus): ?>
            <?php $byFaculty = groupBy($schoolsByCampus[$campus['sede_id']] ?? [], 'facultad_id'); ?>
            <details class="node">
                <summary>Sede <?= e($campus['nombre']) ?> <span class="count"><?= count($byFaculty) ?> facultad(es)</span></summary>
                <?php if (!$byFaculty): ?>
                    <p class="empty">Sin escuelas registradas en esta sede.</p>
                <?php endif; ?>
                <?php foreach ($byFaculty as $facultyId => $schools): ?>
                    <?php $departments = groupBy($departmentsByFaculty[$facultyId] ?? [], 'departamento_id'); ?>
                    <details class="node">
                        <summary>Facultad de <?= e($schools[0]['facultad']) ?></summary>
                        <details class="node">
                            <summary>Escuelas <span class="count"><?= count($schools) ?></span></summary>
                            <ul class="block-list">
                                <?php foreach ($schools as $school): ?>
                                    <li><?= e($school['escuela']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                        <details class="node">
                            <summary>Departamentos académicos <span class="count"><?= count($departments) ?></span></summary>
                            <?php foreach ($departments as $rows): ?>
                                <?php $teachers = array_filter($rows, fn ($t) => $t['docente_id'] !== null); ?>
                                <details class="node">
                                    <summary><?= e($rows[0]['departamento']) ?>
                                        <span class="count"><?= count($teachers) ?> docente(s)</span></summary>
                                    <ul class="block-list">
                                        <?php foreach ($teachers as $teacher): ?>
                                            <li><?= e($teacher['docente']) ?> <span class="hint">(<?= e($teacher['codigo_docente']) ?>)</span></li>
                                        <?php endforeach; ?>
                                        <?php if (!$teachers): ?>
                                            <li class="hint">Sin docentes registrados.</li>
                                        <?php endif; ?>
                                    </ul>
                                </details>
                            <?php endforeach; ?>
                        </details>
                    </details>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
    </details>
</div>
<?php pageFooter(); ?>
