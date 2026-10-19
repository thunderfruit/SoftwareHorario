# SoftwareHorario

Sistema de horarios docentes de la Universidad Nacional de Trujillo (PHP + PostgreSQL).

## Estructura

- `backend/`: conexión a la base de datos y consultas.
- `frontend/`: páginas que ve el usuario. Es la única carpeta que se publica en el servidor web.
- `database/`: `schema.sql` (estructura), `seed.sql` (datos iniciales) y `migrations/` (cambios aplicados sobre una base existente).

## Requisitos

- PHP 8 con la extensión `pdo_pgsql`.
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
4. Desde la raíz del proyecto, inicia el servidor: `php -S localhost:8000`
5. Abre http://localhost:8000/frontend/

### Con XAMPP

Enlaza **solo** `frontend/` dentro de `htdocs`, nunca la raíz del proyecto, porque expondría `.env` y `.git/`:

```
sudo ln -s "$PWD/frontend" /opt/lampp/htdocs/SoftwareHorario
```

Luego abre http://localhost/SoftwareHorario/

## Datos pendientes

Los docentes de `seed.sql` (Departamento Académico de Informática) provienen de fuentes no oficiales. Les faltan el grado académico y el documento de identidad, y a los contratados (B1/B2) su dedicación. Revisa los comentarios del archivo antes de usarlos oficialmente.
