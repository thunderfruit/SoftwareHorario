--
-- PostgreSQL database dump
--

\restrict jU05A3qZBN4rMgmfvdiUZ6pQlKfdmSVcKx75xeYLnPQtjVxDTDmw1R8pwp14LyC

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: btree_gist; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS btree_gist WITH SCHEMA public;


--
-- Name: EXTENSION btree_gist; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION btree_gist IS 'support for indexing common datatypes in GiST';


--
-- Name: check_teacher_schedule_overlap(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.check_teacher_schedule_overlap() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  v_docente_id int;
  v_periodo_id int;
BEGIN
  SELECT docente_id, periodo_id INTO v_docente_id, v_periodo_id
  FROM detalle_curso WHERE detalle_curso_id = NEW.detalle_curso_id;

  -- serializes concurrent inserts for the same teacher so both can't pass the check
  PERFORM pg_advisory_xact_lock(v_docente_id);

  IF EXISTS (
    SELECT 1 FROM horario_detalle h JOIN detalle_curso dc USING (detalle_curso_id)
    WHERE dc.docente_id = v_docente_id AND dc.periodo_id = v_periodo_id
      AND h.dia_semana = NEW.dia_semana
      AND h.hora_inicio < NEW.hora_fin AND NEW.hora_inicio < h.hora_fin
      AND h.horario_detalle_id <> NEW.horario_detalle_id
  ) THEN
    RAISE EXCEPTION 'El docente % ya tiene clase el % entre % y %',
      v_docente_id, NEW.dia_semana, NEW.hora_inicio, NEW.hora_fin;
  END IF;
  RETURN NEW;
END $$;


--
-- Name: set_updated_at(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.set_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: categoria; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categoria (
    categoria_id integer NOT NULL,
    grado_minimo_id integer NOT NULL,
    nombre character varying(50) NOT NULL
);


--
-- Name: categoria_categoria_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categoria_categoria_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categoria_categoria_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categoria_categoria_id_seq OWNED BY public.categoria.categoria_id;


--
-- Name: categoria_salon; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categoria_salon (
    categoria_salon_id integer NOT NULL,
    nombre character varying(100) NOT NULL
);


--
-- Name: categoria_salon_categoria_salon_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categoria_salon_categoria_salon_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categoria_salon_categoria_salon_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categoria_salon_categoria_salon_id_seq OWNED BY public.categoria_salon.categoria_salon_id;


--
-- Name: curso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.curso (
    curso_id integer NOT NULL,
    nombre character varying(150) NOT NULL,
    departamento_id integer NOT NULL
);


--
-- Name: curso_curso_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.curso_curso_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: curso_curso_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.curso_curso_id_seq OWNED BY public.curso.curso_id;


--
-- Name: departamento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.departamento (
    departamento_id integer NOT NULL,
    nombre character varying(150) NOT NULL,
    facultad_id integer NOT NULL
);


--
-- Name: departamento_departamento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.departamento_departamento_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: departamento_departamento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.departamento_departamento_id_seq OWNED BY public.departamento.departamento_id;


--
-- Name: detalle_curso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.detalle_curso (
    detalle_curso_id integer NOT NULL,
    docente_id integer NOT NULL,
    curso_id integer NOT NULL,
    periodo_id integer NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: detalle_curso_detalle_curso_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.detalle_curso_detalle_curso_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: detalle_curso_detalle_curso_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.detalle_curso_detalle_curso_id_seq OWNED BY public.detalle_curso.detalle_curso_id;


--
-- Name: docente; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.docente (
    docente_id integer NOT NULL,
    grado_academico_id integer,
    nombres character varying(150) NOT NULL,
    apellidos character varying(150) NOT NULL,
    numero_documento character varying(12),
    tipo_documento character varying(3) DEFAULT 'DNI'::character varying NOT NULL,
    codigo_docente character varying(20) NOT NULL,
    departamento_id integer,
    CONSTRAINT docente_check CHECK (((((tipo_documento)::text = 'DNI'::text) AND ((numero_documento)::text ~ '^[0-9]{8}$'::text)) OR (((tipo_documento)::text = 'CE'::text) AND ((numero_documento)::text ~ '^[0-9A-Z]{9,12}$'::text)))),
    CONSTRAINT docente_tipo_documento_check CHECK (((tipo_documento)::text = ANY ((ARRAY['DNI'::character varying, 'CE'::character varying])::text[])))
);


--
-- Name: docente_asignacion_periodo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.docente_asignacion_periodo (
    docente_asignacion_periodo_id integer CONSTRAINT docente_asignacion_periodo_docente_asignacion_periodo__not_null NOT NULL,
    docente_id integer NOT NULL,
    periodo_id integer NOT NULL,
    modalidad_id integer NOT NULL,
    categoria_id integer NOT NULL,
    horas_asignadas integer NOT NULL,
    CONSTRAINT docente_asignacion_periodo_horas_asignadas_check CHECK (((horas_asignadas >= 8) AND (horas_asignadas <= 40)))
);


--
-- Name: docente_asignacion_periodo_docente_asignacion_periodo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.docente_asignacion_periodo_docente_asignacion_periodo_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: docente_asignacion_periodo_docente_asignacion_periodo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.docente_asignacion_periodo_docente_asignacion_periodo_id_seq OWNED BY public.docente_asignacion_periodo.docente_asignacion_periodo_id;


--
-- Name: docente_categoria_historial; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.docente_categoria_historial (
    historial_id integer NOT NULL,
    docente_id integer NOT NULL,
    categoria_id integer NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin date,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT docente_categoria_historial_check CHECK (((fecha_fin IS NULL) OR (fecha_fin > fecha_inicio)))
);


--
-- Name: docente_categoria_historial_historial_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.docente_categoria_historial_historial_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: docente_categoria_historial_historial_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.docente_categoria_historial_historial_id_seq OWNED BY public.docente_categoria_historial.historial_id;


--
-- Name: docente_docente_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.docente_docente_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: docente_docente_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.docente_docente_id_seq OWNED BY public.docente.docente_id;


--
-- Name: escuela; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.escuela (
    escuela_id integer NOT NULL,
    nombre character varying(150) NOT NULL,
    facultad_id integer NOT NULL
);


--
-- Name: escuela_curso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.escuela_curso (
    escuela_id integer NOT NULL,
    curso_id integer NOT NULL
);


--
-- Name: escuela_escuela_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.escuela_escuela_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: escuela_escuela_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.escuela_escuela_id_seq OWNED BY public.escuela.escuela_id;


--
-- Name: facultad; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.facultad (
    facultad_id integer NOT NULL,
    nombre character varying(150) NOT NULL
);


--
-- Name: facultad_facultad_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.facultad_facultad_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: facultad_facultad_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.facultad_facultad_id_seq OWNED BY public.facultad.facultad_id;


--
-- Name: grado_academico; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.grado_academico (
    grado_academico_id integer NOT NULL,
    nombre character varying(50) NOT NULL
);


--
-- Name: grado_academico_grado_academico_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.grado_academico_grado_academico_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: grado_academico_grado_academico_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.grado_academico_grado_academico_id_seq OWNED BY public.grado_academico.grado_academico_id;


--
-- Name: horario_detalle; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.horario_detalle (
    horario_detalle_id integer NOT NULL,
    detalle_curso_id integer NOT NULL,
    salon_id integer NOT NULL,
    dia_semana character varying(10) NOT NULL,
    hora_inicio time without time zone NOT NULL,
    hora_fin time without time zone NOT NULL,
    CONSTRAINT horario_detalle_check CHECK ((hora_fin > hora_inicio)),
    CONSTRAINT horario_detalle_dia_semana_check CHECK (((dia_semana)::text = ANY ((ARRAY['Lunes'::character varying, 'Martes'::character varying, 'Miercoles'::character varying, 'Jueves'::character varying, 'Viernes'::character varying, 'Sabado'::character varying])::text[])))
);


--
-- Name: horario_detalle_horario_detalle_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.horario_detalle_horario_detalle_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: horario_detalle_horario_detalle_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.horario_detalle_horario_detalle_id_seq OWNED BY public.horario_detalle.horario_detalle_id;


--
-- Name: modalidad; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.modalidad (
    modalidad_id integer NOT NULL,
    nombre character varying(50) NOT NULL
);


--
-- Name: modalidad_modalidad_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.modalidad_modalidad_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: modalidad_modalidad_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.modalidad_modalidad_id_seq OWNED BY public.modalidad.modalidad_id;


--
-- Name: periodo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.periodo (
    periodo_id integer NOT NULL,
    nombre character varying(20) NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin date NOT NULL,
    CONSTRAINT periodo_check CHECK ((fecha_fin > fecha_inicio))
);


--
-- Name: periodo_periodo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.periodo_periodo_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: periodo_periodo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.periodo_periodo_id_seq OWNED BY public.periodo.periodo_id;


--
-- Name: plaza; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.plaza (
    plaza_id integer NOT NULL,
    categoria_id integer NOT NULL,
    departamento_id integer NOT NULL,
    cantidad_total integer NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT plaza_cantidad_total_check CHECK ((cantidad_total >= 0))
);


--
-- Name: plaza_disponibilidad; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.plaza_disponibilidad AS
 SELECT p.plaza_id,
    p.categoria_id,
    p.departamento_id,
    p.cantidad_total,
    count(d.docente_id) AS cantidad_ocupada,
    (p.cantidad_total - count(d.docente_id)) AS cantidad_disponible
   FROM ((public.plaza p
     LEFT JOIN public.docente_categoria_historial h ON (((h.categoria_id = p.categoria_id) AND (h.fecha_fin IS NULL))))
     LEFT JOIN public.docente d ON (((d.docente_id = h.docente_id) AND (d.departamento_id = p.departamento_id))))
  GROUP BY p.plaza_id, p.categoria_id, p.departamento_id, p.cantidad_total;


--
-- Name: plaza_plaza_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.plaza_plaza_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: plaza_plaza_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.plaza_plaza_id_seq OWNED BY public.plaza.plaza_id;


--
-- Name: salon; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.salon (
    salon_id integer NOT NULL,
    categoria_salon_id integer NOT NULL,
    escuela_id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    capacidad integer NOT NULL,
    sede_id integer NOT NULL,
    CONSTRAINT salon_capacidad_check CHECK ((capacidad > 0))
);


--
-- Name: salon_salon_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.salon_salon_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: salon_salon_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.salon_salon_id_seq OWNED BY public.salon.salon_id;


--
-- Name: sede; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sede (
    sede_id integer NOT NULL,
    nombre character varying NOT NULL
);


--
-- Name: sede_sede_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sede_sede_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sede_sede_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sede_sede_id_seq OWNED BY public.sede.sede_id;


--
-- Name: categoria categoria_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categoria ALTER COLUMN categoria_id SET DEFAULT nextval('public.categoria_categoria_id_seq'::regclass);


--
-- Name: categoria_salon categoria_salon_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categoria_salon ALTER COLUMN categoria_salon_id SET DEFAULT nextval('public.categoria_salon_categoria_salon_id_seq'::regclass);


--
-- Name: curso curso_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curso ALTER COLUMN curso_id SET DEFAULT nextval('public.curso_curso_id_seq'::regclass);


--
-- Name: departamento departamento_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departamento ALTER COLUMN departamento_id SET DEFAULT nextval('public.departamento_departamento_id_seq'::regclass);


--
-- Name: detalle_curso detalle_curso_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_curso ALTER COLUMN detalle_curso_id SET DEFAULT nextval('public.detalle_curso_detalle_curso_id_seq'::regclass);


--
-- Name: docente docente_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente ALTER COLUMN docente_id SET DEFAULT nextval('public.docente_docente_id_seq'::regclass);


--
-- Name: docente_asignacion_periodo docente_asignacion_periodo_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_asignacion_periodo ALTER COLUMN docente_asignacion_periodo_id SET DEFAULT nextval('public.docente_asignacion_periodo_docente_asignacion_periodo_id_seq'::regclass);


--
-- Name: docente_categoria_historial historial_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_categoria_historial ALTER COLUMN historial_id SET DEFAULT nextval('public.docente_categoria_historial_historial_id_seq'::regclass);


--
-- Name: escuela escuela_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.escuela ALTER COLUMN escuela_id SET DEFAULT nextval('public.escuela_escuela_id_seq'::regclass);


--
-- Name: facultad facultad_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.facultad ALTER COLUMN facultad_id SET DEFAULT nextval('public.facultad_facultad_id_seq'::regclass);


--
-- Name: grado_academico grado_academico_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado_academico ALTER COLUMN grado_academico_id SET DEFAULT nextval('public.grado_academico_grado_academico_id_seq'::regclass);


--
-- Name: horario_detalle horario_detalle_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horario_detalle ALTER COLUMN horario_detalle_id SET DEFAULT nextval('public.horario_detalle_horario_detalle_id_seq'::regclass);


--
-- Name: modalidad modalidad_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modalidad ALTER COLUMN modalidad_id SET DEFAULT nextval('public.modalidad_modalidad_id_seq'::regclass);


--
-- Name: periodo periodo_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.periodo ALTER COLUMN periodo_id SET DEFAULT nextval('public.periodo_periodo_id_seq'::regclass);


--
-- Name: plaza plaza_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plaza ALTER COLUMN plaza_id SET DEFAULT nextval('public.plaza_plaza_id_seq'::regclass);


--
-- Name: salon salon_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salon ALTER COLUMN salon_id SET DEFAULT nextval('public.salon_salon_id_seq'::regclass);


--
-- Name: sede sede_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sede ALTER COLUMN sede_id SET DEFAULT nextval('public.sede_sede_id_seq'::regclass);


--
-- Name: categoria categoria_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categoria
    ADD CONSTRAINT categoria_nombre_key UNIQUE (nombre);


--
-- Name: categoria categoria_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categoria
    ADD CONSTRAINT categoria_pkey PRIMARY KEY (categoria_id);


--
-- Name: categoria_salon categoria_salon_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categoria_salon
    ADD CONSTRAINT categoria_salon_pkey PRIMARY KEY (categoria_salon_id);


--
-- Name: curso curso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curso
    ADD CONSTRAINT curso_pkey PRIMARY KEY (curso_id);


--
-- Name: departamento departamento_facultad_id_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departamento
    ADD CONSTRAINT departamento_facultad_id_nombre_key UNIQUE (facultad_id, nombre);


--
-- Name: departamento departamento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departamento
    ADD CONSTRAINT departamento_pkey PRIMARY KEY (departamento_id);


--
-- Name: detalle_curso detalle_curso_docente_id_curso_id_periodo_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_curso
    ADD CONSTRAINT detalle_curso_docente_id_curso_id_periodo_id_key UNIQUE (docente_id, curso_id, periodo_id);


--
-- Name: detalle_curso detalle_curso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_curso
    ADD CONSTRAINT detalle_curso_pkey PRIMARY KEY (detalle_curso_id);


--
-- Name: docente_asignacion_periodo docente_asignacion_periodo_docente_id_periodo_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_asignacion_periodo
    ADD CONSTRAINT docente_asignacion_periodo_docente_id_periodo_id_key UNIQUE (docente_id, periodo_id);


--
-- Name: docente_asignacion_periodo docente_asignacion_periodo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_asignacion_periodo
    ADD CONSTRAINT docente_asignacion_periodo_pkey PRIMARY KEY (docente_asignacion_periodo_id);


--
-- Name: docente_categoria_historial docente_categoria_historial_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_categoria_historial
    ADD CONSTRAINT docente_categoria_historial_pkey PRIMARY KEY (historial_id);


--
-- Name: docente docente_codigo_docente_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente
    ADD CONSTRAINT docente_codigo_docente_key UNIQUE (codigo_docente);


--
-- Name: docente docente_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente
    ADD CONSTRAINT docente_pkey PRIMARY KEY (docente_id);


--
-- Name: docente docente_tipo_documento_numero_documento_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente
    ADD CONSTRAINT docente_tipo_documento_numero_documento_key UNIQUE (tipo_documento, numero_documento);


--
-- Name: escuela_curso escuela_curso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.escuela_curso
    ADD CONSTRAINT escuela_curso_pkey PRIMARY KEY (escuela_id, curso_id);


--
-- Name: escuela escuela_facultad_id_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.escuela
    ADD CONSTRAINT escuela_facultad_id_nombre_key UNIQUE (facultad_id, nombre);


--
-- Name: escuela escuela_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.escuela
    ADD CONSTRAINT escuela_pkey PRIMARY KEY (escuela_id);


--
-- Name: facultad facultad_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.facultad
    ADD CONSTRAINT facultad_nombre_key UNIQUE (nombre);


--
-- Name: facultad facultad_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.facultad
    ADD CONSTRAINT facultad_pkey PRIMARY KEY (facultad_id);


--
-- Name: grado_academico grado_academico_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado_academico
    ADD CONSTRAINT grado_academico_nombre_key UNIQUE (nombre);


--
-- Name: grado_academico grado_academico_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado_academico
    ADD CONSTRAINT grado_academico_pkey PRIMARY KEY (grado_academico_id);


--
-- Name: horario_detalle horario_detalle_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horario_detalle
    ADD CONSTRAINT horario_detalle_pkey PRIMARY KEY (horario_detalle_id);


--
-- Name: modalidad modalidad_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modalidad
    ADD CONSTRAINT modalidad_nombre_key UNIQUE (nombre);


--
-- Name: modalidad modalidad_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modalidad
    ADD CONSTRAINT modalidad_pkey PRIMARY KEY (modalidad_id);


--
-- Name: horario_detalle no_choque_salon; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horario_detalle
    ADD CONSTRAINT no_choque_salon EXCLUDE USING gist (salon_id WITH =, dia_semana WITH =, tsrange(('2000-01-01'::date + hora_inicio), ('2000-01-01'::date + hora_fin)) WITH &&);


--
-- Name: periodo periodo_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.periodo
    ADD CONSTRAINT periodo_nombre_key UNIQUE (nombre);


--
-- Name: periodo periodo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.periodo
    ADD CONSTRAINT periodo_pkey PRIMARY KEY (periodo_id);


--
-- Name: plaza plaza_categoria_id_departamento_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plaza
    ADD CONSTRAINT plaza_categoria_id_departamento_id_key UNIQUE (categoria_id, departamento_id);


--
-- Name: plaza plaza_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plaza
    ADD CONSTRAINT plaza_pkey PRIMARY KEY (plaza_id);


--
-- Name: salon salon_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salon
    ADD CONSTRAINT salon_pkey PRIMARY KEY (salon_id);


--
-- Name: sede sede_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sede
    ADD CONSTRAINT sede_nombre_key UNIQUE (nombre);


--
-- Name: sede sede_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sede
    ADD CONSTRAINT sede_pkey PRIMARY KEY (sede_id);


--
-- Name: docente_categoria_historial_one_current; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX docente_categoria_historial_one_current ON public.docente_categoria_historial USING btree (docente_id) WHERE (fecha_fin IS NULL);


--
-- Name: ix_horario_detalle_curso; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_horario_detalle_curso ON public.horario_detalle USING btree (detalle_curso_id);


--
-- Name: ix_horario_salon; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_horario_salon ON public.horario_detalle USING btree (salon_id, dia_semana);


--
-- Name: ux_historial_categoria_activo; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX ux_historial_categoria_activo ON public.docente_categoria_historial USING btree (docente_id) WHERE (fecha_fin IS NULL);


--
-- Name: detalle_curso trg_detalle_curso_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_detalle_curso_updated_at BEFORE UPDATE ON public.detalle_curso FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- Name: docente_categoria_historial trg_historial_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_historial_updated_at BEFORE UPDATE ON public.docente_categoria_historial FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- Name: horario_detalle trg_horario_detalle_teacher_overlap; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_horario_detalle_teacher_overlap BEFORE INSERT OR UPDATE ON public.horario_detalle FOR EACH ROW EXECUTE FUNCTION public.check_teacher_schedule_overlap();


--
-- Name: plaza trg_plaza_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_plaza_updated_at BEFORE UPDATE ON public.plaza FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- Name: categoria categoria_grado_minimo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categoria
    ADD CONSTRAINT categoria_grado_minimo_id_fkey FOREIGN KEY (grado_minimo_id) REFERENCES public.grado_academico(grado_academico_id);


--
-- Name: curso curso_departamento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curso
    ADD CONSTRAINT curso_departamento_id_fkey FOREIGN KEY (departamento_id) REFERENCES public.departamento(departamento_id);


--
-- Name: departamento departamento_facultad_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departamento
    ADD CONSTRAINT departamento_facultad_id_fkey FOREIGN KEY (facultad_id) REFERENCES public.facultad(facultad_id);


--
-- Name: detalle_curso detalle_curso_curso_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_curso
    ADD CONSTRAINT detalle_curso_curso_id_fkey FOREIGN KEY (curso_id) REFERENCES public.curso(curso_id);


--
-- Name: detalle_curso detalle_curso_docente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_curso
    ADD CONSTRAINT detalle_curso_docente_id_fkey FOREIGN KEY (docente_id) REFERENCES public.docente(docente_id);


--
-- Name: detalle_curso detalle_curso_periodo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_curso
    ADD CONSTRAINT detalle_curso_periodo_id_fkey FOREIGN KEY (periodo_id) REFERENCES public.periodo(periodo_id);


--
-- Name: docente_asignacion_periodo docente_asignacion_periodo_categoria_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_asignacion_periodo
    ADD CONSTRAINT docente_asignacion_periodo_categoria_id_fkey FOREIGN KEY (categoria_id) REFERENCES public.categoria(categoria_id);


--
-- Name: docente_asignacion_periodo docente_asignacion_periodo_docente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_asignacion_periodo
    ADD CONSTRAINT docente_asignacion_periodo_docente_id_fkey FOREIGN KEY (docente_id) REFERENCES public.docente(docente_id);


--
-- Name: docente_asignacion_periodo docente_asignacion_periodo_modalidad_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_asignacion_periodo
    ADD CONSTRAINT docente_asignacion_periodo_modalidad_id_fkey FOREIGN KEY (modalidad_id) REFERENCES public.modalidad(modalidad_id);


--
-- Name: docente_asignacion_periodo docente_asignacion_periodo_periodo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_asignacion_periodo
    ADD CONSTRAINT docente_asignacion_periodo_periodo_id_fkey FOREIGN KEY (periodo_id) REFERENCES public.periodo(periodo_id);


--
-- Name: docente_categoria_historial docente_categoria_historial_categoria_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_categoria_historial
    ADD CONSTRAINT docente_categoria_historial_categoria_id_fkey FOREIGN KEY (categoria_id) REFERENCES public.categoria(categoria_id);


--
-- Name: docente_categoria_historial docente_categoria_historial_docente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente_categoria_historial
    ADD CONSTRAINT docente_categoria_historial_docente_id_fkey FOREIGN KEY (docente_id) REFERENCES public.docente(docente_id);


--
-- Name: docente docente_departamento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente
    ADD CONSTRAINT docente_departamento_id_fkey FOREIGN KEY (departamento_id) REFERENCES public.departamento(departamento_id);


--
-- Name: docente docente_grado_academico_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docente
    ADD CONSTRAINT docente_grado_academico_id_fkey FOREIGN KEY (grado_academico_id) REFERENCES public.grado_academico(grado_academico_id);


--
-- Name: escuela_curso escuela_curso_curso_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.escuela_curso
    ADD CONSTRAINT escuela_curso_curso_id_fkey FOREIGN KEY (curso_id) REFERENCES public.curso(curso_id);


--
-- Name: escuela_curso escuela_curso_escuela_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.escuela_curso
    ADD CONSTRAINT escuela_curso_escuela_id_fkey FOREIGN KEY (escuela_id) REFERENCES public.escuela(escuela_id);


--
-- Name: escuela escuela_facultad_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.escuela
    ADD CONSTRAINT escuela_facultad_id_fkey FOREIGN KEY (facultad_id) REFERENCES public.facultad(facultad_id);


--
-- Name: horario_detalle horario_detalle_detalle_curso_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horario_detalle
    ADD CONSTRAINT horario_detalle_detalle_curso_id_fkey FOREIGN KEY (detalle_curso_id) REFERENCES public.detalle_curso(detalle_curso_id);


--
-- Name: horario_detalle horario_detalle_salon_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horario_detalle
    ADD CONSTRAINT horario_detalle_salon_id_fkey FOREIGN KEY (salon_id) REFERENCES public.salon(salon_id);


--
-- Name: plaza plaza_categoria_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plaza
    ADD CONSTRAINT plaza_categoria_id_fkey FOREIGN KEY (categoria_id) REFERENCES public.categoria(categoria_id);


--
-- Name: plaza plaza_departamento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plaza
    ADD CONSTRAINT plaza_departamento_id_fkey FOREIGN KEY (departamento_id) REFERENCES public.departamento(departamento_id);


--
-- Name: salon salon_categoria_salon_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salon
    ADD CONSTRAINT salon_categoria_salon_id_fkey FOREIGN KEY (categoria_salon_id) REFERENCES public.categoria_salon(categoria_salon_id);


--
-- Name: salon salon_escuela_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salon
    ADD CONSTRAINT salon_escuela_id_fkey FOREIGN KEY (escuela_id) REFERENCES public.escuela(escuela_id);


--
-- Name: salon salon_sede_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salon
    ADD CONSTRAINT salon_sede_id_fkey FOREIGN KEY (sede_id) REFERENCES public.sede(sede_id);


--
-- PostgreSQL database dump complete
--

\unrestrict jU05A3qZBN4rMgmfvdiUZ6pQlKfdmSVcKx75xeYLnPQtjVxDTDmw1R8pwp14LyC

