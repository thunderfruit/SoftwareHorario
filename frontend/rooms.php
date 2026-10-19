<?php
require_once __DIR__ . '/grid.php';

$periods = listPeriods($pdo);
$roomTypes = listRoomTypes($pdo);
$periodId = intParam($_GET, 'period') ?? (currentPeriod($pdo)['periodo_id'] ?? null);
$roomTypeId = intParam($_GET, 'type');
$rooms = listRooms($pdo, $roomTypeId);
$roomId = intParam($_GET, 'room');
$room = current(array_filter($rooms, fn ($r) => $r['salon_id'] === $roomId)) ?: null;

$blocks = $periodId && $room ? scheduleBlocks($pdo, ['periodo_id' => $periodId, 'salon_id' => $roomId]) : [];

pageHeader('Horario por salón');
?>
<form class="filters no-print" method="get">
    <label>Periodo
        <select name="period"><?= selectOptions($periods, 'periodo_id', 'nombre', $periodId) ?></select>
    </label>
    <label>Tipo de salón
        <select name="type"><?= selectOptions($roomTypes, 'categoria_salon_id', 'nombre', $roomTypeId, 'Todos') ?></select>
    </label>
    <label>Salón
        <select name="room"><?= selectOptions($rooms, 'salon_id', 'nombre', $roomId, 'Elige un salón') ?></select>
    </label>
    <button type="submit">Ver horario</button>
</form>

<?php if (!$rooms): ?>
    <p class="empty">No hay salones registrados<?= $roomTypeId ? ' de este tipo' : '' ?>.</p>
<?php elseif (!$room): ?>
    <p class="hint">Elige un salón para ver su ocupación. Usa el tipo de salón para ver solo laboratorios, aulas o auditorios.</p>
<?php else: ?>
    <h2><?= e($room['nombre']) ?> · <?= e($room['categoria']) ?> · capacidad <?= (int) $room['capacidad'] ?></h2>
    <?php renderGrid($blocks, fn ($b) => [
        $b['codigo'] . ' ' . $b['curso'],
        sectionLabel($b) . ' · ' . $b['tipo_sesion'],
        $b['docente'],
    ]); ?>
<?php endif; ?>
<?php pageFooter(); ?>
