<?php
// Run: php tests/schedule_test.php
// Uses the configured database; every row it creates is removed at the end, even on failure.
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/frontend/index.php';
require_once dirname(__DIR__) . '/frontend/grid.php';
require_once dirname(__DIR__) . '/backend/teachers.php';
require_once dirname(__DIR__) . '/backend/teaching_load.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("FALLÓ: $message");
    }
    echo "ok - $message\n";
}

$period = currentPeriod($pdo);
$school = $pdo->query("SELECT escuela_id FROM escuela WHERE nombre = 'Informática'")->fetchColumn();
$course = fn ($code) => (int) $pdo->query("SELECT curso_id FROM curso WHERE codigo = '$code'")->fetchColumn();
$created = ['rooms' => [], 'sections' => [], 'teachers' => [], 'users' => []];

try {
    $pdo->prepare("INSERT INTO usuario (nombre_usuario, password_hash, rol) VALUES ('test-admin', ?, 'Administrador')")
        ->execute([password_hash('clave-de-prueba', PASSWORD_DEFAULT)]);
    $created['users'][] = 'test-admin';
    // all logins run before any output: session_regenerate_id() refuses to run once output has started
    $wrongPassword = !login($pdo, 'test-admin', 'otra-clave') && currentUser() === null;
    $unknownUser = !login($pdo, 'no-existe', 'clave-de-prueba');
    $rightPassword = login($pdo, 'test-admin', 'clave-de-prueba') && currentUser()['rol'] === 'Administrador'
        && !isset(currentUser()['password_hash']);
    logout();
    check($wrongPassword, 'login rechaza una contraseña incorrecta');
    check($unknownUser, 'login rechaza un usuario inexistente');
    check($rightPassword, 'login correcto guarda el rol y no el hash');

    // Two teachers created through saveTeacher, which also exercises the category history.
    $teacherData = fn ($code) => [
        'codigo_docente' => $code, 'apellidos' => 'PRUEBA', 'nombres' => $code, 'tipo_documento' => 'DNI',
        'numero_documento' => null, 'departamento_id' => null, 'grado_academico_id' => null,
        'categoria_id' => 1, 'modalidad_id' => 2, 'horas_asignadas' => 40,
    ];
    $t1 = $created['teachers'][] = saveTeacher($pdo, null, $teacherData('TEST-1'), $period['periodo_id']);
    $t2 = $created['teachers'][] = saveTeacher($pdo, null, $teacherData('TEST-2'), $period['periodo_id']);
    saveTeacher($pdo, $t1, ['categoria_id' => 2, 'modalidad_id' => null] + $teacherData('TEST-1'), $period['periodo_id']);
    $saved = getTeacher($pdo, $t1, $period['periodo_id']);
    check($saved['categoria_id'] === 2 && $saved['modalidad_id'] === null, 'cambio de categoría y retiro de la asignación');
    check((int) $pdo->query("SELECT count(*) FROM docente_categoria_historial WHERE docente_id = $t1 AND fecha_fin IS NULL")->fetchColumn() === 1,
        'solo una categoría vigente tras el cambio');

    foreach (['TEST Lab A', 'TEST Lab B'] as $name) {
        createRoom($pdo, ['nombre' => $name, 'categoria_salon_id' => 2, 'escuela_id' => $school, 'sede_id' => 1, 'capacidad' => 30]);
        $created['rooms'][] = (int) $pdo->lastInsertId('salon_salon_id_seq');
    }
    [$labA, $labB] = $created['rooms'];

    // a regular closure (not fn) so the id is recorded in the outer $created for cleanup
    $section = function ($code, $letter, $extra = false) use ($pdo, $school, $course, $period, &$created) {
        return $created['sections'][] = createSection($pdo, [
            'escuela_id' => $school, 'curso_id' => $course($code), 'periodo_id' => $period['periodo_id'],
            'letra' => $letter, 'extraordinaria' => $extra,
        ]);
    };
    $bdA = $section('13641', 'TA');
    $bdB = $section('13641', 'TB');
    $repeat = $section('13021', 'TR', true);

    $block = fn ($sectionId, $teacher, $room, $type, $start, $end) => addBlock($pdo, [
        'seccion_id' => $sectionId, 'periodo_id' => $period['periodo_id'], 'docente_id' => $teacher, 'salon_id' => $room,
        'tipo_sesion' => $type, 'dia_semana' => 'Lunes', 'hora_inicio' => $start, 'hora_fin' => $end,
    ]);
    check($block($bdA, $t1, $labA, 'Teoría', '08:00', '10:00') === [], 'bloque de teoría guardado');
    check($block($bdA, $t2, $labB, 'Práctica', '08:00', '10:00') === [], 'práctica simultánea con otro docente y salón');

    $roomClash = $block($bdB, $t2, $labA, 'Teoría', '09:00', '11:00');
    check(count($roomClash) === 2 && $roomClash[0]['mismo_salon'] !== $roomClash[1]['mismo_salon'],
        'choque de salón y de docente reportados con sus bloques');
    $teacherClash = $block($bdB, $t1, $labB, 'Teoría', '07:00', '09:00');
    check(count($teacherClash) === 2, 'choque detectado aunque solo se cruce una hora');
    check($block($bdB, $t1, $labA, 'Teoría', '10:00', '12:00') === [], 'bloque contiguo (10:00 tras 08–10) permitido');
    check($block($repeat, $t2, $labB, 'Laboratorio', '14:00', '16:00') === [], 'sección de repetición con horario');

    $cycle5 = scheduleBlocks($pdo, ['periodo_id' => $period['periodo_id'], 'escuela_id' => $school, 'ciclo' => 5]);
    check(count(array_filter($cycle5, fn ($b) => str_starts_with($b['letra'], 'T'))) === 3, 'filtro por escuela y ciclo');
    check(count(scheduleBlocks($pdo, ['periodo_id' => $period['periodo_id'], 'docente_id' => $t2])) === 2, 'filtro por docente');
    check(count(scheduleBlocks($pdo, ['periodo_id' => $period['periodo_id'], 'salon_id' => $labA])) === 2, 'filtro por salón');

    ob_start();
    renderGrid(scheduleBlocks($pdo, ['periodo_id' => $period['periodo_id'], 'salon_id' => $labA]), fn ($b) => [$b['letra']]);
    $html = ob_get_clean();
    $rows = array_slice(explode('<tr>', $html), 2);
    check(count($rows) === 4, 'la grilla va de 08:00 a 12:00');
    check(substr_count($rows[0], 'TA') === 1 && substr_count($rows[2], 'TB') === 1 && substr_count($rows[2], 'TA') === 0,
        'cada bloque aparece en las horas que cubre');

    $repeatBlock = scheduleBlocks($pdo, ['periodo_id' => $period['periodo_id'], 'seccion_id' => $repeat])[0]['horario_detalle_id'];
    deleteBlock($pdo, $bdA, $repeatBlock);
    check(count(scheduleBlocks($pdo, ['periodo_id' => $period['periodo_id'], 'seccion_id' => $repeat])) === 1,
        'no se borra un bloque desde otra sección');
    deleteBlock($pdo, $repeat, $repeatBlock);
    check(!scheduleBlocks($pdo, ['periodo_id' => $period['periodo_id'], 'seccion_id' => $repeat]), 'quitar bloque de su sección');

    deleteSection($pdo, $bdB);
    $created['sections'] = array_diff($created['sections'], [$bdB]);
    check(count(scheduleBlocks($pdo, ['periodo_id' => $period['periodo_id'], 'salon_id' => $labA])) === 1, 'borrar sección borra sus bloques');

    // Teaching load: the admin assigns each part, the teacher proposes a time, the admin confirms it.
    saveLoads($pdo, $bdA, ['Teoría' => $t1, 'Práctica' => $t2]);
    $theory = teacherLoads($pdo, $t1, $period['periodo_id']);
    check(count($theory) === 1 && $theory[0]['tipo_sesion'] === 'Teoría', 'el docente ve solo su parte de la carga');
    $theory = $theory[0]['carga_horaria_id'];
    check(!addDesiredBlock($pdo, $t2, $theory, 'Martes', '08:00', '10:00'), 'un docente no propone en la carga de otro');
    check(addDesiredBlock($pdo, $t1, $theory, 'Martes', '08:00', '10:00'), 'propuesta del docente registrada');
    $proposal = desiredBlocks($pdo, ['seccion_id' => $bdA])[0]['horario_deseado_id'];
    deleteDesiredBlock($pdo, $proposal, 'docente_id', $t2);
    check(count(desiredBlocks($pdo, ['seccion_id' => $bdA])) === 1, 'un docente no borra la propuesta de otro');
    check(confirmDesiredBlock($pdo, $bdA, $proposal, $labA) === [] && !desiredBlocks($pdo, ['seccion_id' => $bdA]),
        'confirmar convierte la propuesta en bloque');
    $confirmed = array_filter(scheduleBlocks($pdo, ['periodo_id' => $period['periodo_id'], 'seccion_id' => $bdA]),
        fn ($b) => $b['dia_semana'] === 'Martes');
    check(count($confirmed) === 1 && current($confirmed)['docente_id'] === $t1, 'el bloque confirmado lleva el docente de la carga');
    check(confirmDesiredBlock($pdo, $bdA, $proposal, $labA) === null, 'una propuesta ya confirmada no se repite');

    addDesiredBlock($pdo, $t1, $theory, 'Jueves', '08:00', '10:00');
    saveLoads($pdo, $bdA, ['Teoría' => $t2]);
    check(!desiredBlocks($pdo, ['seccion_id' => $bdA]) && in_array('Teoría', array_column(teacherLoads($pdo, $t2, $period['periodo_id']), 'tipo_sesion'), true),
        'cambiar el docente descarta las propuestas del anterior');
    deleteSection($pdo, $bdA);
    $created['sections'] = array_diff($created['sections'], [$bdA]);
    check(!teacherLoads($pdo, $t2, $period['periodo_id']), 'borrar la sección borra su carga');
    echo "Todas las pruebas pasaron.\n";
} finally {
    foreach ($created['users'] as $name) {
        $pdo->prepare('DELETE FROM usuario WHERE nombre_usuario = ?')->execute([$name]);
    }
    foreach ($created['sections'] as $id) {
        deleteSection($pdo, $id);
    }
    foreach ($created['rooms'] as $id) {
        deleteRoom($pdo, $id);
    }
    foreach ($created['teachers'] as $id) {
        foreach (['docente_asignacion_periodo', 'docente_categoria_historial', 'docente'] as $table) {
            $pdo->exec("DELETE FROM $table WHERE docente_id = $id");
        }
    }
}
