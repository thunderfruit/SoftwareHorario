-- Plan de estudios (ciclo, créditos, horas, prerrequisitos) y secciones con docente por bloque
-- (teoría y práctica pueden tener docentes distintos). Asume curso, detalle_curso y horario_detalle vacíos.
BEGIN;

ALTER TABLE public.curso
    ADD COLUMN codigo character varying(10) NOT NULL UNIQUE,
    ADD COLUMN tipo character varying(2) NOT NULL CHECK (tipo IN ('EG', 'EE', 'ES')),
    ADD COLUMN horas_teoria smallint NOT NULL DEFAULT 0 CHECK (horas_teoria >= 0),
    ADD COLUMN horas_practica smallint NOT NULL DEFAULT 0 CHECK (horas_practica >= 0),
    ADD COLUMN horas_laboratorio smallint NOT NULL DEFAULT 0 CHECK (horas_laboratorio >= 0),
    ADD COLUMN creditos smallint NOT NULL CHECK (creditos >= 0),
    -- nullable: el plan cita departamentos que el estatuto no lista (p. ej. Lengua y Literatura)
    ALTER COLUMN departamento_id DROP NOT NULL;

ALTER TABLE public.escuela_curso
    ADD COLUMN ciclo smallint NOT NULL CHECK (ciclo BETWEEN 1 AND 12);

CREATE TABLE public.curso_prerrequisito (
    curso_id integer NOT NULL REFERENCES public.curso(curso_id),
    prerrequisito_id integer NOT NULL REFERENCES public.curso(curso_id),
    PRIMARY KEY (curso_id, prerrequisito_id),
    CHECK (curso_id <> prerrequisito_id)
);

DROP TABLE public.horario_detalle;
DROP TABLE public.detalle_curso;
DROP FUNCTION public.check_teacher_schedule_overlap();

CREATE TABLE public.seccion (
    seccion_id serial PRIMARY KEY,
    escuela_id integer NOT NULL,
    curso_id integer NOT NULL,
    periodo_id integer NOT NULL REFERENCES public.periodo(periodo_id),
    letra character varying(5) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    -- solo cursos del plan de esa escuela
    FOREIGN KEY (escuela_id, curso_id) REFERENCES public.escuela_curso(escuela_id, curso_id),
    UNIQUE (escuela_id, curso_id, periodo_id, letra),
    -- destino de la FK compuesta de horario_detalle
    UNIQUE (seccion_id, periodo_id)
);

CREATE TRIGGER trg_seccion_updated_at BEFORE UPDATE ON public.seccion
    FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

CREATE TABLE public.horario_detalle (
    horario_detalle_id serial PRIMARY KEY,
    seccion_id integer NOT NULL,
    -- copia del periodo de la sección (la FK compuesta lo mantiene igual) para que las restricciones de choque lo usen
    periodo_id integer NOT NULL,
    docente_id integer NOT NULL REFERENCES public.docente(docente_id),
    salon_id integer NOT NULL REFERENCES public.salon(salon_id),
    tipo_sesion character varying(11) NOT NULL CHECK (tipo_sesion IN ('Teoría', 'Práctica', 'Laboratorio')),
    dia_semana character varying(10) NOT NULL
        CHECK (dia_semana IN ('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado')),
    hora_inicio time without time zone NOT NULL,
    hora_fin time without time zone NOT NULL,
    CHECK (hora_fin > hora_inicio),
    FOREIGN KEY (seccion_id, periodo_id) REFERENCES public.seccion(seccion_id, periodo_id) ON UPDATE CASCADE,
    CONSTRAINT no_choque_salon EXCLUDE USING gist (
        salon_id WITH =, periodo_id WITH =, dia_semana WITH =,
        tsrange('2000-01-01'::date + hora_inicio, '2000-01-01'::date + hora_fin) WITH &&),
    CONSTRAINT no_choque_docente EXCLUDE USING gist (
        docente_id WITH =, periodo_id WITH =, dia_semana WITH =,
        tsrange('2000-01-01'::date + hora_inicio, '2000-01-01'::date + hora_fin) WITH &&)
);

CREATE INDEX ix_horario_detalle_seccion ON public.horario_detalle (seccion_id);

COMMIT;
