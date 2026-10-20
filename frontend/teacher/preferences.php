<?php
require_once dirname(__DIR__) . '/grid.php';
require_once dirname(__DIR__, 2) . '/backend/teaching_load.php';
$user = requireRole('Docente');
$teacherId = $user['docente_id'];

$periods = listPeriods($pdo);
$periodId = intParam($_REQUEST, 'period') ?? (currentPeriod($pdo)['periodo_id'] ?? null);
$self = "teacher/preferences.php?period=$periodId";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'delete') {
        deleteDesiredBlock($pdo, intParam($_POST, 'desired') ?? 0, 'docente_id', $teacherId);
        flash('ok', 'Propuesta eliminada.');
        redirect($self);
    }
    $day = $_POST['dia_semana'] ?? '';
    $start = intParam($_POST, 'start');
    $end = intParam($_POST, 'end');
    if (!in_array($day, DAYS, true) || !$start || !$end || $start < FIRST_HOUR || $end > LAST_HOUR || $end <= $start) {
        flash('error', 'Elige un día válido y una hora de fin posterior a la de inicio.');
    } elseif (!addDesiredBlock($pdo, $teacherId, intParam($_POST, 'load') ?? 0, $day, sprintf('%02d:00', $start), sprintf('%02d:00', $end))) {
        flash('error', 'Esa parte del curso no está asignada a ti.');
    } else {
        flash('ok', 'Propuesta registrada. El administrador la revisará al armar el horario.');
    }
    redirect($self);
}

$loads = $periodId ? teacherLoads($pdo, $teacherId, $periodId) : [];
$desired = $periodId ? desiredBlocks($pdo, ['docente_id' => $teacherId, 'periodo_id' => $periodId]) : [];
$desiredByLoad = groupBy($desired, 'carga_horaria_id');

pageHeader('Horario deseado', function () use ($periods, $periodId) { ?>
    <form class="filters no-print" method="get">
        <label>Periodo
            <select name="period"><?= selectOptions($periods, 'periodo_id', 'nombre', $periodId) ?></select>
        </label>
        <button type="submit">Ver</button>
    </form>
<?php });
?>
<p class="hint">Propón el día y la hora en que prefieres dictar cada parte de tu carga horaria. Es solo una preferencia:
    el administrador arma el horario definitivo, que verás en <a href="index.php">Mi horario</a>.</p>

<?php if (!$loads): ?>
    <p class="empty">Todavía no tienes carga horaria asignada en este periodo.</p>
<?php else: ?>
    <?php pageTools(); ?>
    <div class="tree">
        <?php foreach (groupBy($loads, 'escuela') as $school => $schoolLoads): ?>
            <details class="node" open>
                <summary>Escuela de <?= e($school) ?> <span class="count"><?= count($schoolLoads) ?> parte(s)</span></summary>
                <?php foreach ($schoolLoads as $load): ?>
                    <?php $proposals = $desiredByLoad[$load['carga_horaria_id']] ?? [];
                    $proposed = array_sum(array_map('blockHours', $proposals)); ?>
                    <details class="node"<?= $proposed < $load['horas'] ? ' open' : '' ?>>
                        <summary><?= e($load['codigo'] . ' ' . $load['curso']) ?>
                            <span class="count"><?= e(sectionLabel($load) . ', ' . $load['tipo_sesion']) ?>, ciclo <?= cycleLabel($load['ciclo']) ?>.
                                Propuestas <?= formatHours($proposed) ?> de <?= (int) $load['horas'] ?> h</span></summary>
                        <ul class="block-list">
                            <?php foreach ($proposals as $proposal): ?>
                                <li><?= e(blockTime($proposal)) ?>
                                    <form method="post" class="inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="desired" value="<?= (int) $proposal['horario_deseado_id'] ?>">
                                        <button type="submit" class="link danger">Quitar</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <form class="form-grid" method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="load" value="<?= (int) $load['carga_horaria_id'] ?>">
                            <label>Día
                                <select name="dia_semana">
                                    <?php foreach (DAY_LABELS as $day => $label): ?>
                                        <option value="<?= e($day) ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Desde
                                <select name="start">
                                    <?php for ($h = FIRST_HOUR; $h < LAST_HOUR; $h++): ?>
                                        <option value="<?= $h ?>"<?= $h === 8 ? ' selected' : '' ?>><?= sprintf('%02d:00', $h) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </label>
                            <label>Hasta
                                <select name="end">
                                    <?php for ($h = FIRST_HOUR + 1; $h <= LAST_HOUR; $h++): ?>
                                        <option value="<?= $h ?>"<?= $h === 10 ? ' selected' : '' ?>><?= sprintf('%02d:00', $h) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </label>
                            <div class="actions"><button type="submit">Proponer</button></div>
                        </form>
                    </details>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
    </div>

    <h2>Tu propuesta en la semana</h2>
    <?php renderGrid($desired, fn ($b) => [
        $b['codigo'] . ' ' . $b['curso'],
        sectionLabel($b) . ', ' . $b['tipo_sesion'],
    ]); ?>
<?php endif; ?>
<?php pageFooter(); ?>
