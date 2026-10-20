<?php
require_once __DIR__ . '/grid.php';

$periods = listPeriods($pdo);
$campuses = listCampuses($pdo);
$roomTypes = listRoomTypes($pdo);
$periodId = intParam($_GET, 'period') ?? (currentPeriod($pdo)['periodo_id'] ?? null);
$filters = ['sede_id' => intParam($_GET, 'campus'), 'categoria_salon_id' => intParam($_GET, 'type')];
$roomChoices = listRooms($pdo, $filters);
$roomId = intParam($_GET, 'room');
// a room left over from another campus or type is ignored instead of emptying the page
$selected = array_values(array_filter($roomChoices, fn ($r) => $r['salon_id'] === $roomId));
$roomId = $selected ? $roomId : null;
$rooms = $selected ?: $roomChoices;

$blocks = $periodId ? groupBy(scheduleBlocks($pdo, ['periodo_id' => $periodId, 'salon_id' => $roomId] + $filters), 'salon_id') : [];

pageHeader('Horario por salón', function () use ($periods, $periodId, $campuses, $roomTypes, $filters, $roomChoices, $roomId) { ?>
    <form class="filters no-print" method="get">
        <label>Periodo
            <select name="period"><?= selectOptions($periods, 'periodo_id', 'nombre', $periodId) ?></select>
        </label>
        <label>Sede
            <select name="campus"><?= selectOptions($campuses, 'sede_id', 'nombre', $filters['sede_id'], 'Todas') ?></select>
        </label>
        <label>Tipo de salón
            <select name="type"><?= selectOptions($roomTypes, 'categoria_salon_id', 'nombre', $filters['categoria_salon_id'], 'Todos') ?></select>
        </label>
        <label>Salón
            <select name="room"><?= selectOptions($roomChoices, 'salon_id', 'nombre', $roomId, 'Todos') ?></select>
        </label>
        <button type="submit">Ver horario</button>
    </form>
<?php });
?>
<?php if (!$rooms): ?>
    <p class="empty">No hay salones registrados para esta selección.</p>
<?php else: ?>
    <?php pageTools(); ?>
    <div class="tree">
        <?php foreach (groupBy($rooms, 'sede') as $campus => $campusRooms): ?>
            <details class="node"<?= $filters['sede_id'] || $roomId ? ' open' : '' ?>>
                <summary>Sede <?= e($campus) ?> <span class="count"><?= count($campusRooms) ?> salón(es)</span></summary>
                <?php foreach (groupBy($campusRooms, 'categoria') as $type => $typeRooms): ?>
                    <details class="node"<?= $filters['categoria_salon_id'] || $roomId ? ' open' : '' ?>>
                        <summary><?= e($type) ?> <span class="count"><?= count($typeRooms) ?> salón(es)</span></summary>
                        <?php foreach ($typeRooms as $room): ?>
                            <?php $roomBlocks = $blocks[$room['salon_id']] ?? []; ?>
                            <details class="node"<?= $roomId ? ' open' : '' ?>>
                                <summary><?= e($room['nombre']) ?>
                                    <span class="count">capacidad <?= (int) $room['capacidad'] ?> ·
                                        <?= formatHours(array_sum(array_map('blockHours', $roomBlocks))) ?> h ocupadas</span></summary>
                                <?php renderGrid($roomBlocks, fn ($b) => [
                                    $b['codigo'] . ' ' . $b['curso'],
                                    sectionLabel($b) . ' · ' . $b['tipo_sesion'],
                                    $b['docente'],
                                ]); ?>
                            </details>
                        <?php endforeach; ?>
                    </details>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php pageFooter(); ?>
