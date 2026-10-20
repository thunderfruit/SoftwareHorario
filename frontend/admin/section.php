<?php
require_once dirname(__DIR__) . '/grid.php';
require_once dirname(__DIR__, 2) . '/backend/teachers.php';
require_once dirname(__DIR__, 2) . '/backend/teaching_load.php';
requireRole('Administrador');

$section = getSection($pdo, intParam($_GET, 'id') ?? 0);
if (!$section) {
    http_response_code(404);
    exit('Sección no encontrada.');
}
$sectionId = $section['seccion_id'];
$self = "admin/section.php?id=$sectionId";

$form = ['tipo_sesion' => 'Teoría', 'docente_id' => null, 'salon_id' => null, 'dia_semana' => 'Lunes', 'start' => 8, 'end' => 10];
$errors = [];
$conflicts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        deleteBlock($pdo, $sectionId, intParam($_POST, 'block') ?? 0);
        flash('ok', 'Bloque eliminado.');
        redirect($self);
    }
    if ($action === 'discard') {
        deleteDesiredBlock($pdo, intParam($_POST, 'desired') ?? 0, 'seccion_id', $sectionId);
        flash('ok', 'Propuesta descartada.');
        redirect($self);
    }
    if ($action === 'confirm') {
        // a proposal becomes a block with the teacher, type, day and hours the teacher chose, in the room chosen here
        $roomId = intParam($_POST, 'salon_id');
        if (!$roomId) {
            flash('error', 'Elige el salón de la propuesta.');
            redirect($self);
        }
        try {
            $conflicts = confirmDesiredBlock($pdo, $sectionId, intParam($_POST, 'desired') ?? 0, $roomId);
            if ($conflicts === null) {
                flash('error', 'La propuesta ya no existe.');
                redirect($self);
            }
            if (!$conflicts) {
                flash('ok', 'Propuesta confirmada como bloque del horario.');
                redirect($self);
            }
        } catch (PDOException $e) {
            $errors[] = dbErrorMessage($e);
        }
    } else {
        $form = [
            'tipo_sesion' => $_POST['tipo_sesion'] ?? '',
            'docente_id' => intParam($_POST, 'docente_id'),
            'salon_id' => intParam($_POST, 'salon_id'),
            'dia_semana' => $_POST['dia_semana'] ?? '',
            'start' => intParam($_POST, 'start'),
            'end' => intParam($_POST, 'end'),
        ];
        if (!in_array($form['tipo_sesion'], SESSION_TYPES, true) || !in_array($form['dia_semana'], DAYS, true)) {
            $errors[] = 'Elige un tipo de sesión y un día válidos.';
        }
        if (!$form['docente_id'] || !$form['salon_id']) {
            $errors[] = 'Elige el docente y el salón.';
        }
        if (!$form['start'] || !$form['end'] || $form['start'] < FIRST_HOUR || $form['end'] > LAST_HOUR || $form['end'] <= $form['start']) {
            $errors[] = 'La hora de fin debe ser posterior a la de inicio.';
        }

        if (!$errors) {
            try {
                $conflicts = addBlock($pdo, [
                    'seccion_id' => $sectionId, 'periodo_id' => $section['periodo_id'],
                    'docente_id' => $form['docente_id'], 'salon_id' => $form['salon_id'],
                    'tipo_sesion' => $form['tipo_sesion'], 'dia_semana' => $form['dia_semana'],
                    'hora_inicio' => sprintf('%02d:00', $form['start']), 'hora_fin' => sprintf('%02d:00', $form['end']),
                ]);
                if (!$conflicts) {
                    flash('ok', 'Bloque agregado.');
                    redirect($self);
                }
            } catch (PDOException $e) {
                $errors[] = dbErrorMessage($e);
            }
        }
    }
}

$blocks = scheduleBlocks($pdo, ['periodo_id' => $section['periodo_id'], 'seccion_id' => $sectionId]);
$teachers = array_map(fn ($t) => $t + ['label' => $t['apellidos'] . ', ' . $t['nombres'] . ' (' . $t['codigo_docente'] . ')'],
    listAllTeachers($pdo));
$rooms = array_map(fn ($r) => $r + ['label' => $r['nombre'] . ' · ' . $r['categoria'] . ' · ' . $r['capacidad']], listRooms($pdo));

$requiredHours = ['Teoría' => $section['horas_teoria'], 'Práctica' => $section['horas_practica'], 'Laboratorio' => $section['horas_laboratorio']];
$scheduledHours = array_fill_keys(SESSION_TYPES, 0);
foreach ($blocks as $block) {
    $scheduledHours[$block['tipo_sesion']] += blockHours($block);
}
$desired = desiredBlocks($pdo, ['seccion_id' => $sectionId]);

pageHeader($section['codigo'] . ' · ' . $section['curso'] . ' · Sección ' . $section['letra']);
?>
<p class="hint">
    <?= e($section['escuela']) ?> · Ciclo <?= cycleLabel($section['ciclo']) ?> · Periodo <?= e($section['periodo']) ?>
    <?= $section['extraordinaria'] ? ' · <span class="tag">Repetición</span>' : '' ?>
    · <a href="sections.php?period=<?= (int) $section['periodo_id'] ?>&amp;school=<?= (int) $section['escuela_id'] ?>&amp;cycle=<?= (int) $section['ciclo'] ?>">Volver a la carga horaria</a>
</p>

<div class="hours">
    <?php foreach ($requiredHours as $type => $required): ?>
        <?php $done = $scheduledHours[$type]; ?>
        <span class="<?= $done == $required ? 'ok' : ($done > $required ? 'over' : 'missing') ?>">
            <?= e($type) ?>: <?= e((string) $done) ?> de <?= (int) $required ?> h
        </span>
    <?php endforeach; ?>
</div>

<?php if ($errors): ?>
    <ul class="flash error" role="alert"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
<?php if ($conflicts): ?>
    <div class="flash error" role="alert">
        <p>No se agregó el bloque porque choca con:</p>
        <ul>
            <?php foreach ($conflicts as $c): ?>
                <li><?= e(
                    ($c['mismo_salon'] ? "El salón {$c['salon']} está ocupado" : "El docente {$c['docente']} ya dicta")
                    . " por {$c['codigo']} {$c['curso']} (secc. {$c['letra']}), " . blockTime($c)
                    . ($c['mismo_salon'] && $c['mismo_docente'] ? ', con el mismo docente' : '')
                ) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($desired): ?>
    <h2>Horario deseado por los docentes</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Propuesta</th><th>Tipo</th><th>Docente</th><th>Salón</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($desired as $proposal): ?>
                <tr>
                    <td><?= e(blockTime($proposal)) ?></td>
                    <td><?= e($proposal['tipo_sesion']) ?></td>
                    <td><?= e($proposal['docente']) ?></td>
                    <td>
                        <form method="post" class="inline-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="confirm">
                            <input type="hidden" name="desired" value="<?= (int) $proposal['horario_deseado_id'] ?>">
                            <select name="salon_id" required aria-label="Salón para la propuesta"><?= selectOptions($rooms, 'salon_id', 'label', null, 'Elige un salón') ?></select>
                            <button type="submit">Confirmar</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" class="inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="discard">
                            <input type="hidden" name="desired" value="<?= (int) $proposal['horario_deseado_id'] ?>">
                            <button type="submit" class="link danger">Descartar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h2>Agregar bloque</h2>
<?php if (!$rooms): ?>
    <p class="flash error">No hay salones registrados. <a href="rooms.php">Registra uno</a> antes de armar el horario.</p>
<?php else: ?>
<form class="card form-grid" method="post">
    <?= csrfField() ?>
    <label>Tipo de sesión
        <select name="tipo_sesion">
            <?php foreach (SESSION_TYPES as $type): ?>
                <option<?= $type === $form['tipo_sesion'] ? ' selected' : '' ?>><?= e($type) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="wide">Docente
        <select name="docente_id" required><?= selectOptions($teachers, 'docente_id', 'label', $form['docente_id'], 'Elige un docente') ?></select>
    </label>
    <label class="wide">Salón
        <select name="salon_id" required><?= selectOptions($rooms, 'salon_id', 'label', $form['salon_id'], 'Elige un salón') ?></select>
    </label>
    <label>Día
        <select name="dia_semana">
            <?php foreach (DAY_LABELS as $day => $label): ?>
                <option value="<?= e($day) ?>"<?= $day === $form['dia_semana'] ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Desde
        <select name="start">
            <?php for ($h = FIRST_HOUR; $h < LAST_HOUR; $h++): ?>
                <option value="<?= $h ?>"<?= $h === $form['start'] ? ' selected' : '' ?>><?= sprintf('%02d:00', $h) ?></option>
            <?php endfor; ?>
        </select>
    </label>
    <label>Hasta
        <select name="end">
            <?php for ($h = FIRST_HOUR + 1; $h <= LAST_HOUR; $h++): ?>
                <option value="<?= $h ?>"<?= $h === $form['end'] ? ' selected' : '' ?>><?= sprintf('%02d:00', $h) ?></option>
            <?php endfor; ?>
        </select>
    </label>
    <div class="actions"><button type="submit">Agregar bloque</button></div>
</form>
<?php endif; ?>

<div class="table-wrap">
    <table>
        <thead><tr><th>Horario</th><th>Tipo</th><th>Docente</th><th>Salón</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($blocks as $block): ?>
            <tr>
                <td><?= e(blockTime($block)) ?></td>
                <td><?= e($block['tipo_sesion']) ?></td>
                <td><?= e($block['docente']) ?></td>
                <td><?= e($block['salon']) ?></td>
                <td>
                    <form method="post" class="inline" onsubmit="return confirm('¿Quitar este bloque del horario?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="block" value="<?= (int) $block['horario_detalle_id'] ?>">
                        <button type="submit" class="link danger">Quitar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$blocks): ?>
            <tr><td colspan="5" class="empty">Esta sección todavía no tiene bloques de horario.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php pageFooter(); ?>
