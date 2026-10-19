-- Datos iniciales. Ejecutar una sola vez sobre una base recién creada con schema.sql.
BEGIN;

-- Facultades, escuelas y departamentos: estatuto reformado UNT 2021, pág. 22-26
-- (https://use-dpa.unitru.edu.pe/Assets/documentos/ESCUELASPROFESIONALES.pdf,
--  https://use-dpa.unitru.edu.pe/Assets/documentos/DEPARTAMENTOSACADÉMICOS.pdf).
INSERT INTO public.facultad (nombre) VALUES
    ('Ciencias Agropecuarias'),
    ('Ciencias Biológicas'),
    ('Ciencias Económicas'),
    ('Ciencias Físicas y Matemáticas'),
    ('Ciencias Sociales'),
    ('Derecho y Ciencias Políticas'),
    ('Educación y Ciencias de la Comunicación'),
    ('Enfermería'),
    ('Estomatología'),
    ('Farmacia y Bioquímica'),
    ('Ingeniería'),
    ('Ingeniería Química'),
    ('Medicina');

INSERT INTO public.sede (nombre) VALUES
    ('Trujillo'),
    ('Valle Jequetepeque'),
    ('Huamachuco'),
    ('Santiago de Chuco');

INSERT INTO public.escuela (facultad_id, nombre)
SELECT f.facultad_id, e.nombre
FROM (VALUES
    ('Ciencias Agropecuarias', 'Agronomía'),
    ('Ciencias Agropecuarias', 'Ingeniería Agrícola'),
    ('Ciencias Agropecuarias', 'Ingeniería Agroindustrial'),
    ('Ciencias Agropecuarias', 'Zootecnia'),
    ('Ciencias Biológicas', 'Ciencias Biológicas'),
    ('Ciencias Biológicas', 'Microbiología y Parasitología'),
    ('Ciencias Biológicas', 'Biología Pesquera'),
    ('Ciencias Económicas', 'Administración'),
    ('Ciencias Económicas', 'Contabilidad y Finanzas'),
    ('Ciencias Económicas', 'Economía'),
    ('Ciencias Físicas y Matemáticas', 'Estadística'),
    ('Ciencias Físicas y Matemáticas', 'Física'),
    ('Ciencias Físicas y Matemáticas', 'Informática'),
    ('Ciencias Físicas y Matemáticas', 'Matemáticas'),
    ('Ciencias Sociales', 'Antropología'),
    ('Ciencias Sociales', 'Arqueología'),
    ('Ciencias Sociales', 'Historia'),
    ('Ciencias Sociales', 'Trabajo Social'),
    ('Ciencias Sociales', 'Turismo'),
    ('Derecho y Ciencias Políticas', 'Derecho'),
    ('Derecho y Ciencias Políticas', 'Ciencia Política y Gobernabilidad'),
    ('Enfermería', 'Enfermería'),
    ('Estomatología', 'Estomatología'),
    ('Educación y Ciencias de la Comunicación', 'Ciencias de la Comunicación'),
    ('Educación y Ciencias de la Comunicación', 'Educación Inicial'),
    ('Educación y Ciencias de la Comunicación', 'Educación Primaria'),
    ('Educación y Ciencias de la Comunicación', 'Educación Secundaria Mención Idiomas'),
    ('Educación y Ciencias de la Comunicación', 'Educación Secundaria Mención Matemáticas'),
    ('Educación y Ciencias de la Comunicación', 'Educación Secundaria Mención Lengua y Literatura'),
    ('Educación y Ciencias de la Comunicación', 'Educación Secundaria Mención Ciencias Naturales'),
    ('Educación y Ciencias de la Comunicación', 'Educación Secundaria Mención Filosofía, Psicología y Ciencias Sociales'),
    ('Educación y Ciencias de la Comunicación', 'Educación Secundaria Mención Historia y Geografía'),
    ('Farmacia y Bioquímica', 'Farmacia y Bioquímica'),
    ('Ingeniería', 'Arquitectura y Urbanismo'),
    ('Ingeniería', 'Ingeniería Civil'),
    ('Ingeniería', 'Ingeniería Industrial'),
    ('Ingeniería', 'Ingeniería de Materiales'),
    ('Ingeniería', 'Ingeniería Mecánica'),
    ('Ingeniería', 'Ingeniería Mecatrónica'),
    ('Ingeniería', 'Ingeniería Metalúrgica'),
    ('Ingeniería', 'Ingeniería de Minas'),
    ('Ingeniería', 'Ingeniería de Sistemas'),
    ('Ingeniería Química', 'Ingeniería Química'),
    ('Ingeniería Química', 'Ingeniería Ambiental'),
    ('Medicina', 'Medicina')
) AS e(facultad, nombre)
JOIN public.facultad f ON f.nombre = e.facultad;

-- Erratas del PDF corregidas: "Microbiológica", "Ciencias Política", "Estadísticas", "Salud Publica",
-- y "Filosofía Humana" en Medicina, que se asume Fisiología Humana.
INSERT INTO public.departamento (facultad_id, nombre)
SELECT f.facultad_id, d.nombre
FROM (VALUES
    ('Ciencias Agropecuarias', 'Agronomía y Zootecnia'),
    ('Ciencias Agropecuarias', 'Ciencias Agroindustriales'),
    ('Ciencias Biológicas', 'Ciencias Biológicas'),
    ('Ciencias Biológicas', 'Microbiología y Parasitología'),
    ('Ciencias Biológicas', 'Pesquería'),
    ('Ciencias Biológicas', 'Química Biológica y Fisiología Animal'),
    ('Ciencias Económicas', 'Administración'),
    ('Ciencias Económicas', 'Contabilidad y Finanzas'),
    ('Ciencias Económicas', 'Economía'),
    ('Ciencias Físicas y Matemáticas', 'Estadística'),
    ('Ciencias Físicas y Matemáticas', 'Física'),
    ('Ciencias Físicas y Matemáticas', 'Informática'),
    ('Ciencias Físicas y Matemáticas', 'Matemáticas'),
    ('Ciencias Sociales', 'Arqueología y Antropología'),
    ('Ciencias Sociales', 'Ciencias Sociales'),
    ('Derecho y Ciencias Políticas', 'Derecho'),
    ('Derecho y Ciencias Políticas', 'Ciencia Política y Gobernabilidad'),
    ('Enfermería', 'Enfermería de la Mujer, Niño y Adolescente'),
    ('Enfermería', 'Salud del Adulto'),
    ('Enfermería', 'Salud Familiar y Comunitaria'),
    ('Estomatología', 'Ciencias Básicas Estomatológicas'),
    ('Estomatología', 'Estomatología'),
    ('Educación y Ciencias de la Comunicación', 'Ciencias de la Educación'),
    ('Educación y Ciencias de la Comunicación', 'Ciencias Psicológicas'),
    ('Educación y Ciencias de la Comunicación', 'Comunicación Social'),
    ('Educación y Ciencias de la Comunicación', 'Filosofía y Arte'),
    ('Educación y Ciencias de la Comunicación', 'Historia y Geografía'),
    ('Farmacia y Bioquímica', 'Farmacotecnia'),
    ('Farmacia y Bioquímica', 'Farmacología'),
    ('Farmacia y Bioquímica', 'Bioquímica'),
    ('Ingeniería', 'Ingeniería Civil, Arquitectura y Urbanismo'),
    ('Ingeniería', 'Ingeniería Industrial'),
    ('Ingeniería', 'Ingeniería de Materiales'),
    ('Ingeniería', 'Mecánica y Energía'),
    ('Ingeniería', 'Ingeniería Metalúrgica'),
    ('Ingeniería', 'Ingeniería de Minas'),
    ('Ingeniería', 'Ingeniería de Sistemas'),
    ('Ingeniería', 'Ingeniería Mecatrónica'),
    ('Ingeniería Química', 'Ingeniería Química'),
    ('Ingeniería Química', 'Ingeniería Ambiental'),
    ('Ingeniería Química', 'Química'),
    ('Medicina', 'Ciencias Básicas'),
    ('Medicina', 'Cirugía'),
    ('Medicina', 'Fisiología Humana'),
    ('Medicina', 'Ginecología y Obstetricia'),
    ('Medicina', 'Medicina'),
    ('Medicina', 'Medicina Preventiva y Salud Pública'),
    ('Medicina', 'Morfología Humana'),
    ('Medicina', 'Pediatría')
) AS d(facultad, nombre)
JOIN public.facultad f ON f.nombre = d.facultad;

INSERT INTO public.grado_academico (nombre) VALUES ('Bachiller'), ('Magíster'), ('Doctor');

-- Ley 30220, art. 85: los tres regímenes de dedicación.
INSERT INTO public.modalidad (nombre) VALUES ('Dedicación exclusiva'), ('Tiempo completo'), ('Tiempo parcial');

INSERT INTO public.categoria_salon (nombre) VALUES ('Aula'), ('Laboratorio'), ('Auditorio');

INSERT INTO public.periodo (nombre, fecha_inicio, fecha_fin) VALUES ('2026-II', '2026-08-24', '2026-12-19');

-- Principal exige doctorado; el resto, maestría.
INSERT INTO public.categoria (grado_minimo_id, nombre)
SELECT g.grado_academico_id, c.nombre
FROM (VALUES ('Principal', 'Doctor'), ('Asociado', 'Magíster'), ('Auxiliar', 'Magíster'),
             ('B1', 'Magíster'), ('B2', 'Magíster')) AS c(nombre, grado)
JOIN public.grado_academico g ON g.nombre = c.grado;

-- Docentes del Departamento Académico de Informática en el periodo 2026-II, según dos fuentes no oficiales
-- que coinciden: una lista previa y las hojas de asistencia del departamento.
-- Las hojas codifican categoría + dedicación: PP/AS/AX = Principal/Asociado/Auxiliar, TC/TP/DE.
-- Pendiente confirmar: 5443 figura como PPDE en una hoja y como Asociado T.C. en otra y en la lista previa.
-- Pendiente: dedicación de B1/B2 (sin ella no se crea su asignación del periodo).
-- documento: DNI (8 dígitos) o carné de extranjería (9-12 letras/dígitos); NULL si no se conoce.
-- shortcut: fecha_inicio de la categoría es el inicio de 2026-II; corregir con la fecha real de cada docente.
WITH t (codigo, apellidos, nombres, documento, categoria, dedicacion) AS (VALUES
    ('4779', 'CRUZ SILVA',           'JOSE GABRIEL',       NULL, 'Asociado',  'TC'),
    ('4798', 'CASTILLO DIESTRA',     'CARLOS ENRIQUE',     NULL, 'Asociado',  'TC'),
    ('4891', 'RODRIGUEZ MELQUIADES', 'JOSE ANTONIO',       NULL, 'Principal', 'TC'),
    ('4893', 'GUTIERREZ GUTIERREZ',  'JORGE LUIS',         NULL, 'Principal', 'TC'),
    ('5263', 'CRUZ FLORIAN',         'IRIS AUREA',         NULL, 'Auxiliar',  'TC'),
    ('5264', 'ARAUJO GONZALEZ',      'CRISTIAN',           NULL, 'Auxiliar',  'TC'),
    ('5408', 'GUEVARA RUIZ',         'RICARDO MANUEL',     NULL, 'Asociado',  'TC'),
    ('5443', 'SIFUENTES DIAZ',       'YENNY MILAGRITOS',   NULL, 'Asociado',  'TC'),
    ('5572', 'MENDOZA TORRES',       'EDWIN RAUL',         NULL, 'Asociado',  'TC'),
    ('5702', 'BRAVO ESCALANTE',      'JORGE DAVID',        NULL, 'Auxiliar',  'TC'),
    ('5703', 'PERALTA LUJAN',        'JOSE LUIS',          NULL, 'Asociado',  'TC'),
    ('5799', 'SALAZAR CAMPOS',       'JUAN ORLANDO',       NULL, 'Asociado',  'TC'),
    ('5920', 'GOMEZ MORALES',        'ANTHONY JOSE',       NULL, 'Asociado',  'TC'),
    ('5978', 'POLO ROMERO',          'VICTOR JAIME',       NULL, 'Auxiliar',  'TC'),
    ('6173', 'PEDRO HUAMAN',         'LIZ SOFIA RAYMUNDA', NULL, 'Auxiliar',  'TC'),
    ('6326', 'PACHECO TORRES',       'JUAN FRANCISCO',     NULL, 'Auxiliar',  'TP'),
    ('6461', 'QUISPE VARON',         'CELESTINO MEDARDO',  NULL, 'Auxiliar',  'TC'),
    ('6866', 'ABANTO CORCUERA',      'MANUEL REGILDER',    NULL, 'B1',        NULL),
    ('6918', 'ALVARADO RAMOS',       'JORGE LUIS',         NULL, 'B1',        NULL),
    ('6963', 'URQUIZO GOMEZ',        'YOSIP VLADIMIR',     NULL, 'B2',        NULL),
    ('7253', 'CAJACHUAN TERRONES',   'LOURDES NATHALIE',   NULL, 'B1',        NULL),
    ('7322', 'APAZA CHAVEZ',         'WILMER AUFREDY',     NULL, 'B1',        NULL),
    ('7360', 'CASTRO RODRIGUEZ',     'MAX LEOVIGILDO',     NULL, 'B2',        NULL)
),
dedicacion (codigo, modalidad, horas) AS (VALUES
    ('DE', 'Dedicación exclusiva', 40),
    ('TC', 'Tiempo completo', 40),
    ('TP', 'Tiempo parcial', 10)
),
inserted AS (
    INSERT INTO public.docente (codigo_docente, apellidos, nombres, tipo_documento, numero_documento, departamento_id)
    SELECT t.codigo, t.apellidos, t.nombres,
           CASE WHEN t.documento !~ '^[0-9]{8}$' THEN 'CE' ELSE 'DNI' END,
           t.documento, d.departamento_id
    FROM t
    JOIN public.departamento d ON d.nombre = 'Informática'
    RETURNING docente_id, codigo_docente
),
historial AS (
    INSERT INTO public.docente_categoria_historial (docente_id, categoria_id, fecha_inicio)
    SELECT i.docente_id, c.categoria_id, DATE '2026-08-24'
    FROM inserted i
    JOIN t ON t.codigo = i.codigo_docente
    JOIN public.categoria c ON c.nombre = t.categoria
)
INSERT INTO public.docente_asignacion_periodo (docente_id, periodo_id, modalidad_id, categoria_id, horas_asignadas)
SELECT i.docente_id, p.periodo_id, m.modalidad_id, c.categoria_id, ded.horas
FROM inserted i
JOIN t ON t.codigo = i.codigo_docente
JOIN dedicacion ded ON ded.codigo = t.dedicacion
JOIN public.modalidad m ON m.nombre = ded.modalidad
JOIN public.categoria c ON c.nombre = t.categoria
JOIN public.periodo p ON p.nombre = '2026-II';

COMMIT;
