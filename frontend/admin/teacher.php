<?php
require_once dirname(__DIR__) . '/layout.php';
require_once dirname(__DIR__, 2) . '/backend/teachers.php';
require_once dirname(__DIR__, 2) . '/backend/schedule.php';
requireRole('Administrador');

$period = currentPeriod($pdo);
if (!$period) {
    exit('Registra al menos un periodo académico antes de gestionar docentes.');
}
$catalogs = teacherCatalogs($pdo);
$teacherId = intParam($_GET, 'id');
$teacher = $teacherId ? getTeacher($pdo, $teacherId, $period['periodo_id']) : null;
if ($teacherId && !$teacher) {
    http_response_code(404);
    exit('Docente no encontrado.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $text = fn ($key) => trim($_POST[$key] ?? '');
    $teacher = [
        'codigo_docente' => $text('codigo_docente'),
        'apellidos' => $text('apellidos'),
        'nombres' => $text('nombres'),
        'tipo_documento' => $text('tipo_documento') === 'CE' ? 'CE' : 'DNI',
        'numero_documento' => strtoupper($text('numero_documento')) ?: null,
        'departamento_id' => intParam($_POST, 'departamento_id'),
        'grado_academico_id' => intParam($_POST, 'grado_academico_id'),
        'categoria_id' => intParam($_POST, 'categoria_id'),
        'modalidad_id' => intParam($_POST, 'modalidad_id'),
        'horas_asignadas' => intParam($_POST, 'horas_asignadas'),
    ];

    foreach (['codigo_docente' => 'el código', 'apellidos' => 'los apellidos', 'nombres' => 'los nombres'] as $key => $label) {
        if ($teacher[$key] === '') {
            $errors[] = "Falta $label.";
        }
    }
    $documentPattern = $teacher['tipo_documento'] === 'DNI' ? '/^[0-9]{8}$/' : '/^[0-9A-Z]{9,12}$/';
    if ($teacher['numero_documento'] !== null && !preg_match($documentPattern, $teacher['numero_documento'])) {
        $errors[] = $teacher['tipo_documento'] === 'DNI'
            ? 'El DNI debe tener 8 dígitos.'
            : 'El carné de extranjería debe tener de 9 a 12 letras o dígitos.';
    }
    if ($teacher['modalidad_id'] !== null) {
        if ($teacher['categoria_id'] === null) {
            $errors[] = 'Para asignar modalidad y horas el docente necesita una categoría.';
        }
        if ($teacher['horas_asignadas'] === null || $teacher['horas_asignadas'] < 8 || $teacher['horas_asignadas'] > 40) {
            $errors[] = 'Las horas asignadas deben estar entre 8 y 40.';
        }
    }

    if (!$errors) {
        try {
            saveTeacher($pdo, $teacherId, $teacher, $period['periodo_id']);
            flash('ok', 'Docente guardado.');
            redirect('admin/teachers.php');
        } catch (PDOException $e) {
            $errors[] = dbErrorMessage($e);
        }
    }
}

$value = fn ($key) => $teacher[$key] ?? null;
pageHeader($teacherId ? 'Editar docente' : 'Nuevo docente');
?>
<?php if ($errors): ?>
    <ul class="flash error" role="alert"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
<form class="card form-grid" method="post">
    <?= csrfField() ?>
    <label>Código docente *
        <input type="text" name="codigo_docente" maxlength="20" required value="<?= e($value('codigo_docente'), '') ?>">
    </label>
    <label>Apellidos *
        <input type="text" name="apellidos" maxlength="150" required value="<?= e($value('apellidos'), '') ?>">
    </label>
    <label>Nombres *
        <input type="text" name="nombres" maxlength="150" required value="<?= e($value('nombres'), '') ?>">
    </label>
    <label>Tipo de documento
        <select name="tipo_documento">
            <option value="DNI">DNI</option>
            <option value="CE"<?= $value('tipo_documento') === 'CE' ? ' selected' : '' ?>>Carné de extranjería</option>
        </select>
    </label>
    <label>N.º documento
        <input type="text" name="numero_documento" maxlength="12" value="<?= e($value('numero_documento'), '') ?>">
    </label>
    <label>Departamento
        <select name="departamento_id">
            <option value="">Sin asignar</option>
            <?php foreach (array_unique(array_column($catalogs['departments'], 'facultad')) as $faculty): ?>
                <optgroup label="<?= e($faculty) ?>">
                    <?= selectOptions(array_filter($catalogs['departments'], fn ($d) => $d['facultad'] === $faculty),
                        'departamento_id', 'nombre', $value('departamento_id')) ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Grado académico
        <select name="grado_academico_id"><?= selectOptions($catalogs['degrees'], 'grado_academico_id', 'nombre', $value('grado_academico_id'), 'Sin registrar') ?></select>
    </label>
    <label>Categoría actual
        <select name="categoria_id"><?= selectOptions($catalogs['categories'], 'categoria_id', 'nombre', $value('categoria_id'), 'Sin categoría') ?></select>
    </label>
    <fieldset>
        <legend>Asignación en <?= e($period['nombre']) ?></legend>
        <label>Modalidad
            <select name="modalidad_id"><?= selectOptions($catalogs['modalities'], 'modalidad_id', 'nombre', $value('modalidad_id'), 'Sin asignación') ?></select>
        </label>
        <label>Horas semanales
            <input type="number" name="horas_asignadas" min="8" max="40" value="<?= e($value('horas_asignadas') === null ? null : (string) $value('horas_asignadas'), '') ?>">
        </label>
    </fieldset>
    <div class="actions">
        <button type="submit">Guardar</button>
        <a class="button secondary" href="teachers.php">Cancelar</a>
    </div>
</form>
<?php pageFooter(); ?>
