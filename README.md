# SoftwareHorario

Sistema de horarios docentes de la Universidad Nacional de Trujillo (PHP + PostgreSQL).

## Estructura

- `backend/`: conexión, sesión y consultas a la base de datos, y el script para crear usuarios.
- `frontend/`: páginas que ve el usuario. Es la única carpeta que se publica en el servidor web. `frontend/img/` tiene el escudo de la UNT, extraído del Manual Corporativo 2022.
- `database/`: `schema.sql` (estructura), `seed.sql` (datos iniciales) y `migrations/` (cambios aplicados sobre una base existente).
- `tests/`: prueba de la lógica de horarios contra la base configurada.

## Requisitos

- PHP 8.2 o superior con la extensión `pdo_pgsql`.
- PostgreSQL (probado con la versión 18).

## Puesta en marcha

1. Crea la base de datos y cárgala:
   ```
   createdb horarios
   psql -d horarios -f database/schema.sql
   psql -d horarios -f database/seed.sql
   ```
2. Copia `.env.example` a `.env` y pon tus credenciales. `.env` no se sube al repositorio.
3. Comprueba la conexión: `php backend/connection.php`
4. Crea los usuarios. La contraseña se pide por teclado y debe tener al menos 8 caracteres:
   ```
   php backend/create_user.php admin admin
   php backend/create_user.php jsalazar docente 5799
   ```
   El último argumento es el código del docente.
5. Desde la raíz del proyecto, inicia el servidor: `php -S localhost:8000`
6. Abre http://localhost:8000/frontend/

Si ya tenías la base creada, aplica solo las migraciones nuevas en orden, por ejemplo:
`psql -d horarios -f database/migrations/004_campus_and_teaching_load.sql`.

### Con XAMPP

1. Enlaza **solo** `frontend/` dentro de `htdocs`, nunca la raíz del proyecto, porque expondría `.env` y `.git/`. Desde la raíz del proyecto:
   ```
   sudo ln -s "$PWD/frontend" /opt/lampp/htdocs/SoftwareHorario
   ```
2. Apache de XAMPP corre como el usuario `daemon`, que no puede leer tu carpeta personal (en Fedora, `/home/<usuario>` tiene permisos `700`) y responde **403 Forbidden**. En un equipo de desarrollo, haz que Apache corra con tu usuario editando `/opt/lampp/etc/httpd.conf`:
   ```
   User <tu_usuario>
   Group <tu_usuario>
   ```
   En lugar de esto podrías dar `chmod o+x` a cada carpeta de la ruta, pero tu carpeta personal quedaría accesible para cualquier usuario del sistema.
3. Arranca solo Apache, desde el panel (`sudo /opt/lampp/manager-linux-x64.run`) o con `sudo /opt/lampp/lampp startapache`. No hace falta la MySQL de XAMPP, porque la base es PostgreSQL, que debe estar corriendo aparte. El PHP de XAMPP 8.2 ya trae `pdo_pgsql`. Si editas `httpd.conf` con Apache encendido, reinícialo.
4. Abre http://localhost/SoftwareHorario/ (`http://localhost` a secas muestra la portada de XAMPP).

## Uso

| Quién | Páginas |
|---|---|
| Público, sin login | **Horario:** filtra por periodo y, si quieres, por escuela y ciclo; se despliega Escuela → Ciclo (con su grilla semanal) → Curso → Sección. **Horario por salón:** Sede → Tipo de salón → Salón, con su grilla. **Estructura:** Universidad → Sedes → Facultades → Escuelas y Departamentos → Docentes. |
| Administrador | **Carga horaria:** crea las secciones de los cursos del plan (también las de repetición) y asigna un docente a cada parte (teoría, práctica, laboratorio); el árbol Ciclo → Curso → Sección marca los cursos sin sección. En el horario de cada sección confirma o descarta lo que proponen los docentes, eligiendo el salón, o agrega bloques directamente. **Docentes:** busca por código, apellidos, documento, facultad, departamento, categoría y modalidad; crea y edita docentes. **Salones:** registra salones y los busca por nombre, sede, tipo y escuela. |
| Docente | **Mi horario:** su horario semanal definitivo. **Horario deseado:** para cada parte de su carga propone día y hora; es una preferencia, no reserva salón ni es definitiva. |

- **Árboles:** cada nivel se expande y contrae; el botón **Expandir todo** abre o cierra todos a la vez. Al imprimir sale solo lo que está expandido.

- **Bloques de horario:** cada uno tiene su tipo de sesión (teoría, práctica o laboratorio) y su propio docente, así que teoría y práctica pueden tener docentes distintos.
- **Choques:** la base de datos rechaza que un salón o un docente tenga dos bloques que se crucen en el mismo periodo, y la página explica con qué bloque choca.
- **Impresión:** las grillas se pueden imprimir; la impresión oculta la barra de navegación y los filtros.

## Pruebas

```
php tests/schedule_test.php
```

Usa la base configurada en `.env` y borra al terminar todo lo que crea.

## Pendientes

### Prioridad alta
- [ ] Validar la carga docente: avisar cuando las horas programadas superan la asignación del periodo.
- [ ] Respetar el tipo de salón: avisar o impedir un bloque de laboratorio en un aula.
- [ ] Avance del periodo: secciones con horas sin programar (los cursos sin sección ya se marcan en Carga horaria).
- [ ] Choques por ciclo: evitar que dos cursos del mismo ciclo se crucen; para repetición, solo un aviso.
- [ ] Avisar al docente cuando sus propuestas se cruzan entre sí o con su horario definitivo.

### Para la presentación
- [ ] Cupo de la sección comparado con el aforo del salón.
- [ ] Exportar los horarios a CSV por docente o por ciclo.
- [ ] Copiar las secciones y bloques de un periodo al siguiente.

### Administración
- [ ] Página de periodos: crear y cerrar.
- [ ] Página de escuelas: cambiar su sede.
- [ ] Página de usuarios y cambio de contraseña.
- [ ] Edición del plan de estudios: cursos y prerrequisitos.

### Calidad y entrega
- [ ] Limitar los intentos fallidos de login.
- [ ] Pruebas de páginas y permisos.
- [ ] Probar en el XAMPP del profesor (PHP 8.2).
- [ ] Diagramas de casos de uso y entidad-relación, y un manual de usuario.

## Datos pendientes

Los docentes de `seed.sql` (Departamento Académico de Informática) provienen de fuentes no oficiales. Les faltan el grado académico y el documento de identidad, y a los contratados (B1/B2) su dedicación. Revisa los comentarios del archivo antes de usarlos oficialmente. Los salones tienen una capacidad provisional de 40. Todas las escuelas están en la sede Trujillo hasta confirmar cuáles funcionan en cada filial (columna `escuela.sede_id`).

El escudo y los colores provienen del Manual Corporativo de la UNT. El manual indica que el logo está protegido (INDECOPI, certificado N.° 00051701) y que su uso oficial requiere la aprobación de la Oficina de Comunicaciones e Imagen Institucional. La tipografía del manual es Trajan Pro, que es comercial; la página usa Cinzel, una alternativa libre de Google Fonts basada en las mismas capitales romanas.
