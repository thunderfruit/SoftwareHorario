<?php
require_once __DIR__ . '/schedule.php';

// Session types a course actually has (more than 0 hours), with their weekly hours.
function sessionHours(array $course): array
{
    return array_filter([
        'Teoría' => $course['horas_teoria'], 'Práctica' => $course['horas_practica'], 'Laboratorio' => $course['horas_laboratorio'],
    ]);
}

// Teacher assigned to each part of the sections of a school in a period, with how many blocks they proposed.
function listLoads(PDO $pdo, int $periodId, int $schoolId): array
{
    $stmt = $pdo->prepare('SELECT ch.*, d.apellidos || \', \' || d.nombres AS docente,
                                  (SELECT count(*) FROM public.horario_deseado hd
                                   WHERE hd.carga_horaria_id = ch.carga_horaria_id) AS propuestas
                           FROM public.carga_horaria ch
                           JOIN public.seccion s USING (seccion_id)
                           JOIN public.docente d ON d.docente_id = ch.docente_id
                           WHERE s.periodo_id = ? AND s.escuela_id = ?');
    $stmt->execute([$periodId, $schoolId]);
    return $stmt->fetchAll();
}

/**
 * Sets the teacher of each part of a section: [tipo_sesion => docente_id], null leaves the part unassigned.
 * Changing the teacher discards what the previous one proposed.
 */
function saveLoads(PDO $pdo, int $sectionId, array $teacherByType): void
{
    $pdo->beginTransaction();
    try {
        foreach ($teacherByType as $type => $teacherId) {
            $pdo->prepare('DELETE FROM public.carga_horaria
                           WHERE seccion_id = ? AND tipo_sesion = ? AND docente_id IS DISTINCT FROM ?')
                ->execute([$sectionId, $type, $teacherId]);
            if ($teacherId !== null) {
                $pdo->prepare('INSERT INTO public.carga_horaria (seccion_id, tipo_sesion, docente_id) VALUES (?, ?, ?)
                               ON CONFLICT (seccion_id, tipo_sesion) DO NOTHING')
                    ->execute([$sectionId, $type, $teacherId]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Parts of sections assigned to a teacher in a period, with the weekly hours of each part.
function teacherLoads(PDO $pdo, int $teacherId, int $periodId): array
{
    $stmt = $pdo->prepare('SELECT ch.carga_horaria_id, ch.tipo_sesion, s.seccion_id, s.letra, s.extraordinaria,
                                  c.codigo, c.nombre AS curso, ec.ciclo, e.nombre AS escuela,
                                  CASE ch.tipo_sesion WHEN \'Teoría\' THEN c.horas_teoria
                                      WHEN \'Práctica\' THEN c.horas_practica ELSE c.horas_laboratorio END AS horas
                           FROM public.carga_horaria ch
                           JOIN public.seccion s USING (seccion_id)
                           JOIN public.curso c ON c.curso_id = s.curso_id
                           JOIN public.escuela_curso ec ON ec.escuela_id = s.escuela_id AND ec.curso_id = s.curso_id
                           JOIN public.escuela e ON e.escuela_id = s.escuela_id
                           WHERE ch.docente_id = ? AND s.periodo_id = ?
                           ORDER BY e.nombre, ec.ciclo, c.nombre, s.letra, ch.tipo_sesion');
    $stmt->execute([$teacherId, $periodId]);
    return $stmt->fetchAll();
}

/**
 * Blocks proposed by teachers, with the columns renderGrid() needs.
 * Filters (all optional): docente_id, periodo_id, seccion_id.
 */
function desiredBlocks(PDO $pdo, array $filters): array
{
    $where = ['true'];
    $params = [];
    foreach (['docente_id' => 'ch.docente_id', 'periodo_id' => 's.periodo_id', 'seccion_id' => 'ch.seccion_id'] as $key => $column) {
        if (isset($filters[$key])) {
            $where[] = "$column = :$key";
            $params[$key] = $filters[$key];
        }
    }
    $stmt = $pdo->prepare('
        SELECT hd.*, ch.tipo_sesion, ch.docente_id, ch.seccion_id, s.letra, s.extraordinaria, s.periodo_id,
               c.codigo, c.nombre AS curso, d.apellidos || \', \' || d.nombres AS docente
        FROM public.horario_deseado hd
        JOIN public.carga_horaria ch USING (carga_horaria_id)
        JOIN public.seccion s ON s.seccion_id = ch.seccion_id
        JOIN public.curso c ON c.curso_id = s.curso_id
        JOIN public.docente d ON d.docente_id = ch.docente_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY ' . dayOrder('hd.dia_semana') . ', hd.hora_inicio');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Returns false when the part is not assigned to $teacherId, so a teacher can't propose for someone else's load.
function addDesiredBlock(PDO $pdo, int $teacherId, int $loadId, string $day, string $start, string $end): bool
{
    $stmt = $pdo->prepare('INSERT INTO public.horario_deseado (carga_horaria_id, dia_semana, hora_inicio, hora_fin)
                           SELECT carga_horaria_id, :day, :start, :end FROM public.carga_horaria
                           WHERE carga_horaria_id = :load AND docente_id = :teacher');
    $stmt->execute(['day' => $day, 'start' => $start, 'end' => $end, 'load' => $loadId, 'teacher' => $teacherId]);
    return $stmt->rowCount() === 1;
}

// $ownerColumn scopes the delete: 'docente_id' for the teacher's own page, 'seccion_id' for the admin's section page.
function deleteDesiredBlock(PDO $pdo, int $desiredId, string $ownerColumn, int $ownerId): void
{
    if (!in_array($ownerColumn, ['docente_id', 'seccion_id'], true)) {
        throw new InvalidArgumentException("Columna no permitida: $ownerColumn");
    }
    $pdo->prepare("DELETE FROM public.horario_deseado hd USING public.carga_horaria ch
                   WHERE ch.carga_horaria_id = hd.carga_horaria_id AND hd.horario_deseado_id = ? AND ch.$ownerColumn = ?")
        ->execute([$desiredId, $ownerId]);
}

/**
 * Turns a proposal of the section into a real block in $roomId and removes the proposal.
 * Returns null when the proposal doesn't exist, otherwise addBlock()'s result ([] when saved, or the clashing blocks).
 */
function confirmDesiredBlock(PDO $pdo, int $sectionId, int $desiredId, int $roomId): ?array
{
    $desired = current(array_filter(desiredBlocks($pdo, ['seccion_id' => $sectionId]),
        fn ($d) => $d['horario_deseado_id'] === $desiredId)) ?: null;
    if ($desired === null) {
        return null;
    }
    $conflicts = addBlock($pdo, [
        'seccion_id' => $sectionId, 'periodo_id' => $desired['periodo_id'], 'docente_id' => $desired['docente_id'],
        'salon_id' => $roomId, 'tipo_sesion' => $desired['tipo_sesion'], 'dia_semana' => $desired['dia_semana'],
        'hora_inicio' => $desired['hora_inicio'], 'hora_fin' => $desired['hora_fin'],
    ]);
    if (!$conflicts) {
        deleteDesiredBlock($pdo, $desiredId, 'seccion_id', $sectionId);
    }
    return $conflicts;
}
