-- Secciones extraordinarias (repitencia) y usuarios con rol para el login.
BEGIN;

-- Sección dictada fuera de su ciclo habitual o en un periodo extraordinario (p. ej. vacacional).
ALTER TABLE public.seccion
    ADD COLUMN extraordinaria boolean NOT NULL DEFAULT false;

CREATE TABLE public.usuario (
    usuario_id serial PRIMARY KEY,
    nombre_usuario character varying(50) NOT NULL UNIQUE,
    password_hash character varying(255) NOT NULL,
    rol character varying(15) NOT NULL CHECK (rol IN ('Administrador', 'Docente')),
    docente_id integer UNIQUE REFERENCES public.docente(docente_id),
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    -- un usuario docente siempre apunta a su docente; un administrador nunca
    CHECK ((rol = 'Docente') = (docente_id IS NOT NULL))
);

COMMIT;
