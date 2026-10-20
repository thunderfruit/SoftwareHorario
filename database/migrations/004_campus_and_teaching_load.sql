-- Sede de cada escuela (filiales) y carga horaria: el administrador asigna un docente a cada parte
-- de una sección (teoría, práctica, laboratorio) y el docente propone el día y la hora que prefiere.
BEGIN;

-- Todas las escuelas empiezan en Trujillo; las de filiales se corrigen a mano.
ALTER TABLE public.escuela ADD COLUMN sede_id integer REFERENCES public.sede(sede_id);
UPDATE public.escuela SET sede_id = (SELECT sede_id FROM public.sede WHERE nombre = 'Trujillo');
ALTER TABLE public.escuela ALTER COLUMN sede_id SET NOT NULL;

CREATE TABLE public.carga_horaria (
    carga_horaria_id serial PRIMARY KEY,
    seccion_id integer NOT NULL REFERENCES public.seccion(seccion_id) ON DELETE CASCADE,
    tipo_sesion character varying(11) NOT NULL CHECK (tipo_sesion IN ('Teoría', 'Práctica', 'Laboratorio')),
    docente_id integer NOT NULL REFERENCES public.docente(docente_id),
    UNIQUE (seccion_id, tipo_sesion)
);

CREATE INDEX ix_carga_horaria_docente ON public.carga_horaria (docente_id);

-- Propuesta del docente; no reserva salón ni bloquea a nadie. El administrador la convierte en horario_detalle.
CREATE TABLE public.horario_deseado (
    horario_deseado_id serial PRIMARY KEY,
    carga_horaria_id integer NOT NULL REFERENCES public.carga_horaria(carga_horaria_id) ON DELETE CASCADE,
    dia_semana character varying(10) NOT NULL
        CHECK (dia_semana IN ('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado')),
    hora_inicio time without time zone NOT NULL,
    hora_fin time without time zone NOT NULL,
    CHECK (hora_fin > hora_inicio)
);

CREATE INDEX ix_horario_deseado_carga ON public.horario_deseado (carga_horaria_id);

COMMIT;
