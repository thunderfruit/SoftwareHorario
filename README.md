# SoftwareHorario

Sistema de horarios docentes de la Universidad Nacional de Trujillo (PHP + PostgreSQL).

## Estructura

- `backend/`: conexión, sesión y consultas a la base de datos, y el script para crear usuarios.
- `frontend/`: páginas que ve el usuario. Es la única carpeta que se publica en el servidor web.
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

### Con XAMPP

Enlaza **solo** `frontend/` dentro de `htdocs`, nunca la raíz del proyecto, porque expondría `.env` y `.git/`:

```
sudo ln -s "$PWD/frontend" /opt/lampp/htdocs/SoftwareHorario
```

Luego abre http://localhost/SoftwareHorario/

## Uso

| Quién | Páginas |
|---|---|
| Público, sin login | Horario por escuela y ciclo; horario por salón, filtrable por tipo (aula, laboratorio, auditorio). |
| Administrador | **Secciones:** crea secciones de los cursos del plan, incluidas las de repitencia, y arma su horario por bloques. **Docentes:** busca, crea y edita docentes, su categoría y su asignación del periodo. **Salones:** registra los salones. |
| Docente | Su horario semanal. |

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
- [ ] Avance del periodo: cursos del plan sin sección y secciones con horas sin programar.
- [ ] Choques por ciclo: evitar que dos cursos del mismo ciclo se crucen; para repitencia, solo un aviso.

### Para la presentación
- [ ] Cupo de la sección comparado con el aforo del salón.
- [ ] Exportar los horarios a CSV por docente o por ciclo.
- [ ] Copiar las secciones y bloques de un periodo al siguiente.

### Administración
- [ ] Página de periodos: crear y cerrar.
- [ ] Página de usuarios y cambio de contraseña.
- [ ] Edición del plan de estudios: cursos y prerrequisitos.

### Calidad y entrega
- [ ] Limitar los intentos fallidos de login.
- [ ] Pruebas de páginas y permisos.
- [ ] Probar en el XAMPP del profesor (PHP 8.2).
- [ ] Diagramas de casos de uso y entidad-relación, y un manual de usuario.

## Datos pendientes

Los docentes de `seed.sql` (Departamento Académico de Informática) provienen de fuentes no oficiales. Les faltan el grado académico y el documento de identidad, y a los contratados (B1/B2) su dedicación. Revisa los comentarios del archivo antes de usarlos oficialmente. Los salones tienen una capacidad provisional de 40.
