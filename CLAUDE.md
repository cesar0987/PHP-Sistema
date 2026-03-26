# Terracota — Sistema POS (Ferretería)

Sistema de Punto de Venta e Inventario para Terracota Construcciones. Desarrollado con Laravel 12 + Filament v3.

## Stack Tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.2+ / Laravel 12 |
| Panel Admin | Filament v3 + Livewire v3 |
| Base de datos | SQLite (desarrollo) / PostgreSQL (producción) |
| Frontend | Tailwind CSS v4 / Vite v7 |
| PDF | barryvdh/laravel-dompdf |
| Permisos | spatie/laravel-permission |
| Auditoría | spatie/laravel-activitylog |

## Estructura del Proyecto

```
app/
├── Filament/               # Panel administrativo (Filament v3)
│   ├── Pages/              # Páginas custom (Settings, CreditCalendar)
│   ├── Resources/          # 18 recursos CRUD
│   └── Widgets/            # 4 widgets del dashboard
├── Http/Controllers/       # Solo CashRegisterController (PDF)
├── Models/                 # 33 modelos Eloquent
├── Policies/               # 18 políticas de autorización
├── Providers/Filament/     # AdminPanelProvider
└── Services/               # Lógica de negocio (NUNCA en controllers/resources)
    ├── SaleService.php
    ├── InventoryService.php
    ├── PurchaseService.php
    ├── LocationService.php
    ├── CreditService.php
    ├── ReceiptService.php
    ├── SifenCdcService.php     # CDC 44 dígitos
    ├── SifenQrService.php      # URL QR con hash CSC
    └── SifenXmlService.php     # Generación XML SIFEN v150

config/
├── sifen.php               # Configuración fiscal SIFEN Paraguay
└── ...

database/migrations/        # 37+ migraciones
Obsidian/                   # Documentación y planes (REQUERIDO antes de implementar)
resources/views/pdf/        # Templates Blade para PDFs
tests/
├── Unit/                   # Tests de servicios
└── Feature/                # Tests de flujos completos
```

## Comandos de Desarrollo

```bash
composer dev        # Inicia servidor, queue, logs y vite en paralelo
composer test       # Corre PHPUnit con limpieza de caché
composer setup      # Instalación completa desde cero
php artisan migrate # Aplica migraciones pendientes
```

## Arquitectura — Reglas Críticas

### 1. Service Layer (OBLIGATORIO)
Toda la lógica de negocio vive en `app/Services/`. Los recursos Filament y controllers **solo** delegan al servicio correspondiente.

```php
// ✅ Correcto — en un Resource o Controller
$sale = app(SaleService::class)->createSale($data);

// ❌ Incorrecto — lógica de negocio en Resource
$sale = Sale::create([...]);
Stock::decrement(...);
```

### 2. BranchScope (AUTOMÁTICO)
Los modelos principales tienen `#[ScopedBy([BranchScope::class])]`. Los usuarios no-admin **solo ven datos de su sucursal**. El scope se aplica automáticamente. Nunca filtrarlo manualmente.

### 3. Transacciones de Base de Datos
Cualquier operación que toque múltiples tablas (ventas, stock, pagos) **debe** usar `DB::transaction()`.

### 4. Activity Log
Todos los modelos implementan `LogsActivity`. Los cambios se registran automáticamente. Los tests deben tener en cuenta que cada mutación genera actividad.

### 5. Soft Deletes
Los modelos críticos (Sale, Product, Customer, Supplier) usan `SoftDeletes`. **Nunca hacer hard delete** de estos registros.

### 6. Stock — Solo a través de InventoryService
El stock **solo se modifica** a través de `InventoryService`. Nunca modificar `Stock::quantity` directamente.

```php
// ✅ Correcto
$inventoryService->removeStock($variant, $warehouse, $quantity, [...]);

// ❌ Incorrecto
$stock->decrement('quantity', $quantity);
```

## Modelos Principales y Relaciones

```
Company
  └── Branch (has BranchScope)
        ├── Warehouse
        │     └── Stock ←── ProductVariant ←── Product
        ├── Sale (has BranchScope)
        │     ├── SaleItem ──► ProductVariant
        │     └── Payment
        └── Purchase (has BranchScope)
              └── PurchaseItem ──► ProductVariant
```

## SIFEN — Facturación Electrónica Paraguay

El sistema implementa SIFEN v150 (SET Paraguay). Ver `Obsidian/Plan_Sifen_XML.md` para el mapeo completo de campos.

| Servicio | Función |
|----------|---------|
| `SifenCdcService` | Genera el CDC de 44 dígitos con módulo 11. `buildBase()` auto-padea establecimiento, puntoExp y numeroDoc |
| `SifenQrService` | Construye la URL del QR con hash SHA256+CSC |
| `SifenXmlService` | Genera el XML completo `<rDE>` |

Variables de entorno SIFEN:
```env
SIFEN_ENV=test                              # test | production
SIFEN_CSC_ID=0001
SIFEN_CSC_VAL=ABCD0000000000000000000000000000
SIFEN_CERT_PASSWORD=
```

**Pendiente:** Firma digital RSA-SHA256 con `robrichards/xmlseclibs` y certificado `.p12` de la SET.

## Permisos y Roles

Spatie Permission gestiona roles y permisos. Los seeders están en `database/seeders/RolesAndPermissionsSeeder.php`.

Panel path: `/admin`
Branding: "Terracota Construcciones"
Color: Amber / Dark mode habilitado

## Testing

```bash
composer test
# o específicamente:
php artisan test tests/Unit/SifenCdcServiceTest.php
php artisan test tests/Unit/SaleServiceTest.php
```

Tests usan SQLite en memoria (`:memory:`). Ver `phpunit.xml`.

Suite actual: **58 tests / 144 assertions**.

Archivos de Feature tests incluyen:
- `AuthFlowTest.php`
- `CashRegisterFlowTest.php`

## Planificación (REQUERIDO)

**Antes de implementar cualquier feature**, crear documento en `Obsidian/` con:
- Nombre del feature
- Descripción y objetivo
- Enfoque técnico
- Checklist de tareas

Ver ejemplos en `Obsidian/Plan_Sifen_XML.md`, `Obsidian/Plan_Kendall_Kendall.md`.

## Variables de Entorno Clave

```env
APP_ENV=local
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SIFEN_ENV=test
```
