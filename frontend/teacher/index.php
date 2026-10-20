<?php
require_once dirname(__DIR__) . '/grid.php';
$user = requireRole('Docente');

$periods = listPeriods($pdo);
$periodId = intParam($_GET, 'period') ?? (currentPeriod($pdo)['periodo_id'] ?? null);
$blocks = $periodId ? scheduleBlocks($pdo, ['periodo_id' => $periodId, 'docente_id' => $user['docente_id']]) : [];
$hours = array_sum(array_map('blockHours', $blocks));

pageHeader('Mi horario', function () use ($periods, $periodId) { ?>
    <form class="filters no-print" method="get">
        <label>Periodo
            <select name="period"><?= selectOptions($periods, 'periodo_id', 'nombre', $periodId) ?></select>
        </label>
        <button type="submit">Ver</button>
    </form>
<?php });
?>
<p class="count"><?= formatHours($hours) ?> hora(s) de clase por semana.</p>
<?php pageTools(false); ?>
<?php renderGrid($blocks, fn ($b) => [
    $b['codigo'] . ' ' . $b['curso'],
    sectionLabel($b) . ' · ' . $b['tipo_sesion'],
    'Ciclo ' . cycleLabel($b['ciclo']),
    $b['salon'],
]); ?>
<?php pageFooter(); ?>
