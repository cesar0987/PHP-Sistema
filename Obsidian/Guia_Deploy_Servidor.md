# Guía de Deploy — Servidor Local

> **Última actualización:** 26/03/2026
> **Stack:** Laravel 12 + Filament v3 + PHP 8.3 + SQLite o PostgreSQL

---

## Índice

1. [Requisitos del servidor](#1-requisitos-del-servidor)
2. [Copiar el proyecto](#2-copiar-el-proyecto)
3. [Instalar dependencias](#3-instalar-dependencias)
4. [Configurar .env](#4-configurar-env)
5. [Base de datos](#5-base-de-datos)
6. [Permisos de carpetas](#6-permisos-de-carpetas)
7. [Configurar Nginx](#7-configurar-nginx)
8. [Queue Worker](#8-queue-worker)
9. [Optimizar para producción](#9-optimizar-para-producción)
10. [Crear usuario admin](#10-crear-usuario-admin)
11. [Checklist final](#11-checklist-final)

---

## 1. Requisitos del servidor

| Componente | Versión mínima |
|------------|----------------|
| PHP | 8.2+ |
| Nginx o Apache | cualquier reciente |
| Composer | 2.x |
| Node.js + npm | 18+ |
| SQLite **o** PostgreSQL | según lo que elijas |

### Instalar PHP y extensiones

```bash
sudo apt update
sudo apt install php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-fileinfo \
  php8.3-tokenizer php8.3-sqlite3 php8.3-pgsql
```

### Instalar Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Instalar Node.js

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs
```

---

## 2. Copiar el proyecto

**Opción A — desde Git (recomendado):**

```bash
git clone <tu-repo> /var/www/terracota
```

**Opción B — copiar manualmente (excluir vendor y node_modules):**

```bash
rsync -av --exclude=vendor --exclude=node_modules \
  /origen/ usuario@servidor:/var/www/terracota/
```

> ⚠️ La carpeta `public/build/` tampoco suele ir en git. Tenés que generarla en el servidor con `npm run build` o copiarla manualmente.

---

## 3. Instalar dependencias

```bash
cd /var/www/terracota

composer install --no-dev --optimize-autoloader

npm ci
npm run build          # genera public/build/ — solo se hace una vez
```

---

## 4. Configurar .env

```bash
cp .env.example .env
php artisan key:generate
```

Editá el `.env` con los valores del nuevo servidor:

```env
APP_NAME="Terracota Construcciones"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.X     # IP o dominio del servidor

# --- Si usás SQLite (más simple) ---
DB_CONNECTION=sqlite

# --- Si usás PostgreSQL ---
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=terracota
DB_USERNAME=terracota_user
DB_PASSWORD=contraseña_segura

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# SIFEN (si aplica)
SIFEN_ENV=test
SIFEN_CSC_ID=0001
SIFEN_CSC_VAL=ABCD0000000000000000000000000000
```

---

## 5. Base de datos

### Opción A — SQLite

```bash
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=ReceiptTemplateSeeder
```

### Opción B — PostgreSQL

```bash
# Crear la base de datos y usuario
sudo -u postgres psql -c "CREATE DATABASE terracota;"
sudo -u postgres psql -c "CREATE USER terracota_user WITH PASSWORD 'xxx';"
sudo -u postgres psql -c "GRANT ALL ON DATABASE terracota TO terracota_user;"

# Migrar y sembrar
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=ReceiptTemplateSeeder
```

### Restaurar backup de Odoo (si migrás datos existentes)

Ver [[Manual_Carga_Stock]] para importar productos desde CSV usando el importador del sistema.

---

## 6. Permisos de carpetas

```bash
chown -R www-data:www-data /var/www/terracota
chmod -R 755 /var/www/terracota/storage
chmod -R 755 /var/www/terracota/bootstrap/cache

php artisan storage:link
```

---

## 7. Configurar Nginx

Crear el archivo `/etc/nginx/sites-available/terracota`:

```nginx
server {
    listen 80;
    server_name 192.168.1.X;   # reemplazá con tu IP o dominio

    root /var/www/terracota/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;   # necesario para subir CSVs y PDFs
}
```

Activar y recargar:

```bash
ln -s /etc/nginx/sites-available/terracota /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

## 8. Queue Worker

El sistema usa `QUEUE_CONNECTION=database`. Sin el worker activo, las notificaciones y logs de actividad pueden no procesarse.

**Para probar manualmente:**

```bash
php artisan queue:work --daemon
```

**Para producción — crear servicio systemd:**

Crear `/etc/systemd/system/terracota-queue.service`:

```ini
[Unit]
Description=Terracota Queue Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/terracota
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --timeout=60
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
systemctl enable terracota-queue
systemctl start terracota-queue
systemctl status terracota-queue
```

---

## 9. Optimizar para producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
```

> Si modificás el `.env` o cualquier archivo de configuración, volvé a correr estos comandos.

Para limpiar los caches:

```bash
php artisan optimize:clear
```

---

## 10. Crear usuario admin

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name'      => 'Admin',
    'email'     => 'admin@terracota.com',
    'password'  => bcrypt('tu_password'),
    'branch_id' => 1,
])->assignRole('admin');
```

> ⚠️ El `branch_id` debe existir en la tabla `branches`. Si no creaste sucursales todavía, creá una primero desde el panel o con un seeder.

---

## 11. Checklist final

- [ ] PHP 8.3 + todas las extensiones instaladas
- [ ] `composer install --no-dev` ejecutado
- [ ] `npm run build` ejecutado (genera `public/build/`)
- [ ] `.env` configurado (`APP_ENV=production`, `APP_DEBUG=false`)
- [ ] `php artisan key:generate` ejecutado
- [ ] Migraciones corridas (`php artisan migrate --force`)
- [ ] Seeders corridos (`RolesAndPermissionsSeeder`, `ReceiptTemplateSeeder`)
- [ ] Permisos de `storage/` y `bootstrap/cache/` asignados a `www-data`
- [ ] `php artisan storage:link` ejecutado
- [ ] Nginx configurado apuntando a `/public`
- [ ] Queue worker activo como servicio
- [ ] Caches de producción generados (`php artisan optimize`)
- [ ] Usuario admin creado y acceso verificado en `/admin`
