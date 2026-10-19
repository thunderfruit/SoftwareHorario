<?php
// Run: php tests/schedule_test.php
// Uses the configured database; every row it creates is removed at the end, even on failure.
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/frontend/index.php';
require_once dirname(__DIR__) . '/frontend/grid.php';
require_once dirname(__DIR__) . '/backend/teachers.php';

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
$created = ['rooms' => [], 'sections' => [], 'teachers' => []];

try {
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
    check($block($repeat, $t2, $labB, 'Laboratorio', '14:00', '16:00') === [], 'sección de repitencia con horario');

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

    deleteSection($pdo, $bdB);
    $created['sections'] = array_diff($created['sections'], [$bdB]);
    check(count(scheduleBlocks($pdo, ['periodo_id' => $period['periodo_id'], 'salon_id' => $labA])) === 1, 'borrar sección borra sus bloques');
    echo "Todas las pruebas pasaron.\n";
} finally {
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
