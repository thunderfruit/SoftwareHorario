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
