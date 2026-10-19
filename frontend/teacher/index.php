<?php
require_once dirname(__DIR__) . '/grid.php';
$user = requireRole('Docente');

$periods = listPeriods($pdo);
$periodId = intParam($_GET, 'period') ?? (currentPeriod($pdo)['periodo_id'] ?? null);
$blocks = $periodId ? scheduleBlocks($pdo, ['periodo_id' => $periodId, 'docente_id' => $user['docente_id']]) : [];
$hours = array_sum(array_map(fn ($b) => timeToHours($b['hora_fin']) - timeToHours($b['hora_inicio']), $blocks));

pageHeader('Mi horario');
?>
<form class="filters no-print" method="get">
    <label>Periodo
        <select name="period"><?= selectOptions($periods, 'periodo_id', 'nombre', $periodId) ?></select>
    </label>
    <button type="submit">Ver</button>
</form>
<p class="count"><?= e((string) $hours) ?> hora(s) de clase por semana.</p>
<?php renderGrid($blocks, fn ($b) => [
    $b['codigo'] . ' ' . $b['curso'],
    sectionLabel($b) . ' · ' . $b['tipo_sesion'],
    'Ciclo ' . cycleLabel($b['ciclo']),
    $b['salon'],
]); ?>
<?php pageFooter(); ?>
