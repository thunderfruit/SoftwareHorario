<?php
require_once __DIR__ . '/connection.php';

/**
 * Teachers with their faculty, current category and the assignment of their latest period, ordered by faculty and department.
 * Empty filters are ignored. Text filters (code, last_name, document) are case-insensitive partial matches;
 * id filters (faculty_id, department_id, category_id, modality_id) are exact.
 */
function searchTeachers(PDO $pdo, array $filters = []): array
{
    $where = [];
    $params = [];
    foreach (['code' => 'd.codigo_docente', 'last_name' => 'd.apellidos', 'document' => 'd.numero_documento'] as $key => $column) {
        if (($filters[$key] ?? '') !== '') {
            $where[] = "$column ILIKE :$key";
            $params[$key] = likePattern($filters[$key]);
        }
    }
    $ids = ['faculty_id' => 'f.facultad_id', 'department_id' => 'd.departamento_id', 'category_id' => 'h.categoria_id',
        'modality_id' => 'a.modalidad_id'];
    foreach ($ids as $key => $column) {
        if (isset($filters[$key])) {
            $where[] = "$column = :$key";
            $params[$key] = $filters[$key];
        }
    }

    $sql = '
        SELECT d.docente_id, d.codigo_docente, d.apellidos, d.nombres, d.tipo_documento, d.numero_documento,
               f.nombre AS facultad, dep.nombre AS departamento,
               c.nombre AS categoria, m.nombre AS modalidad, a.horas_asignadas
        FROM public.docente d
        LEFT JOIN public.departamento dep ON dep.departamento_id = d.departamento_id
        LEFT JOIN public.facultad f ON f.facultad_id = dep.facultad_id
        LEFT JOIN public.docente_categoria_historial h ON h.docente_id = d.docente_id AND h.fecha_fin IS NULL
        LEFT JOIN public.categoria c ON c.categoria_id = h.categoria_id
        LEFT JOIN LATERAL (
            SELECT ap.modalidad_id, ap.horas_asignadas
            FROM public.docente_asignacion_periodo ap
            JOIN public.periodo p ON p.periodo_id = ap.periodo_id
            WHERE ap.docente_id = d.docente_id
            ORDER BY p.fecha_inicio DESC
            LIMIT 1
        ) a ON true
        LEFT JOIN public.modalidad m ON m.modalidad_id = a.modalidad_id'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY f.nombre, dep.nombre, d.apellidos, d.nombres';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Every department with its teachers (one row per teacher; teacher columns are null for an empty department).
function listDepartmentsWithTeachers(PDO $pdo): array
{
    return $pdo->query('SELECT dep.facultad_id, dep.departamento_id, dep.nombre AS departamento,
                               d.docente_id, d.codigo_docente, d.apellidos || \', \' || d.nombres AS docente
                        FROM public.departamento dep
                        LEFT JOIN public.docente d ON d.departamento_id = dep.departamento_id
                        ORDER BY dep.nombre, d.apellidos, d.nombres')->fetchAll();
}

function listAllTeachers(PDO $pdo): array
{
    return $pdo->query('SELECT docente_id, codigo_docente, apellidos, nombres FROM public.docente
                        ORDER BY apellidos, nombres')->fetchAll();
}

// Teacher row plus current category and the assignment for the given period.
function getTeacher(PDO $pdo, int $teacherId, int $periodId): ?array
{
    $stmt = $pdo->prepare('
        SELECT d.*, h.categoria_id, a.modalidad_id, a.horas_asignadas
        FROM public.docente d
        LEFT JOIN public.docente_categoria_historial h ON h.docente_id = d.docente_id AND h.fecha_fin IS NULL
        LEFT JOIN public.docente_asignacion_periodo a ON a.docente_id = d.docente_id AND a.periodo_id = :period
        WHERE d.docente_id = :id');
    $stmt->execute(['id' => $teacherId, 'period' => $periodId]);
    return $stmt->fetch() ?: null;
}

/**
 * Creates or updates a teacher, their current category and their assignment for $periodId.
 * A category change closes the current history row today and opens a new one.
 */
function saveTeacher(PDO $pdo, ?int $teacherId, array $data, int $periodId): int
{
    // names are stored upper-case like the official lists; upper() runs in PostgreSQL so Ñ and accents work without mbstring
    $pdo->beginTransaction();
    try {
        $teacher = array_intersect_key($data, array_flip([
            'codigo_docente', 'apellidos', 'nombres', 'tipo_documento', 'numero_documento',
            'departamento_id', 'grado_academico_id',
        ]));
        if ($teacherId === null) {
            $stmt = $pdo->prepare('INSERT INTO public.docente (codigo_docente, apellidos, nombres, tipo_documento,
                                       numero_documento, departamento_id, grado_academico_id)
                                   VALUES (:codigo_docente, upper(:apellidos), upper(:nombres), :tipo_documento,
                                       :numero_documento, :departamento_id, :grado_academico_id)
                                   RETURNING docente_id');
            $stmt->execute($teacher);
            $teacherId = (int) $stmt->fetchColumn();
        } else {
            $pdo->prepare('UPDATE public.docente SET codigo_docente = :codigo_docente, apellidos = upper(:apellidos),
                               nombres = upper(:nombres), tipo_documento = :tipo_documento, numero_documento = :numero_documento,
                               departamento_id = :departamento_id, grado_academico_id = :grado_academico_id
                           WHERE docente_id = :id')
                ->execute($teacher + ['id' => $teacherId]);
        }

        saveCurrentCategory($pdo, $teacherId, $data['categoria_id']);

        if ($data['modalidad_id'] === null) {
            $pdo->prepare('DELETE FROM public.docente_asignacion_periodo WHERE docente_id = ? AND periodo_id = ?')
                ->execute([$teacherId, $periodId]);
        } else {
            $pdo->prepare('INSERT INTO public.docente_asignacion_periodo
                               (docente_id, periodo_id, modalidad_id, categoria_id, horas_asignadas)
                           VALUES (:docente, :periodo, :modalidad, :categoria, :horas)
                           ON CONFLICT (docente_id, periodo_id) DO UPDATE
                           SET modalidad_id = EXCLUDED.modalidad_id, categoria_id = EXCLUDED.categoria_id,
                               horas_asignadas = EXCLUDED.horas_asignadas')
                ->execute([
                    'docente' => $teacherId, 'periodo' => $periodId, 'modalidad' => $data['modalidad_id'],
                    'categoria' => $data['categoria_id'], 'horas' => $data['horas_asignadas'],
                ]);
        }

        $pdo->commit();
        return $teacherId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function saveCurrentCategory(PDO $pdo, int $teacherId, ?int $categoryId): void
{
    $stmt = $pdo->prepare('SELECT historial_id, categoria_id, fecha_inicio >= CURRENT_DATE AS starts_today_or_later
                           FROM public.docente_categoria_historial WHERE docente_id = ? AND fecha_fin IS NULL');
    $stmt->execute([$teacherId]);
    $current = $stmt->fetch();

    if (($current['categoria_id'] ?? null) === $categoryId) {
        return;
    }
    if ($current) {
        // a row that started today can't be closed today (fecha_fin must be after fecha_inicio), so it is replaced
        $sql = $current['starts_today_or_later']
            ? 'DELETE FROM public.docente_categoria_historial WHERE historial_id = ?'
            : 'UPDATE public.docente_categoria_historial SET fecha_fin = CURRENT_DATE WHERE historial_id = ?';
        $pdo->prepare($sql)->execute([$current['historial_id']]);
    }
    if ($categoryId !== null) {
        $pdo->prepare('INSERT INTO public.docente_categoria_historial (docente_id, categoria_id, fecha_inicio)
                       VALUES (?, ?, CURRENT_DATE)')->execute([$teacherId, $categoryId]);
    }
}

// Lookup lists for the teacher form and filters.
function teacherCatalogs(PDO $pdo): array
{
    return [
        'faculties' => $pdo->query('SELECT * FROM public.facultad ORDER BY nombre')->fetchAll(),
        'departments' => $pdo->query('SELECT d.departamento_id, d.nombre, f.nombre AS facultad
                                      FROM public.departamento d JOIN public.facultad f USING (facultad_id)
                                      ORDER BY f.nombre, d.nombre')->fetchAll(),
        'degrees' => $pdo->query('SELECT * FROM public.grado_academico ORDER BY grado_academico_id')->fetchAll(),
        'categories' => $pdo->query('SELECT * FROM public.categoria ORDER BY nombre')->fetchAll(),
        'modalities' => $pdo->query('SELECT * FROM public.modalidad ORDER BY modalidad_id')->fetchAll(),
    ];
}
