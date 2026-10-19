<?php
require_once __DIR__ . '/grid.php';

$periods = listPeriods($pdo);
$schools = listSchoolsWithPlan($pdo);
$periodId = intParam($_GET, 'period') ?? (currentPeriod($pdo)['periodo_id'] ?? null);
$schoolId = intParam($_GET, 'school') ?? ($schools[0]['escuela_id'] ?? null);
$school = current(array_filter($schools, fn ($s) => $s['escuela_id'] === $schoolId)) ?: null;
$lastCycle = $school['ultimo_ciclo'] ?? 1;
$cycle = min(intParam($_GET, 'cycle') ?? 1, $lastCycle);

$blocks = $periodId && $school
    ? scheduleBlocks($pdo, ['periodo_id' => $periodId, 'escuela_id' => $schoolId, 'ciclo' => $cycle])
    : [];

pageHeader('Horario por escuela');
?>
<form class="filters no-print" method="get">
    <label>Periodo
        <select name="period"><?= selectOptions($periods, 'periodo_id', 'nombre', $periodId) ?></select>
    </label>
    <label>Escuela
        <select name="school"><?= selectOptions($schools, 'escuela_id', 'nombre', $schoolId) ?></select>
    </label>
    <label>Ciclo
        <select name="cycle">
            <?php for ($c = 1; $c <= $lastCycle; $c++): ?>
                <option value="<?= $c ?>"<?= $c === $cycle ? ' selected' : '' ?>><?= cycleLabel($c) ?></option>
            <?php endfor; ?>
        </select>
    </label>
    <button type="submit">Ver horario</button>
</form>

<?php if ($school): ?>
    <h2><?= e($school['nombre']) ?> · Ciclo <?= cycleLabel($cycle) ?></h2>
<?php endif; ?>
<?php renderGrid($blocks, fn ($b) => [
    $b['codigo'] . ' ' . $b['curso'],
    sectionLabel($b) . ' · ' . $b['tipo_sesion'],
    $b['docente'],
    $b['salon'],
]); ?>
<p class="hint no-print">Las secciones marcadas como <strong>Repitencia</strong> se dictan de manera extraordinaria para quienes llevan el curso nuevamente.</p>
<?php pageFooter(); ?>
