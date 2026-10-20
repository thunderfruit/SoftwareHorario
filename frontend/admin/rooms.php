<?php
require_once dirname(__DIR__) . '/grid.php';
requireRole('Administrador');

$roomTypes = listRoomTypes($pdo);
$schools = $pdo->query('SELECT e.escuela_id, e.nombre || \' (\' || f.nombre || \')\' AS nombre
                        FROM public.escuela e JOIN public.facultad f USING (facultad_id) ORDER BY e.nombre')->fetchAll();
$campuses = listCampuses($pdo);
$filters = [
    'sede_id' => intParam($_GET, 'campus'),
    'categoria_salon_id' => intParam($_GET, 'type'),
    'escuela_id' => intParam($_GET, 'school'),
    'nombre' => trim($_GET['name'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    try {
        if (($_POST['action'] ?? '') === 'delete') {
            deleteRoom($pdo, intParam($_POST, 'room') ?? 0);
            flash('ok', 'Salón eliminado.');
        } else {
            $room = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'categoria_salon_id' => intParam($_POST, 'categoria_salon_id'),
                'escuela_id' => intParam($_POST, 'escuela_id'),
                'sede_id' => intParam($_POST, 'sede_id'),
                'capacidad' => intParam($_POST, 'capacidad'),
            ];
            if (in_array(null, $room, true) || $room['nombre'] === '') {
                flash('error', 'Completa todos los campos; la capacidad debe ser mayor que cero.');
            } else {
                createRoom($pdo, $room);
                flash('ok', 'Salón registrado.');
            }
        }
    } catch (PDOException $e) {
        flash('error', $e->getCode() === '23503' ? 'No se puede eliminar: el salón tiene clases programadas.' : dbErrorMessage($e));
    }
    redirect('admin/rooms.php');
}

$rooms = listRooms($pdo, $filters);
$filtered = array_filter($filters, fn ($v) => $v !== '' && $v !== null) !== [];
pageHeader('Salones');
?>
<h2>Registrar salón</h2>
<form class="card form-grid" method="post">
    <?= csrfField() ?>
    <label>Nombre
        <input type="text" name="nombre" maxlength="100" required placeholder="Lab. 3 / Aula 201">
    </label>
    <label>Tipo
        <select name="categoria_salon_id" required><?= selectOptions($roomTypes, 'categoria_salon_id', 'nombre', null, 'Elige el tipo') ?></select>
    </label>
    <label>Capacidad
        <input type="number" name="capacidad" min="1" required>
    </label>
    <label class="wide">Escuela a cargo
        <select name="escuela_id" required><?= selectOptions($schools, 'escuela_id', 'nombre', null, 'Elige la escuela') ?></select>
    </label>
    <label>Sede
        <select name="sede_id" required><?= selectOptions($campuses, 'sede_id', 'nombre', $campuses[0]['sede_id'] ?? null) ?></select>
    </label>
    <div class="actions"><button type="submit">Registrar salón</button></div>
</form>

<h2>Salones registrados</h2>
<form class="filters" method="get">
    <label>Nombre
        <input type="text" name="name" value="<?= e($filters['nombre'], '') ?>">
    </label>
    <label>Sede
        <select name="campus"><?= selectOptions($campuses, 'sede_id', 'nombre', $filters['sede_id'], 'Todas') ?></select>
    </label>
    <label>Tipo
        <select name="type"><?= selectOptions($roomTypes, 'categoria_salon_id', 'nombre', $filters['categoria_salon_id'], 'Todos') ?></select>
    </label>
    <label class="wide">Escuela a cargo
        <select name="school"><?= selectOptions($schools, 'escuela_id', 'nombre', $filters['escuela_id'], 'Todas') ?></select>
    </label>
    <div class="actions">
        <button type="submit">Buscar</button>
        <a class="button secondary" href="?">Mostrar todos</a>
    </div>
</form>

<?php if (!$rooms): ?>
    <p class="empty"><?= $filtered ? 'No hay salones con esos criterios.' : 'Todavía no hay salones registrados.' ?></p>
<?php else: ?>
    <?php pageTools(); ?>
    <div class="tree">
        <?php foreach (groupBy($rooms, 'sede') as $campus => $campusRooms): ?>
            <details class="node"<?= $filtered ? ' open' : '' ?>>
                <summary>Sede <?= e($campus) ?> <span class="count"><?= count($campusRooms) ?> salón(es)</span></summary>
                <?php foreach (groupBy($campusRooms, 'categoria') as $type => $typeRooms): ?>
                    <details class="node"<?= $filtered ? ' open' : '' ?>>
                        <summary><?= e($type) ?> <span class="count"><?= count($typeRooms) ?> salón(es)</span></summary>
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>Nombre</th><th>Capacidad</th><th>Escuela a cargo</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($typeRooms as $room): ?>
                                    <tr>
                                        <td><?= e($room['nombre']) ?></td>
                                        <td><?= (int) $room['capacidad'] ?></td>
                                        <td><?= e($room['escuela']) ?></td>
                                        <td>
                                            <form method="post" class="inline" onsubmit="return confirm('¿Eliminar este salón?')">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="room" value="<?= (int) $room['salon_id'] ?>">
                                                <button type="submit" class="link danger">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php pageFooter(); ?>
