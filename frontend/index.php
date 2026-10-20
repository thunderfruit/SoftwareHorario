<?php
require_once __DIR__ . '/grid.php';

$periods = listPeriods($pdo);
$schools = listSchoolsWithPlan($pdo);
$periodId = intParam($_GET, 'period') ?? (currentPeriod($pdo)['periodo_id'] ?? null);
$schoolId = intParam($_GET, 'school');
$school = current(array_filter($schools, fn ($s) => $s['escuela_id'] === $schoolId)) ?: null;
$lastCycle = $school['ultimo_ciclo'] ?? max(array_column($schools, 'ultimo_ciclo') ?: [1]);
$cycle = intParam($_GET, 'cycle');
$cycle = $cycle <= $lastCycle ? $cycle : null;

$blocks = $periodId
    ? scheduleBlocks($pdo, ['periodo_id' => $periodId, 'escuela_id' => $school['escuela_id'] ?? null, 'ciclo' => $cycle])
    : [];
$bySchool = groupBy($blocks, 'escuela');
ksort($bySchool);

pageHeader('Horario', function () use ($periods, $periodId, $schools, $school, $lastCycle, $cycle) { ?>
    <form class="filters no-print" method="get">
        <label>Periodo
            <select name="period"><?= selectOptions($periods, 'periodo_id', 'nombre', $periodId) ?></select>
        </label>
        <label>Escuela
            <select name="school"><?= selectOptions($schools, 'escuela_id', 'nombre', $school['escuela_id'] ?? null, 'Todas') ?></select>
        </label>
        <label>Ciclo
            <select name="cycle">
                <option value="">Todos</option>
                <?php for ($c = 1; $c <= $lastCycle; $c++): ?>
                    <option value="<?= $c ?>"<?= $c === $cycle ? ' selected' : '' ?>><?= cycleLabel($c) ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <button type="submit">Ver horario</button>
    </form>
<?php });
?>
<?php if (!$blocks): ?>
    <p class="empty">No hay clases registradas para esta selección.</p>
<?php else: ?>
    <?php pageTools(); ?>
    <div class="tree">
        <?php foreach ($bySchool as $schoolName => $schoolBlocks): ?>
            <?php $byCycle = groupBy($schoolBlocks, 'ciclo');
            ksort($byCycle); ?>
            <details class="node"<?= $school || count($bySchool) === 1 ? ' open' : '' ?>>
                <summary>Escuela de <?= e($schoolName) ?> <span class="count"><?= count($byCycle) ?> ciclo(s)</span></summary>
                <?php foreach ($byCycle as $cycleNumber => $cycleBlocks): ?>
                    <?php $byCourse = groupBy($cycleBlocks, 'codigo');
                    ksort($byCourse); ?>
                    <details class="node"<?= $cycle ? ' open' : '' ?>>
                        <summary>Ciclo <?= cycleLabel($cycleNumber) ?> <span class="count"><?= count($byCourse) ?> curso(s)</span></summary>
                        <?php renderGrid($cycleBlocks, fn ($b) => [
                            $b['codigo'] . ' ' . $b['curso'],
                            sectionLabel($b) . ', ' . $b['tipo_sesion'],
                            $b['docente'],
                            $b['salon'],
                        ]); ?>
                        <?php foreach ($byCourse as $courseBlocks): ?>
                            <?php $bySection = groupBy($courseBlocks, 'letra');
                            ksort($bySection); ?>
                            <details class="node">
                                <summary><?= e($courseBlocks[0]['codigo'] . ' ' . $courseBlocks[0]['curso']) ?>
                                    <span class="count"><?= count($bySection) ?> sección(es)</span></summary>
                                <?php foreach ($bySection as $sectionBlocks): ?>
                                    <details class="node">
                                        <summary><?= e(sectionLabel($sectionBlocks[0])) ?></summary>
                                        <ul class="block-list">
                                            <?php foreach ($sectionBlocks as $b): ?>
                                                <li><strong><?= e(blockTime($b)) ?></strong>
                                                    <?= e($b['tipo_sesion']) ?> con <?= e($b['docente']) ?>, en <?= e($b['salon']) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </details>
                                <?php endforeach; ?>
                            </details>
                        <?php endforeach; ?>
                    </details>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<p class="hint no-print">Las secciones marcadas como <strong>Repetición</strong> se dictan de manera extraordinaria para quienes llevan el curso nuevamente.</p>
<?php pageFooter(); ?>
