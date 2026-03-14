# Runbook Técnico - Terracota POS

Este documento está destinado al equipo de IT, DevSecOps o Administradores de Sistemas que deban desplegar, mantener o auditar la infraestructura del Punto de Venta Terracota.

## Índice
1. [Requisitos Previos](#requisitos-previos)
2. [Instalación y Despliegue Local](#instalación-y-despliegue-local)
3. [Base de Datos SQLite y Backups](#base-de-datos-sqlite-y-backups)
4. [Manejo de Errores Comunes](#manejo-de-errores-comunes)
5. [Tareas Programadas (Cron)](#tareas-programadas-cron)

---

## Requisitos Previos

- **PHP**: Versión 8.2 o superior (probado en 8.3). Exigidas extensiones: `pdo_sqlite`, `mbstring`, `xml`, `cURL`, `zip`.
- **Composer**: Versión 2.x para gestión de paquetes PHP.
- **Node.js & NPM**: Versiones LTS (ej. Node 18+) para compilar activos (Filament/Tailwind).
- **Git**: (Opcional, pero recomendado para pull de actualizaciones).

---

## Instalación y Despliegue Local

### 1. Clonar el Repositorio
```bash
git clone <url_del_repo> terracota-pos
cd terracota-pos
```

### 2. Variables de Entorno
Clonar el archivo de configuración base y adaptarlo:
```bash
cp .env.example .env
```
Confirma que la base de datos esté ajustada a SQLite:
```ini
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
```

### 3. Instalar Dependencias
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```
*(Nota: Omite `--no-dev` si es un entorno local de pruebas).*

### 4. Preparar Base de Datos
Crea el archivo físico de la BD si no existe, genera la key de Laravel, corre migraciones y el seeder vital.
```bash
touch database/database.sqlite
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
```
*(Es importante el flag `--force` si estás en entorno de Producción `APP_ENV=production`).*

### 5. Iniciar Aplicación
Para entornos locales, usar el servidor integrado de Artisan:
```bash
php artisan serve
```
Para Servidor Linux (Apache/Nginx):
Apunta el *DocumentRoot* a la carpeta `/public` y asegúrate de dar permisos de escritura a `storage` y `bootstrap/cache`:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Base de Datos SQLite y Backups

Dado que el sistema utiliza **SQLite** (un archivo único en el disco duro), los respaldos no requieren dumps SQL pesados, pero exigen sumo cuidado con el bloqueo de archivos (File Locks).

**Ubicación de la BD:**
`database/database.sqlite`

### Política de Backups
Se recomienda hacer un copiado de este archivo a la nube (S3, Dropbox) o a un disco externo diariamente o cada 12 horas.

**Comando de Backup Seguro:**
Debido a que el archivo puede estar bloqueado o transaccionando, prefiere usar la CLI nativa de `sqlite3` para hacer el volcado seguro en "caliente" (Online Backup API):

```bash
sqlite3 database/database.sqlite ".backup 'backups/db_backup_$(date +%Y%m%d_%H%M).sqlite'"
```
*(Este comando permite copiar la base de datos sin interrumpir lecturas o escrituras de la aplicación en el proceso).*

---

## Manejo de Errores Comunes

### Error: `Database is locked` (500 Internal Server Error)
- **Causa**: Múltiples procesos PHP intentando escribir masivamente al mismo tiempo, o un comando bloqueante que no finalizó (ej. migración a media ejecución).
- **Solución 1**: Reiniciar el servicio PHP-FPM / Apache o el comando `php artisan serve`.
- **Solución 2**: Verificar que la carpeta `database/` tenga permisos totales de lectura y escritura para el usuario del servidor web.
- **Solución 3**: En SQLite, configurar un timeout mayor si la concurrencia es alta. (Edita `config/database.php`, array `sqlite`, `busy_timeout => 5000`).

### Error: `PermissionDoesNotExist` (Falta un permiso en producción)
- **Solución**: Refrescar el caché de Spatie Permissions y asegurarse de correr el seeder.
```bash
php artisan permission:cache-reset
php artisan db:seed --class=RolesAndPermissionsSeeder
```

---

## Tareas Programadas (Cron)

El sistema Laravel requiere la ejecución del *scheduler* cada minuto. Añade la siguiente línea al crontab de tu servidor Ubuntu/Debian (`crontab -e -u www-data`):

```bash
* * * * * cd /ruta/al/proyecto/terracota-pos && php artisan schedule:run >> /dev/null 2>&1
```

Actualmente, el sistema manejaría (si se configurara) la limpieza de tokens o reportes nocturnos por esta vía.
