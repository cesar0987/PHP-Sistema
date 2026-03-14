# Sistema POS Ferretería - Laravel + Filament

Sistema de punto de venta (POS) para ferretería con gestión de inventario, ubicaciones, ventas y reportes.

## Stack

- **PHP** 8.3+
- **Laravel** 12.x
- **Filament** v3 (Admin Panel)
- **Livewire** v3 (Componentes interactivos)
- **SQLite** (desarrollo) / **PostgreSQL** (producción)

## Características

- CRUD completo de productos, categorías, proveedores, clientes
- Sistema de ubicaciones A → Z → AA → AB
- Stock movements (nunca modificar directo)
- POS con lector de código de barras
- Caja diaria
- Métodos de pago múltiple (efectivo, tarjeta, transferencia, QR)
- PDF tickets/facturas/recibos
- Dashboard con widgets
- Reportes de ventas y stock

## Instalación

```bash
# Instalar dependencias
composer install

# Copiar archivo de entorno
cp .env.example .env

# Generar clave
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# (Opcional) Poblar con datos de prueba
php artisan db:seed

# Iniciar servidor
php artisan serve
```

## Credenciales de prueba

- **URL:** http://localhost:8000/admin
- **Email:** admin@ferreteria.com
- **Password:** password

## Estructura

```
app/
├── Filament/
│   ├── Resources/    # Recursos del admin
│   ├── Pages/        # Páginas personalizadas
│   └── Widgets/      # Widgets del dashboard
├── Livewire/         # Componentes interactivos (POS)
├── Models/           # Modelos Eloquent
└── Services/        # Lógica de negocio
    ├── SaleService.php
    ├── InventoryService.php
    ├── PurchaseService.php
    ├── LocationService.php
    └── ReceiptService.php
```

## Comandos útiles

```bash
# Limpiar cache
php artisan cache:clear

# Ver rutas
php artisan route:list

# Ejecutar tests
php artisan test

# Instalar Filament
php artisan filament:install
```

## Paquetes instalados

| Paquete | Uso |
|---------|-----|
| filament/filament | Panel admin |
| livewire/livewire | POS interactivo |
| spatie/laravel-permission | Roles y permisos |
| spatie/laravel-activitylog | Auditoría |
| barryvdh/laravel-dompdf | PDF tickets/facturas |

## Licencia

MIT
