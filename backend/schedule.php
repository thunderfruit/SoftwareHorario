<?php
require_once __DIR__ . '/connection.php';

const DAYS = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
const SESSION_TYPES = ['Teoría', 'Práctica', 'Laboratorio'];
const FIRST_HOUR = 7;
const LAST_HOUR = 22;

// Period that contains today; otherwise the most recent one.
function currentPeriod(PDO $pdo): ?array
{
    return $pdo->query('SELECT * FROM public.periodo
                        ORDER BY (CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin) DESC, fecha_inicio DESC
                        LIMIT 1')->fetch() ?: null;
}

function listPeriods(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM public.periodo ORDER BY fecha_inicio DESC')->fetchAll();
}

// Only schools with a loaded study plan.
function listSchoolsWithPlan(PDO $pdo): array
{
    return $pdo->query('SELECT e.escuela_id, e.nombre, max(ec.ciclo) AS ultimo_ciclo
                        FROM public.escuela e JOIN public.escuela_curso ec USING (escuela_id)
                        GROUP BY e.escuela_id, e.nombre ORDER BY e.nombre')->fetchAll();
}

function planCourses(PDO $pdo, int $schoolId): array
{
    $stmt = $pdo->prepare('SELECT c.curso_id, c.codigo, c.nombre, ec.ciclo
                           FROM public.escuela_curso ec JOIN public.curso c USING (curso_id)
                           WHERE ec.escuela_id = ? ORDER BY ec.ciclo, c.nombre');
    $stmt->execute([$schoolId]);
    return $stmt->fetchAll();
}

function listRoomTypes(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM public.categoria_salon ORDER BY nombre')->fetchAll();
}

function listRooms(PDO $pdo, ?int $roomTypeId = null): array
{
    $stmt = $pdo->prepare('SELECT s.*, cs.nombre AS categoria, e.nombre AS escuela, se.nombre AS sede
                           FROM public.salon s
                           JOIN public.categoria_salon cs USING (categoria_salon_id)
                           JOIN public.escuela e USING (escuela_id)
                           JOIN public.sede se USING (sede_id)
                           WHERE CAST(:type AS integer) IS NULL OR s.categoria_salon_id = :type
                           ORDER BY s.nombre');
    $stmt->execute(['type' => $roomTypeId]);
    return $stmt->fetchAll();
}

function createRoom(PDO $pdo, array $data): void
{
    $pdo->prepare('INSERT INTO public.salon (nombre, categoria_salon_id, escuela_id, sede_id, capacidad)
                   VALUES (:nombre, :categoria_salon_id, :escuela_id, :sede_id, :capacidad)')->execute($data);
}

function deleteRoom(PDO $pdo, int $roomId): void
{
    $pdo->prepare('DELETE FROM public.salon WHERE salon_id = ?')->execute([$roomId]);
}

function listSections(PDO $pdo, int $periodId, int $schoolId): array
{
    $stmt = $pdo->prepare('SELECT s.*, c.codigo, c.nombre AS curso, ec.ciclo,
                                  (SELECT count(*) FROM public.horario_detalle h WHERE h.seccion_id = s.seccion_id) AS bloques
                           FROM public.seccion s
                           JOIN public.curso c USING (curso_id)
                           JOIN public.escuela_curso ec USING (escuela_id, curso_id)
                           WHERE s.periodo_id = ? AND s.escuela_id = ?
                           ORDER BY ec.ciclo, c.nombre, s.letra');
    $stmt->execute([$periodId, $schoolId]);
    return $stmt->fetchAll();
}

function getSection(PDO $pdo, int $sectionId): ?array
{
    $stmt = $pdo->prepare('SELECT s.*, c.codigo, c.nombre AS curso, c.horas_teoria, c.horas_practica, c.horas_laboratorio,
                                  ec.ciclo, e.nombre AS escuela, p.nombre AS periodo
                           FROM public.seccion s
                           JOIN public.curso c USING (curso_id)
                           JOIN public.escuela_curso ec USING (escuela_id, curso_id)
                           JOIN public.escuela e USING (escuela_id)
                           JOIN public.periodo p USING (periodo_id)
                           WHERE s.seccion_id = ?');
    $stmt->execute([$sectionId]);
    return $stmt->fetch() ?: null;
}

function createSection(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO public.seccion (escuela_id, curso_id, periodo_id, letra, extraordinaria)
                           VALUES (:escuela_id, :curso_id, :periodo_id, :letra, :extraordinaria)
                           RETURNING seccion_id');
    $stmt->bindValue('escuela_id', $data['escuela_id'], PDO::PARAM_INT);
    $stmt->bindValue('curso_id', $data['curso_id'], PDO::PARAM_INT);
    $stmt->bindValue('periodo_id', $data['periodo_id'], PDO::PARAM_INT);
    $stmt->bindValue('letra', $data['letra']);
    $stmt->bindValue('extraordinaria', $data['extraordinaria'], PDO::PARAM_BOOL);
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function deleteSection(PDO $pdo, int $sectionId): void
{
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM public.horario_detalle WHERE seccion_id = ?')->execute([$sectionId]);
        $pdo->prepare('DELETE FROM public.seccion WHERE seccion_id = ?')->execute([$sectionId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Schedule blocks with everything needed to display them.
 * Filters (all optional except periodo_id): escuela_id, ciclo, salon_id, categoria_salon_id, docente_id, seccion_id.
 */
function scheduleBlocks(PDO $pdo, array $filters): array
{
    $where = ['h.periodo_id = :periodo_id'];
    $params = ['periodo_id' => $filters['periodo_id']];
    $columns = [
        'escuela_id' => 's.escuela_id', 'ciclo' => 'ec.ciclo', 'salon_id' => 'h.salon_id',
        'categoria_salon_id' => 'sa.categoria_salon_id', 'docente_id' => 'h.docente_id', 'seccion_id' => 'h.seccion_id',
    ];
    foreach ($columns as $key => $column) {
        if (isset($filters[$key])) {
            $where[] = "$column = :$key";
            $params[$key] = $filters[$key];
        }
    }

    $stmt = $pdo->prepare('
        SELECT h.*, s.letra, s.extraordinaria, c.codigo, c.nombre AS curso, ec.ciclo,
               d.apellidos || \', \' || d.nombres AS docente, sa.nombre AS salon, cs.nombre AS categoria_salon
        FROM public.horario_detalle h
        JOIN public.seccion s USING (seccion_id)
        JOIN public.curso c ON c.curso_id = s.curso_id
        JOIN public.escuela_curso ec ON ec.escuela_id = s.escuela_id AND ec.curso_id = s.curso_id
        JOIN public.docente d ON d.docente_id = h.docente_id
        JOIN public.salon sa ON sa.salon_id = h.salon_id
        JOIN public.categoria_salon cs ON cs.categoria_salon_id = sa.categoria_salon_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY array_position(ARRAY[\'' . implode("','", DAYS) . '\']::varchar[], h.dia_semana), h.hora_inicio');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Blocks in the same period and day that overlap the given block in its room or with its teacher.
function findConflicts(PDO $pdo, array $block): array
{
    $stmt = $pdo->prepare('
        SELECT h.*, c.codigo, c.nombre AS curso, s.letra, sa.nombre AS salon,
               d.apellidos || \', \' || d.nombres AS docente,
               h.salon_id = :salon_id AS mismo_salon, h.docente_id = :docente_id AS mismo_docente
        FROM public.horario_detalle h
        JOIN public.seccion s USING (seccion_id)
        JOIN public.curso c ON c.curso_id = s.curso_id
        JOIN public.salon sa ON sa.salon_id = h.salon_id
        JOIN public.docente d ON d.docente_id = h.docente_id
        WHERE h.periodo_id = :periodo_id AND h.dia_semana = :dia_semana
          AND h.hora_inicio < :hora_fin AND :hora_inicio < h.hora_fin
          AND (h.salon_id = :salon_id OR h.docente_id = :docente_id)');
    $stmt->execute(array_intersect_key($block, array_flip(
        ['periodo_id', 'dia_semana', 'hora_inicio', 'hora_fin', 'salon_id', 'docente_id'])));
    return $stmt->fetchAll();
}

/**
 * Inserts a block. Returns [] when saved, or the blocks it clashes with.
 * The database guarantees there are no clashes; findConflicts only explains the rejection.
 */
function addBlock(PDO $pdo, array $block): array
{
    try {
        $pdo->prepare('INSERT INTO public.horario_detalle
                           (seccion_id, periodo_id, docente_id, salon_id, tipo_sesion, dia_semana, hora_inicio, hora_fin)
                       VALUES (:seccion_id, :periodo_id, :docente_id, :salon_id, :tipo_sesion, :dia_semana, :hora_inicio, :hora_fin)')
            ->execute($block);
        return [];
    } catch (PDOException $e) {
        // with nothing to show (e.g. deleted meanwhile) rethrow instead of reporting success
        $conflicts = $e->getCode() === '23P01' ? findConflicts($pdo, $block) : [];
        if (!$conflicts) {
            throw $e;
        }
        return $conflicts;
    }
}

function deleteBlock(PDO $pdo, int $blockId): void
{
    $pdo->prepare('DELETE FROM public.horario_detalle WHERE horario_detalle_id = ?')->execute([$blockId]);
}
