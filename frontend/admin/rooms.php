<?php
require_once dirname(__DIR__) . '/grid.php';
requireRole('Administrador');

$roomTypes = listRoomTypes($pdo);
$schools = $pdo->query('SELECT e.escuela_id, e.nombre || \' (\' || f.nombre || \')\' AS nombre
                        FROM public.escuela e JOIN public.facultad f USING (facultad_id) ORDER BY e.nombre')->fetchAll();
$campuses = $pdo->query('SELECT * FROM public.sede ORDER BY sede_id')->fetchAll();

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

$rooms = listRooms($pdo);
pageHeader('Salones');
?>
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

<div class="table-wrap">
    <table>
        <thead><tr><th>Nombre</th><th>Tipo</th><th>Capacidad</th><th>Escuela</th><th>Sede</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?= e($room['nombre']) ?></td>
                <td><?= e($room['categoria']) ?></td>
                <td><?= (int) $room['capacidad'] ?></td>
                <td><?= e($room['escuela']) ?></td>
                <td><?= e($room['sede']) ?></td>
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
        <?php if (!$rooms): ?>
            <tr><td colspan="6" class="empty">Todavía no hay salones registrados.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php pageFooter(); ?>
