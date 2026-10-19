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

-- Salones que usa Informática. INFO y POOL se asumen aulas, LAB laboratorios.
-- shortcut: capacidad 40 es un valor provisional; reemplazar por el aforo real de cada salón.
INSERT INTO public.salon (categoria_salon_id, escuela_id, nombre, capacidad, sede_id)
SELECT cs.categoria_salon_id, e.escuela_id, s.nombre, 40, se.sede_id
FROM (VALUES ('INFO 2', 'Aula'), ('INFO 3', 'Aula'), ('INFO 5', 'Aula'),
             ('LAB A', 'Laboratorio'), ('LAB B', 'Laboratorio'), ('LAB C', 'Laboratorio'),
             ('POOL 17', 'Aula'), ('POOL 18', 'Aula')) AS s(nombre, categoria)
JOIN public.categoria_salon cs ON cs.nombre = s.categoria
JOIN public.escuela e ON e.nombre = 'Informática'
JOIN public.sede se ON se.nombre = 'Trujillo';

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

-- Plan de estudios de la Escuela de Informática (sección 12 del currículo).
-- Horas T/P/L verificadas por columna contra el PDF oficial del plan 2018 (enlazado en https://inf.unitru.edu.pe/curriculos).
-- departamento NULL: "Lengua y literatura" no figura entre los departamentos del estatuto.
WITH plan (ciclo, codigo, nombre, tipo, ht, hp, hl, creditos, departamento) AS (VALUES
    (1,  '13024', 'Desarrollo personal',                                 'EG', 2, 2, 0, 3,  'Ciencias Psicológicas'),
    (1,  '13029', 'Lectura crítica y redacción de textos académicos',    'EG', 2, 2, 0, 3,  NULL),
    (1,  '13027', 'Física general',                                      'EG', 2, 4, 0, 4,  'Física'),
    (1,  '13021', 'Algoritmos y programación',                           'ES', 2, 2, 2, 4,  'Informática'),
    (1,  '13023', 'Desarrollo del pensamiento lógico matemático',        'EG', 1, 4, 0, 3,  'Matemáticas'),
    (1,  '13028', 'Introducción al análisis matemático',                 'EG', 2, 4, 0, 4,  'Matemáticas'),
    (1,  '13031', 'Taller de liderazgo y trabajo en equipo',             'EG', 0, 2, 0, 1,  'Ciencias Sociales'),
    (2,  '13040', 'Ética, convivencia humana y ciudadanía',              'EG', 2, 2, 0, 3,  'Ciencias Sociales'),
    (2,  '13043', 'Sociedad, cultura y ecología',                        'EG', 1, 4, 0, 3,  'Ciencias Sociales'),
    (2,  '13036', 'Cultura investigativa y pensamiento crítico',         'EG', 2, 2, 0, 3,  'Ciencias Sociales'),
    (2,  '13039', 'Estructura de datos',                                 'ES', 2, 2, 2, 4,  'Informática'),
    (2,  '13038', 'Estadística general',                                 'EG', 2, 4, 0, 4,  'Estadística'),
    (2,  '13034', 'Análisis matemático',                                 'EG', 2, 4, 0, 4,  'Matemáticas'),
    (2,  '13046', 'Taller de manejo de TIC',                             'EG', 0, 2, 0, 1,  'Informática'),
    (3,  '13629', 'Geometría analítica',                                 'EE', 2, 4, 0, 4,  'Matemáticas'),
    (3,  '13630', 'Paradigmas de lenguajes de programación',             'ES', 2, 2, 2, 4,  'Informática'),
    (3,  '13631', 'Estrategias algorítmicas',                            'ES', 2, 2, 2, 4,  'Informática'),
    (3,  '13632', 'Matemática discreta',                                 'EE', 2, 4, 0, 4,  'Matemáticas'),
    (3,  '13633', 'Física para ciencia de la computación',               'EE', 2, 0, 2, 3,  'Física'),
    (3,  '13634', 'Análisis numérico',                                   'EE', 2, 0, 2, 3,  'Matemáticas'),
    (4,  '13635', 'Computación gráfica',                                 'ES', 2, 2, 2, 4,  'Informática'),
    (4,  '13636', 'Organización de archivos',                            'ES', 2, 0, 2, 3,  'Informática'),
    (4,  '13637', 'Algoritmos y complejidad',                            'ES', 2, 2, 2, 4,  'Informática'),
    (4,  '13638', 'Lenguajes formales y autómatas',                      'ES', 2, 2, 2, 4,  'Informática'),
    (4,  '13639', 'Electrónica para computación',                        'ES', 2, 2, 2, 4,  'Informática'),
    (4,  '13640', 'Innovación y emprendimiento',                         'ES', 2, 2, 0, 3,  'Informática'),
    (5,  '13641', 'Base de datos I',                                     'ES', 2, 2, 2, 4,  'Informática'),
    (5,  '13642', 'Ingeniería de software I',                            'ES', 2, 2, 2, 4,  'Informática'),
    (5,  '13643', 'Inteligencia artificial I',                           'ES', 2, 2, 2, 4,  'Informática'),
    (5,  '13644', 'Compiladores',                                        'ES', 2, 2, 2, 4,  'Informática'),
    (5,  '13645', 'Técnicas digitales para computación',                 'ES', 2, 2, 2, 4,  'Informática'),
    (5,  '13646', 'Metodología de la investigación científica',          'ES', 2, 0, 0, 2,  'Informática'),
    (6,  '14068', 'Computación gráfica avanzada',                        'ES', 2, 2, 2, 4,  'Informática'),
    (6,  '14069', 'Base de datos II',                                    'ES', 2, 2, 2, 4,  'Informática'),
    (6,  '14070', 'Ingeniería de software II',                           'ES', 2, 2, 2, 4,  'Informática'),
    (6,  '14071', 'Inteligencia artificial II',                          'ES', 0, 2, 2, 2,  'Informática'),
    (6,  '14072', 'Comunicación de datos',                               'ES', 2, 2, 2, 4,  'Informática'),
    (6,  '14073', 'Arquitectura y organización de computadoras',         'ES', 2, 2, 2, 4,  'Informática'),
    (7,  '14074', 'Base de datos avanzada',                              'ES', 2, 2, 2, 4,  'Informática'),
    (7,  '14075', 'Desarrollo de software',                              'ES', 2, 2, 2, 4,  'Informática'),
    (7,  '14076', 'Percepción y visión por computadora',                 'ES', 2, 2, 2, 4,  'Informática'),
    (7,  '14077', 'Redes de computadoras I',                             'ES', 2, 2, 2, 4,  'Informática'),
    (7,  '14078', 'Sistemas operativos I',                               'ES', 2, 2, 2, 4,  'Informática'),
    (7,  '14079', 'Gestión de proyectos informáticos',                   'ES', 0, 2, 2, 2,  'Informática'),
    (8,  '14080', 'Robótica',                                            'ES', 2, 2, 2, 4,  'Informática'),
    (8,  '14081', 'Redes de computadoras II',                            'ES', 2, 2, 2, 4,  'Informática'),
    (8,  '14082', 'Sistemas operativos II',                              'ES', 2, 2, 2, 4,  'Informática'),
    (8,  '14083', 'Prácticas preprofesionales',                          'ES', 1, 9, 9, 10, 'Informática'),
    (9,  '14084', 'Interacción humano-computador',                       'ES', 2, 2, 2, 4,  'Informática'),
    (9,  '14085', 'Tópicos en base de datos',                            'ES', 2, 2, 2, 4,  'Informática'),
    (9,  'INF34', 'Tópicos en ingeniería de software',                   'ES', 2, 2, 2, 4,  'Informática'),
    (9,  '14087', 'Ingeniería de software avanzada',                     'ES', 2, 2, 2, 4,  'Informática'),
    (9,  '14088', 'Seguridad informática',                               'ES', 2, 2, 2, 4,  'Informática'),
    (9,  '14089', 'Proyecto de tesis',                                   'ES', 1, 2, 0, 2,  'Informática'),
    (10, '14090', 'Tópicos en tecnologías inmersivas',                   'ES', 2, 2, 2, 4,  'Informática'),
    (10, '14091', 'Sistemas de información',                             'ES', 2, 0, 2, 3,  'Informática'),
    (10, '14092', 'Ética para profesionales en informática',             'ES', 2, 2, 0, 3,  'Informática'),
    (10, '14093', 'Proyecto de competencia',                             'ES', 0, 2, 2, 2,  'Informática'),
    (10, '14094', 'Proyecto integrador',                                 'ES', 2, 4, 0, 4,  'Informática'),
    (10, 'INF43', 'Tesis',                                               'ES', 0, 2, 0, 1,  'Informática')
),
cursos AS (
    INSERT INTO public.curso (codigo, nombre, tipo, horas_teoria, horas_practica, horas_laboratorio, creditos, departamento_id)
    SELECT plan.codigo, plan.nombre, plan.tipo, plan.ht, plan.hp, plan.hl, plan.creditos, d.departamento_id
    FROM plan
    LEFT JOIN public.departamento d ON d.nombre = plan.departamento
    RETURNING curso_id, codigo
)
INSERT INTO public.escuela_curso (escuela_id, curso_id, ciclo)
SELECT e.escuela_id, c.curso_id, plan.ciclo
FROM cursos c
JOIN plan ON plan.codigo = c.codigo
JOIN public.escuela e ON e.nombre = 'Informática';

-- Requisitos que no son cursos quedan fuera: 14083 (haber aprobado hasta el ciclo VI),
-- 14089 (176 créditos aprobados), 14093 (cursos del área de la competencia elegida).
INSERT INTO public.curso_prerrequisito (curso_id, prerrequisito_id)
SELECT c.curso_id, p.curso_id
FROM (VALUES
    ('13039', '13021'), ('13034', '13028'),
    ('13629', '13034'), ('13631', '13039'), ('13633', '13027'), ('13634', '13034'),
    ('13635', '13629'), ('13635', '13039'), ('13636', '13039'), ('13637', '13631'), ('13637', '13632'),
    ('13638', '13632'), ('13639', '13633'),
    ('13641', '13636'), ('13642', '13630'), ('13643', '13637'), ('13644', '13638'), ('13645', '13639'),
    ('14068', '13635'), ('14069', '13641'), ('14070', '13642'), ('14071', '13643'), ('14072', '13645'), ('14073', '13645'),
    ('14074', '14069'), ('14075', '14070'), ('14076', '14071'), ('14077', '14072'), ('14078', '14073'), ('14079', '14070'),
    ('14080', '14071'), ('14081', '14077'), ('14082', '14078'),
    ('14084', '14070'), ('14085', '14069'), ('INF34', '14070'), ('14087', '14070'), ('14088', '14081'),
    ('14090', '14084'), ('14091', '14074'), ('14091', '14087'), ('14092', '13040'),
    ('14094', '14081'), ('14094', '14079'), ('14094', '13640'), ('14094', '14075'),
    ('INF43', '14089')
) AS r(curso, prerrequisito)
JOIN public.curso c ON c.codigo = r.curso
JOIN public.curso p ON p.codigo = r.prerrequisito;

-- Autocomprobación contra los totales del plan (215 créditos; 60 cursos; 47 prerrequisitos).
DO $$
BEGIN
    ASSERT (SELECT sum(creditos) FROM public.curso) = 215, 'la suma de créditos del plan no es 215';
    ASSERT (SELECT count(*) FROM public.escuela_curso) = 60, 'faltan cursos en el plan';
    ASSERT (SELECT count(*) FROM public.curso_prerrequisito) = 47, 'algún prerrequisito no encontró su curso';
END $$;

COMMIT;
