<?php
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__) . '/backend/schedule.php';

const SESSION_CLASSES = ['Teoría' => 'teoria', 'Práctica' => 'practica', 'Laboratorio' => 'laboratorio'];

/**
 * Weekly grid, one row per hour. A block shows in every hour it covers (in full in its first hour,
 * abbreviated after), so overlapping blocks (e.g. sections A and B at the same time) simply stack in the same cell.
 * $describe returns the plain-text lines shown for a block.
 */
function renderGrid(array $blocks, callable $describe): void
{
    if (!$blocks) {
        echo '<p class="empty">No hay clases registradas para esta selección.</p>';
        return;
    }
    $days = array_values(array_filter(DAYS, fn ($day) => $day !== 'Sabado'
        || in_array('Sabado', array_column($blocks, 'dia_semana'), true)));
    $from = min(array_map(fn ($b) => (int) substr($b['hora_inicio'], 0, 2), $blocks));
    $to = max(array_map(fn ($b) => (int) ceil(timeToHours($b['hora_fin'])), $blocks));
    ?>
    <ul class="legend" aria-label="Tipos de sesión">
        <?php foreach (SESSION_CLASSES as $type => $class): ?>
            <li class="<?= $class ?>"><?= e($type) ?></li>
        <?php endforeach; ?>
    </ul>
    <div class="table-wrap">
        <table class="grid">
            <thead>
            <tr><th scope="col">Hora</th><?php foreach ($days as $day): ?><th scope="col"><?= e(DAY_LABELS[$day]) ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
            <?php for ($hour = $from; $hour < $to; $hour++): ?>
                <tr>
                    <th scope="row"><?= sprintf('%02d:00–%02d:00', $hour, $hour + 1) ?></th>
                    <?php foreach ($days as $day): ?>
                        <td>
                            <?php foreach ($blocks as $block):
                                if ($block['dia_semana'] !== $day || timeToHours($block['hora_inicio']) >= $hour + 1
                                    || timeToHours($block['hora_fin']) <= $hour) {
                                    continue;
                                } ?>
                                <?php $continues = timeToHours($block['hora_inicio']) < $hour; ?>
                                <div class="block <?= SESSION_CLASSES[$block['tipo_sesion']] ?><?= $continues ? ' continues' : '' ?>">
                                    <?= $continues
                                        ? e($describe($block)[0]) . ' <span class="hint">(continúa)</span>'
                                        : implode('<br>', array_map('e', $describe($block))) ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function timeToHours(string $time): float
{
    [$h, $m] = explode(':', $time);
    return (int) $h + (int) $m / 60;
}

function blockHours(array $block): float
{
    return timeToHours($block['hora_fin']) - timeToHours($block['hora_inicio']);
}

// Hours as shown on screen: "2", "1.5".
function formatHours(float $hours): string
{
    return rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.');
}

function cycleLabel(int $cycle): string
{
    return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$cycle - 1];
}

function sectionLabel(array $block): string
{
    return 'Sección ' . $block['letra'] . ($block['extraordinaria'] ? ' (repetición)' : '');
}

function blockTime(array $block): string
{
    return DAY_LABELS[$block['dia_semana']] . ' ' . substr($block['hora_inicio'], 0, 5) . '–' . substr($block['hora_fin'], 0, 5);
}
