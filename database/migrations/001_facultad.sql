-- Facultad agrupa escuelas y departamentos; el docente pertenece al departamento, no a la escuela.
BEGIN;

CREATE TABLE public.facultad (
    facultad_id serial PRIMARY KEY,
    nombre character varying(150) NOT NULL UNIQUE
);

-- La vista depende de escuela.departamento_id; se recrea al final.
DROP VIEW public.plaza_disponibilidad;

ALTER TABLE public.escuela
    DROP COLUMN departamento_id,
    ADD COLUMN facultad_id integer NOT NULL REFERENCES public.facultad(facultad_id),
    ADD UNIQUE (facultad_id, nombre);

ALTER TABLE public.departamento
    ADD COLUMN facultad_id integer NOT NULL REFERENCES public.facultad(facultad_id),
    ADD UNIQUE (facultad_id, nombre);

-- departamento, grado y documento son nullable mientras los datos de los docentes estén pendientes.
ALTER TABLE public.docente
    DROP COLUMN escuela_id,
    ADD COLUMN codigo_docente character varying(20) NOT NULL UNIQUE,
    ADD COLUMN departamento_id integer REFERENCES public.departamento(departamento_id),
    ALTER COLUMN grado_academico_id DROP NOT NULL,
    ALTER COLUMN numero_documento DROP NOT NULL;

-- El departamento diseña el curso; escuela_curso sigue siendo el plan de estudios.
ALTER TABLE public.curso
    ADD COLUMN departamento_id integer NOT NULL REFERENCES public.departamento(departamento_id);

CREATE VIEW public.plaza_disponibilidad AS
SELECT p.plaza_id,
       p.categoria_id,
       p.departamento_id,
       p.cantidad_total,
       count(d.docente_id) AS cantidad_ocupada,
       p.cantidad_total - count(d.docente_id) AS cantidad_disponible
FROM public.plaza p
LEFT JOIN public.docente_categoria_historial h ON h.categoria_id = p.categoria_id AND h.fecha_fin IS NULL
LEFT JOIN public.docente d ON d.docente_id = h.docente_id AND d.departamento_id = p.departamento_id
GROUP BY p.plaza_id, p.categoria_id, p.departamento_id, p.cantidad_total;

COMMIT;
