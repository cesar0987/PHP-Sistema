# Deploy en Producción - Terracota POS

Este documento detalla los pasos para poner el sistema en línea en un entorno real de producción (VPS, Servidor Dedicado o Hosting con acceso SSH).

## 1. Preparación del Servidor
- **SO Recomendado**: Ubuntu 22.04 LTS / 24.04 LTS
- **Web Server**: Nginx o Apache2
- **PHP**: 8.2+ con extensiones (pdo_sqlite, curl, mbstring, dom, xml, zip, gd)
- **Node**: v18+ (Solo requerido para compilar en el servidor con `npm run build`)

## 2. Descarga y Dependencias
En el directorio protegido (usualmente `/var/www/terracota-pos`):
```bash
git clone <url> .
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

## 3. Configuración de Entorno (.env)
Asegura que las siguientes variables estén seteadas para producción:
```ini
APP_NAME="Terracota POS"
APP_ENV=production
APP_KEY=base64:.... (generado con php artisan key:generate)
APP_DEBUG=false
APP_URL=https://pos.terracota.com

DB_CONNECTION=sqlite
```

## 4. Instalación de Base de Datos y Permisos
Si es instalación desde cero:
```bash
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
```

**CRÍTICO - Permisos de carpetas**:
```bash
chown -R www-data:www-data storage bootstrap/cache database/database.sqlite
chmod -R 775 storage bootstrap/cache database
```

## 5. Optimización de Laravel
Para maximizar la velocidad en producción:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
php artisan filament:optimize
```
*(Nota: Nunca corras estos comandos en entorno local con `APP_DEBUG=true`).*

## 6. Configurar Servidor Web (Ej. Nginx)
El `root` debe apuntar siempre a la subcarpeta `public`:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name pos.terracota.com;
    root /var/www/terracota-pos/public;
 
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";
 
    index index.php;
 
    charset utf-8;
 
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
 
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 7. Migración Futura (SQLite -> PostgreSQL/MySQL)
Si el volumen de transacciones crece (ej. cientos de miles de registros al mes):
1. Hacer down del sistema (`php artisan down`).
2. Exportar los datos usando una herramienta de migración (ej. pgloader o un script en Python/PHP).
3. Cambiar `DB_CONNECTION` de `sqlite` a `pgsql`.
4. Importar la data exportada.
5. Levantar el sistema (`php artisan up`).
