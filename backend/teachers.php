<?php
require_once __DIR__ . '/connection.php';

// Escapes LIKE wildcards so a user typing "%" or "_" searches for them literally.
function likePattern(string $value): string
{
    return '%' . addcslashes($value, '%_\\') . '%';
}

/**
 * Teachers with their current category and the assignment of their latest period.
 * Empty filters are ignored; text filters are case-insensitive partial matches.
 */
function searchTeachers(PDO $pdo, string $code = '', string $lastName = '', string $document = ''): array
{
    $where = [];
    $params = [];

    if ($code !== '') {
        $where[] = 'd.codigo_docente ILIKE :code';
        $params['code'] = likePattern($code);
    }
    if ($lastName !== '') {
        $where[] = 'd.apellidos ILIKE :last_name';
        $params['last_name'] = likePattern($lastName);
    }
    if ($document !== '') {
        $where[] = 'd.numero_documento ILIKE :document';
        $params['document'] = likePattern($document);
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
        . ' ORDER BY d.apellidos, d.nombres';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
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

// Lookup lists for the teacher form.
function teacherCatalogs(PDO $pdo): array
{
    return [
        'departments' => $pdo->query('SELECT d.departamento_id, d.nombre, f.nombre AS facultad
                                      FROM public.departamento d JOIN public.facultad f USING (facultad_id)
                                      ORDER BY f.nombre, d.nombre')->fetchAll(),
        'degrees' => $pdo->query('SELECT * FROM public.grado_academico ORDER BY grado_academico_id')->fetchAll(),
        'categories' => $pdo->query('SELECT * FROM public.categoria ORDER BY nombre')->fetchAll(),
        'modalities' => $pdo->query('SELECT * FROM public.modalidad ORDER BY modalidad_id')->fetchAll(),
    ];
}
