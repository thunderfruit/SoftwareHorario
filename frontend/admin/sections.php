<?php
require_once dirname(__DIR__) . '/grid.php';
require_once dirname(__DIR__, 2) . '/backend/teaching_load.php';
require_once dirname(__DIR__, 2) . '/backend/teachers.php';
requireRole('Administrador');

$periods = listPeriods($pdo);
$schools = listSchoolsWithPlan($pdo);
$periodId = intParam($_REQUEST, 'period') ?? (currentPeriod($pdo)['periodo_id'] ?? null);
$schoolId = intParam($_REQUEST, 'school') ?? ($schools[0]['escuela_id'] ?? null);
$cycle = intParam($_REQUEST, 'cycle');
$back = "admin/sections.php?period=$periodId&school=$schoolId&cycle=$cycle";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            deleteSection($pdo, intParam($_POST, 'section') ?? 0);
            flash('ok', 'Sección eliminada junto con su horario.');
        } elseif ($action === 'assign') {
            $section = getSection($pdo, intParam($_POST, 'section') ?? 0);
            $posted = is_array($_POST['teacher'] ?? null) ? $_POST['teacher'] : [];
            // only the parts the course has; an empty choice unassigns the part
            $teacherByType = [];
            foreach (array_keys($section ? sessionHours($section) : []) as $type) {
                $teacherByType[$type] = intParam($posted, $type);
            }
            if ($section) {
                saveLoads($pdo, $section['seccion_id'], $teacherByType);
                flash('ok', 'Carga horaria guardada.');
            }
        } else {
            $letter = strtoupper(trim($_POST['letra'] ?? ''));
            $courseId = intParam($_POST, 'course');
            if (!$courseId || !preg_match('/^[A-Z0-9]{1,5}$/', $letter) || !$periodId || !$schoolId) {
                flash('error', 'Elige un curso y escribe una sección de 1 a 5 letras o dígitos.');
                redirect($back);
            }
            createSection($pdo, [
                'escuela_id' => $schoolId, 'curso_id' => $courseId, 'periodo_id' => $periodId,
                'letra' => $letter, 'extraordinaria' => isset($_POST['extraordinaria']),
            ]);
            flash('ok', 'Sección creada. Ahora asigna sus docentes.');
        }
    } catch (PDOException $e) {
        flash('error', $e->getCode() === '23505' ? 'Esa sección ya existe para el curso en este periodo.' : dbErrorMessage($e));
    }
    redirect($back);
}

$courses = $schoolId ? array_filter(planCourses($pdo, $schoolId), fn ($c) => !$cycle || $c['ciclo'] === $cycle) : [];
$sections = $periodId && $schoolId ? groupBy(listSections($pdo, $periodId, $schoolId), 'curso_id') : [];
$loads = [];
foreach ($periodId && $schoolId ? listLoads($pdo, $periodId, $schoolId) : [] as $load) {
    $loads[$load['seccion_id']][$load['tipo_sesion']] = $load;
}
$teachers = array_map(fn ($t) => $t + ['label' => $t['apellidos'] . ', ' . $t['nombres']], listAllTeachers($pdo));
$lastCycle = max(array_column($schools, 'ultimo_ciclo') ?: [1]);

pageHeader('Carga horaria', function () use ($periods, $periodId, $schools, $schoolId, $lastCycle, $cycle) { ?>
    <form class="filters" method="get">
        <label>Periodo
            <select name="period"><?= selectOptions($periods, 'periodo_id', 'nombre', $periodId) ?></select>
        </label>
        <label>Escuela
            <select name="school"><?= selectOptions($schools, 'escuela_id', 'nombre', $schoolId) ?></select>
        </label>
        <label>Ciclo
            <select name="cycle">
                <option value="">Todos</option>
                <?php for ($c = 1; $c <= $lastCycle; $c++): ?>
                    <option value="<?= $c ?>"<?= $c === $cycle ? ' selected' : '' ?>><?= cycleLabel($c) ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <button type="submit">Ver</button>
    </form>
<?php });
?>
<p class="hint">Crea las secciones de cada curso y asigna un docente a cada parte (teoría, práctica, laboratorio).
    El docente verá su carga y podrá proponer el horario que prefiere; tú confirmas los bloques en el horario de la sección.</p>

<form class="card form-grid" method="post">
    <?= csrfField() ?>
    <input type="hidden" name="period" value="<?= (int) $periodId ?>">
    <input type="hidden" name="school" value="<?= (int) $schoolId ?>">
    <input type="hidden" name="cycle" value="<?= (int) $cycle ?: '' ?>">
    <label class="wide">Curso
        <select name="course" required>
            <option value="">Elige un curso del plan</option>
            <?php foreach (groupBy($courses, 'ciclo') as $courseCycle => $cycleCourses): ?>
                <optgroup label="Ciclo <?= cycleLabel($courseCycle) ?>">
                    <?= selectOptions(array_map(fn ($c) => $c + ['label' => $c['codigo'] . ' ' . $c['nombre']], $cycleCourses),
                        'curso_id', 'label', null) ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Sección
        <input type="text" name="letra" value="A" maxlength="5" required>
    </label>
    <label class="checkbox">
        <input type="checkbox" name="extraordinaria"> Repetición (dictado extraordinario)
    </label>
    <div class="actions"><button type="submit">Crear sección</button></div>
</form>

<?php pageTools(); ?>
<div class="tree">
    <?php foreach (groupBy($courses, 'ciclo') as $courseCycle => $cycleCourses): ?>
        <?php $withSections = count(array_filter($cycleCourses, fn ($c) => isset($sections[$c['curso_id']]))); ?>
        <details class="node"<?= $cycle ? ' open' : '' ?>>
            <summary>Ciclo <?= cycleLabel($courseCycle) ?>
                <span class="count"><?= $withSections ?> de <?= count($cycleCourses) ?> cursos con sección</span></summary>
            <?php foreach ($cycleCourses as $course): ?>
                <?php $courseSections = $sections[$course['curso_id']] ?? []; ?>
                <details class="node">
                    <summary><?= e($course['codigo'] . ' ' . $course['nombre']) ?>
                        <?php if ($courseSections): ?>
                            <span class="count"><?= count($courseSections) ?> sección(es)</span>
                        <?php else: ?>
                            <span class="tag">Sin sección</span>
                        <?php endif; ?>
                    </summary>
                    <?php foreach ($courseSections as $section): ?>
                        <?php $parts = sessionHours($section);
                        $sectionLoads = $loads[$section['seccion_id']] ?? []; ?>
                        <details class="node">
                            <summary>Sección <?= e($section['letra']) ?>
                                <?= $section['extraordinaria'] ? '<span class="tag">Repetición</span>' : '' ?>
                                <span class="count"><?= count($sectionLoads) ?> de <?= count($parts) ?> parte(s) asignadas,
                                    <?= (int) $section['bloques'] ?> bloque(s)</span></summary>
                            <form class="form-grid" method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="assign">
                                <input type="hidden" name="section" value="<?= (int) $section['seccion_id'] ?>">
                                <input type="hidden" name="period" value="<?= (int) $periodId ?>">
                                <input type="hidden" name="school" value="<?= (int) $schoolId ?>">
                                <input type="hidden" name="cycle" value="<?= (int) $cycle ?: '' ?>">
                                <?php foreach ($parts as $type => $hours): ?>
                                    <?php $load = $sectionLoads[$type] ?? null; ?>
                                    <label class="wide"><?= e($type) ?>, <?= (int) $hours ?> h
                                        <?= $load && $load['propuestas'] ? '<span class="hint">(' . (int) $load['propuestas'] . ' propuesta(s) del docente)</span>' : '' ?>
                                        <select name="teacher[<?= e($type) ?>]">
                                            <?= selectOptions($teachers, 'docente_id', 'label', $load['docente_id'] ?? null, 'Sin asignar') ?>
                                        </select>
                                    </label>
                                <?php endforeach; ?>
                                <div class="actions"><button type="submit">Guardar carga</button></div>
                            </form>
                            <div class="row-actions">
                                <a href="section.php?id=<?= (int) $section['seccion_id'] ?>">Armar horario</a>
                                <form method="post" class="inline" onsubmit="return confirm('¿Eliminar la sección, su carga y todos sus bloques de horario?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="section" value="<?= (int) $section['seccion_id'] ?>">
                                    <input type="hidden" name="period" value="<?= (int) $periodId ?>">
                                    <input type="hidden" name="school" value="<?= (int) $schoolId ?>">
                                    <input type="hidden" name="cycle" value="<?= (int) $cycle ?: '' ?>">
                                    <button type="submit" class="link danger">Eliminar sección</button>
                                </form>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </details>
            <?php endforeach; ?>
        </details>
    <?php endforeach; ?>
    <?php if (!$courses): ?>
        <p class="empty">Esta escuela no tiene cursos en su plan<?= $cycle ? ' para ese ciclo' : '' ?>.</p>
    <?php endif; ?>
</div>
<?php pageFooter(); ?>
