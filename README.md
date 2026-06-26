# MatrizSedes - Matriz de Seguimiento V3.0

Sistema web profesional para la Matriz de Seguimiento de la Unidad de Planificacion y Proyectos del Servicio Departamental de Salud - SEDES Potosi.

## Funciones principales

- Acceso por roles: administrador, tecnico y usuario.
- Paneles diferenciados por rol.
- Creacion, edicion, revision y seguimiento de tareas institucionales.
- Carga multiple de archivos con nombres originales.
- Perfil de tarea para usuarios, tecnicos y revision administrativa.
- Notificaciones visuales para tareas nuevas, archivos enviados y archivos actualizados.
- Control de cumplimiento, observaciones y estados de revision.
- Reportes por tarea, semanales y por rango de fechas.
- Gestion de usuarios, perfiles, fotografias y recuperacion asistida de contrasenas.
- Dias especiales y feriados nacionales fijos precargados.
- Control de sesiones activas, inactividad y limitacion basica contra exceso de solicitudes.
- Diseno responsivo para PC y celular.

## Requisitos del servidor

- PHP 8.2 o superior.
- Composer 2.
- MySQL 8 o MariaDB compatible.
- Servidor web Apache o Nginx.
- Extensiones PHP habituales de Laravel: OpenSSL, PDO, PDO MySQL, Mbstring, Tokenizer, XML, Ctype, JSON, Fileinfo, BCMath.
- Permisos de escritura en `storage/` y `bootstrap/cache/`.
- LibreOffice o soffice instalado en el servidor para generar reportes PDF. Si no esta disponible, el sistema entregara el reporte en DOCX como respaldo.

## Instalacion en servidor

1. Copiar o clonar el proyecto en el servidor.

```bash
git clone https://github.com/cybertruck99/MatrizSedes.git
cd MatrizSedes
```

2. Instalar dependencias PHP.

```bash
composer install --no-dev --optimize-autoloader
```

3. Crear el archivo de configuracion.

```bash
cp .env.example .env
php artisan key:generate
```

En Windows:

```bat
copy .env.example .env
php artisan key:generate
```

4. Crear la base de datos MySQL.

```sql
CREATE DATABASE matriz_sedes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

5. Configurar `.env`.

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dominio-o-ip-del-servidor

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=matriz_sedes
DB_USERNAME=usuario_mysql
DB_PASSWORD=contrasena_mysql

FILESYSTEM_DISK=public
```

Si LibreOffice no esta en el PATH del servidor, agregar la ruta:

```env
SOFFICE_PATH=/usr/bin/soffice
```

En Windows puede ser:

```env
SOFFICE_PATH="C:/Program Files/LibreOffice/program/soffice.exe"
```

6. Ejecutar migraciones y datos iniciales.

```bash
php artisan migrate --seed
php artisan storage:link
```

7. Dar permisos de escritura al servidor web.

Linux:

```bash
chmod -R 775 storage bootstrap/cache
```

Windows/IIS o Laragon: verificar que el usuario del servidor web tenga escritura en:

```text
storage
bootstrap/cache
```

8. Optimizar para produccion.

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Configuracion del servidor web

El dominio o virtual host debe apuntar a la carpeta:

```text
MatrizSedes/public
```

No se debe exponer directamente la raiz del proyecto.

### Apache

Activar `mod_rewrite` y permitir `.htaccess`.

### Nginx

Usar `public` como `root` y redirigir las solicitudes a `index.php`.

## Admin base inicial

Al instalar por primera vez existe una cuenta administradora base:

```text
Usuario: adminbase1
Contrasena: admin123
```

Esta cuenta es solo para el primer ingreso. Al acceder por primera vez el sistema solicitara cambiar usuario y contrasena. Despues de configurar la cuenta base, el aviso ya no volvera a mostrarse.

Importante: no eliminar la ultima cuenta administradora. El sistema bloquea esa accion para evitar quedar sin acceso administrativo.

## Uso local rapido

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Abrir:

```text
http://127.0.0.1:8000
```

## Notas de entrega

El paquete de instalacion no incluye:

- `vendor/`
- `node_modules/`
- `.env`
- archivos subidos por usuarios
- fotos de perfil cargadas
- sesiones
- cache
- logs
- ZIPs previos

Estos archivos se generan o configuran en el servidor durante la instalacion.
