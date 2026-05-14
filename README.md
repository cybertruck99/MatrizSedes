# MatrizSedes

Sistema web base para la **Matriz de Seguimiento del Área de Proyectos y Planificación - SEDES Potosí**.

## Tecnologías

- Laravel 11
- PHP 8.2 o superior
- MySQL
- Blade + CSS propio responsivo

## Instalación

```bat
cd ruta\donde\descomprimiste\MatrizSedes
composer install
copy .env.example .env
php artisan key:generate
```

Crear base de datos en MySQL:

```sql
CREATE DATABASE matriz_sedes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Configurar credenciales de MySQL en `.env` si corresponde:

```env
DB_DATABASE=matriz_sedes
DB_USERNAME=root
DB_PASSWORD=
```

Ejecutar migraciones y datos de prueba:

```bat
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Abrir:

```text
http://127.0.0.1:8000
```

## Usuarios de prueba

| Rol | Usuario | Contraseña |
|---|---|---|
| Admin | ADM001 | admin123 |
| Técnico | TEC001 | tecnico123 |
| User | USR001 | user123 |

## Módulos incluidos

- Login único con redirección por rol.
- Panel Admin.
- CRUD de registros/tareas.
- CRUD de usuarios con historial de retirados.
- Matriz de seguimiento con cumplimiento y pintado por estado.
- CRUD de días especiales que no cuentan como días hábiles.
- Panel User con tareas, perfil y carga de archivo.
- Panel Técnico con consulta de matriz, usuarios y tareas propias.
