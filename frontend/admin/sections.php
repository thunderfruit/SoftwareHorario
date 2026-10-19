<?php
require_once dirname(__DIR__) . '/grid.php';
requireRole('Administrador');

$periods = listPeriods($pdo);
$schools = listSchoolsWithPlan($pdo);
$periodId = intParam($_REQUEST, 'period') ?? (currentPeriod($pdo)['periodo_id'] ?? null);
$schoolId = intParam($_REQUEST, 'school') ?? ($schools[0]['escuela_id'] ?? null);
$back = "admin/sections.php?period=$periodId&school=$schoolId";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    try {
        if (($_POST['action'] ?? '') === 'delete') {
            deleteSection($pdo, intParam($_POST, 'section') ?? 0);
            flash('ok', 'Sección eliminada junto con su horario.');
        } else {
            $letter = strtoupper(trim($_POST['letra'] ?? ''));
            $courseId = intParam($_POST, 'course');
            if (!$courseId || !preg_match('/^[A-Z0-9]{1,5}$/', $letter) || !$periodId || !$schoolId) {
                flash('error', 'Elige un curso y escribe una sección de 1 a 5 letras o dígitos.');
                redirect($back);
            }
            $sectionId = createSection($pdo, [
                'escuela_id' => $schoolId, 'curso_id' => $courseId, 'periodo_id' => $periodId,
                'letra' => $letter, 'extraordinaria' => isset($_POST['extraordinaria']),
            ]);
            flash('ok', 'Sección creada. Ahora agrega sus bloques de horario.');
            redirect("admin/section.php?id=$sectionId");
        }
    } catch (PDOException $e) {
        flash('error', $e->getCode() === '23505' ? 'Esa sección ya existe para el curso en este periodo.' : dbErrorMessage($e));
    }
    redirect($back);
}

$sections = $periodId && $schoolId ? listSections($pdo, $periodId, $schoolId) : [];
$courses = $schoolId ? planCourses($pdo, $schoolId) : [];

pageHeader('Secciones');
?>
<form class="filters" method="get">
    <label>Periodo
        <select name="period"><?= selectOptions($periods, 'periodo_id', 'nombre', $periodId) ?></select>
    </label>
    <label>Escuela
        <select name="school"><?= selectOptions($schools, 'escuela_id', 'nombre', $schoolId) ?></select>
    </label>
    <button type="submit">Ver</button>
</form>

<form class="card form-grid" method="post">
    <?= csrfField() ?>
    <input type="hidden" name="period" value="<?= (int) $periodId ?>">
    <input type="hidden" name="school" value="<?= (int) $schoolId ?>">
    <label class="wide">Curso
        <select name="course" required>
            <option value="">Elige un curso del plan</option>
            <?php foreach (array_unique(array_column($courses, 'ciclo')) as $cycle): ?>
                <optgroup label="Ciclo <?= cycleLabel($cycle) ?>">
                    <?php foreach ($courses as $course): if ($course['ciclo'] !== $cycle) continue; ?>
                        <option value="<?= (int) $course['curso_id'] ?>"><?= e($course['codigo'] . ' · ' . $course['nombre']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Sección
        <input type="text" name="letra" value="A" maxlength="5" required>
    </label>
    <label class="checkbox">
        <input type="checkbox" name="extraordinaria"> Repitencia (dictado extraordinario)
    </label>
    <div class="actions"><button type="submit">Crear sección</button></div>
</form>

<div class="table-wrap">
    <table>
        <thead>
        <tr><th>Ciclo</th><th>Código</th><th>Curso</th><th>Sección</th><th>Bloques</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($sections as $section): ?>
            <tr>
                <td><?= cycleLabel($section['ciclo']) ?></td>
                <td><?= e($section['codigo']) ?></td>
                <td><?= e($section['curso']) ?></td>
                <td><?= e($section['letra']) ?><?= $section['extraordinaria'] ? ' <span class="tag">Repitencia</span>' : '' ?></td>
                <td><?= (int) $section['bloques'] ?></td>
                <td class="row-actions">
                    <a href="section.php?id=<?= (int) $section['seccion_id'] ?>">Horario</a>
                    <form method="post" class="inline" onsubmit="return confirm('¿Eliminar la sección y todos sus bloques de horario?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="section" value="<?= (int) $section['seccion_id'] ?>">
                        <input type="hidden" name="period" value="<?= (int) $periodId ?>">
                        <input type="hidden" name="school" value="<?= (int) $schoolId ?>">
                        <button type="submit" class="link danger">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$sections): ?>
            <tr><td colspan="6" class="empty">No hay secciones en este periodo para esta escuela.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php pageFooter(); ?>
