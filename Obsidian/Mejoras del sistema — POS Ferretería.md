

> **Stack:** Laravel · Filament v3 · Livewire v3 · SQLite (dev)  
> **Nivel:** Semi-senior · Desarrollo con IA + MCP  
> **Áreas:** Seguridad · Performance · UX · Integraciones · Testing · Documentación

---

## Progreso general

| Área                        | Items | Estado     |
| --------------------------- | ----- | ---------- |
| Seguridad y auditoría       | 10    | ✅✅⬜⬜✅⬜⬜✅✅✅ |
| Performance y escalabilidad | 10    | ✅⬜✅✅✅⬜⬜⬜⬜✅ |
| Experiencia de usuario (UX) | 10    | ⬜⬜⬜⬜✅✅✅⬜⬜✅ |
| Integraciones externas      | 9     | ⬜⬜⬜⬜⬜⬜⬜⬜⬜  |
| Testing y calidad           | 10    | ⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜ |
| Documentación               | 9     | ✅✅⬜⬜⬜⬜⬜⬜⬜  |

> **Última actualización:** Sesión 5 (14/03/2026)  
> **Avances recientes:** SoftDeletes en 13 modelos, LogsActivity en 15 modelos, ActivityResource con pestañas por categoría, handler global de errores de BD, InventoryCount en grupo Inventario

---

## 1. Seguridad y auditoría

> Prioridad alta — implementar desde el inicio del proyecto

### Autenticación y accesos

- [ ] **Activar 2FA para usuarios admin**
  - Paquete: `Laravel Fortify` o `Jetstream`
  - Aplicar solo a roles admin y supervisor

- [x] **Bloqueo por intentos fallidos de login**
  - `throttle` middleware en rutas de autenticación
  - Guardar `login_attempts` en tabla `users`

- [ ] **Tokens de API con scopes por rol**
  - `Laravel Sanctum` con `->createToken('pos', ['sale:create'])`
  - Cada rol tiene abilities distintas

- [x] **Expiración automática de sesiones inactivas**
  - `config/session.php` → ajustar `lifetime` (ej. 120 min)
  - Redirigir al login con mensaje claro

- [x] **Logs de auditoría en cada acción sensible**
  - Paquete: `spatie/laravel-activitylog`
  - Registrar: ventas, ajustes de stock, anulaciones, login

### Datos y base de datos

- [ ] **Nunca exponer IDs secuenciales en URLs**
  - Usar `UUIDs` como primary key o `hashids`
  - `$table->uuid('id')->primary()` en migraciones

- [ ] **Validar y sanitizar todos los inputs**
  - `Form Requests` de Laravel para cada operación
  - Nunca validar directamente en controllers

- [x] **Encriptar datos sensibles en BD**
  - `encrypt()` / `decrypt()` de Laravel
  - Aplicar en: RUC, datos bancarios, tokens de integración

- [x] **Backups automáticos diarios**
  - Paquete: `spatie/laravel-backup`
  - Destino: S3, Google Drive o disco local externo

- [x] **Rate limiting en endpoints críticos**
  - `RateLimiter::for('pos', ...)` en `RouteServiceProvider`
  - Aplicar en: login, creación de ventas, ajustes de stock

### Notas de implementación

```php
// Activar auditoría en un modelo
use Spatie\Activitylog\Traits\LogsActivity;

class Sale extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['total', 'status', 'user_id'];
    protected static $logName = 'sale';
}
```

```php
// Rate limiting en RouteServiceProvider
RateLimiter::for('pos', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

---

## 2. Performance y escalabilidad

> Crítico para el POS — debe responder en menos de 200ms al escanear un barcode

### Base de datos

- [x] **Índices en columnas de búsqueda frecuente**
  - Agregar en migraciones: `barcode`, `sku`, `sale_date`, `product_id`, `warehouse_id`
  - Sin índices el POS se vuelve lento con 10.000+ productos

- [ ] **Eager loading para evitar N+1**
  - `Product::with(['variants', 'category', 'locations'])->get()`
  - Usar `->withCount('stockMovements')` en lugar de contar en PHP

- [x] **Paginación en todos los listados de Filament**
  - `->paginate(50)` — nunca `->get()` en tablas grandes
  - Configurar en cada Resource: `protected static int $defaultTableRecordsPerPage = 25`

- [x] **Query caching para reportes pesados**
  - `Cache::remember('daily_sales_'.today(), 300, fn() => ...)`
  - TTL de 5–15 minutos según frecuencia de cambio

- [x] **Soft deletes en lugar de borrado real**
  - `SoftDeletes` trait en todos los modelos principales
  - Permite recuperar datos eliminados por error

### Aplicación

- [ ] **Cola de jobs para PDFs y emails**
  - `Laravel Queues` con driver `redis` o `database`
  - Generar PDF del ticket en background, no bloqueando el POS

- [ ] **Optimizar imágenes de productos**
  - Paquete: `spatie/laravel-medialibrary`
  - Generar conversiones: `thumb (150x150)`, `card (400x400)`

- [ ] **Horizon para monitorear colas en producción**
  - `laravel/horizon` → dashboard visual de jobs y workers
  - Configurar alertas si la cola supera N jobs pendientes

- [ ] **Telescopio en desarrollo para debug**
  - `laravel/telescope` → ver queries, jobs, requests, exceptions
  - Solo en entorno `local`, nunca en producción

- [x] **Config y rutas cacheadas en producción**
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`

### Notas de implementación

```php
// Índices en migración
Schema::table('products', function (Blueprint $table) {
    $table->index('barcode');
    $table->index('sku');
    $table->index(['category_id', 'active']);
});
```

```php
// Job para generar PDF en background
class GenerateReceiptPdf implements ShouldQueue
{
    public function handle(): void
    {
        $pdf = PDF::loadView('receipts.ticket', ['sale' => $this->sale]);
        Storage::put("receipts/{$this->sale->id}.pdf", $pdf->output());
    }
}
```

---

## 3. Experiencia de usuario (UX)

> El POS lo usan cajeros todo el día — cada segundo que se ahorra importa

### POS y flujo de venta

- [ ] **Atajos de teclado en el POS**
  - `F2` = nueva venta
  - `F4` = ir a cobrar
  - `ESC` = cancelar / limpiar carrito
  - `F6` = búsqueda manual de producto

- [ ] **Feedback visual inmediato al escanear barcode**
  - Flash verde si el producto se encontró
  - Flash rojo + sonido si el código no existe
  - Implementar con Livewire + `$dispatch('barcode-result')`

- [ ] **Búsqueda por nombre, SKU y barcode simultánea**
  - `Laravel Scout` + `Meilisearch` para búsqueda en tiempo real
  - Fallback: `LIKE '%query%'` con índice de texto completo

- [ ] **Carrito persistente si se corta la sesión**
  - Guardar estado del carrito en `localStorage`
  - Sincronizar con backend cada 30 segundos

- [x] **Confirmación antes de anular una venta**
  - Modal con motivo obligatorio (select + texto libre)
  - Registrar en `stock_movements` con type `return`

### Panel Filament

- [x] **Indicadores de stock en listado de productos**
  - Badge verde: stock > mínimo
  - Badge amarillo: stock ≤ mínimo
  - Badge rojo: sin stock

- [x] **Filtros rápidos preconfigurados**
  - Sin stock
  - Bajo stock mínimo
  - Sin ubicación asignada
  - Sin imagen

- [ ] **Dashboard personalizable por rol**
  - Admin: ventas, margen, flujo de caja
  - Cajero: ventas del día, caja actual
  - Depósito: stock bajo, ubicaciones

- [ ] **Notificaciones en tiempo real**
  - `FilamentNotification` para: stock crítico, caja sin cerrar
  - Integrar con Laravel Broadcasting si hay varios usuarios

- [x] **Modo oscuro activado**
  - Ya incluido en Filament v3
  - Activar en `AdminPanelProvider`: `->darkMode(true)`

### Notas de implementación

```php
// Indicador de stock en Filament Table
Tables\Columns\BadgeColumn::make('stock_status')
    ->label('Stock')
    ->getStateUsing(fn ($record) => match(true) {
        $record->stock <= 0 => 'Sin stock',
        $record->stock <= $record->stock_alert => 'Bajo',
        default => 'OK',
    })
    ->colors([
        'danger' => 'Sin stock',
        'warning' => 'Bajo',
        'success' => 'OK',
    ]),
```

---

## 4. Integraciones externas

> Claves para Paraguay — Bancard y SIFEN son prioritarias

### Pagos

- [ ] **Pagos con QR — Bancard Paraguay**
  - API: Bancard vPOS
  - Generar QR en el POS → cliente escanea → confirmar pago
  - Documentación: https://developers.bancard.com.py

- [ ] **Pagos con tarjeta vía terminal físico**
  - Integración con terminal Bancard o PagosNet
  - Registrar número de autorización en `payments`

- [ ] **Pagos parciales y saldo pendiente**
  - Tabla `payments` con campo `status`: `partial | paid | pending`
  - Un sale puede tener múltiples payments (efectivo + tarjeta)

- [ ] **Facturación electrónica SIFEN — SET Paraguay**
  - API del Servicio Nacional de Facturación Electrónica
  - Comprobantes electrónicos timbrados (obligatorio según facturación)
  - Requiere certificado digital y habilitación previa en SET

### Sistema y externos

- [ ] **API REST propia para app móvil futura**
  - `Laravel Sanctum` + `API Resource Collections`
  - Versionar: `/api/v1/products`, `/api/v1/sales`

- [ ] **Webhooks para alertas de stock bajo**
  - POST a Slack o WhatsApp Business API
  - Disparar cuando `stock <= stock_alert`

- [ ] **Integración con balanzas de peso**
  - Lectura por puerto COM/USB para productos a granel
  - Útil en ferretería para: tornillos, arena, clavos por kg

- [ ] **Exportar reportes a Excel**
  - Paquete: `maatwebsite/excel`
  - Exportar: ventas del mes, inventario actual, movimientos de caja

- [ ] **Envío de ticket por WhatsApp o email**
  - WhatsApp: Twilio o 360Dialog (WhatsApp Business API)
  - Email: `Laravel Mail` con plantilla del ticket en HTML

### Notas de implementación

```php
// Exportar ventas a Excel
class SalesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Sale::with('items.product')
            ->whereBetween('sale_date', [$this->from, $this->to])
            ->get()
            ->map(fn($s) => [
                'fecha'    => $s->sale_date->format('d/m/Y'),
                'cliente'  => $s->customer->name ?? 'Consumidor final',
                'total'    => $s->total,
                'metodo'   => $s->payments->pluck('method')->join(', '),
            ]);
    }
}
```

---

## 5. Testing y calidad de código

> Priorizar tests de los Services — son el corazón del negocio

### Tests críticos del negocio

- [ ] **Test unitario: SaleService.createSale()**
  - Verificar cálculo correcto de total y descuento
  - Verificar que el stock se descuenta correctamente

- [ ] **Test unitario: InventoryService.removeStock()**
  - No debe permitir stock negativo
  - Debe crear un `stock_movement` en cada operación

- [ ] **Test unitario: LocationService.numberToLetters()**
  - `1 → A`, `26 → Z`, `27 → AA`, `28 → AB`, `53 → BA`
  - Casos borde: 0, números negativos

- [ ] **Test de feature: flujo completo de venta**
  - Desde buscar producto → agregar al carrito → cobrar → ticket PDF
  - Verificar que el stock quedó actualizado

- [ ] **Test de feature: compra actualiza stock**
  - Recibir compra → verificar `stock_movements` creados
  - Verificar que el stock en `stocks` aumentó correctamente

### Calidad de código

- [ ] **Laravel Pint para formateo automático**
  - `./vendor/bin/pint`
  - Estilo PSR-12 — agregar al pipeline de CI

- [ ] **Larastan/PHPStan para análisis estático**
  - `./vendor/bin/phpstan analyse --level=6`
  - Detecta errores de tipos antes de ejecutar

- [ ] **Pre-commit hooks**
  - `CaptainHook` o scripts en `.git/hooks/pre-commit`
  - Ejecutar: Pint + PHPStan + tests antes de cada commit

- [ ] **CI/CD con GitHub Actions**
  - Correr tests en cada PR antes de mergear
  - Deploy automático a producción si los tests pasan

- [ ] **Cobertura mínima del 70% en Services**
  - `php artisan test --coverage`
  - Foco en: `SaleService`, `InventoryService`, `LocationService`

### Notas de implementación

```php
// Test del generador de ubicaciones
it('convierte números a letras correctamente', function () {
    expect(LocationService::numberToLetters(1))->toBe('A');
    expect(LocationService::numberToLetters(26))->toBe('Z');
    expect(LocationService::numberToLetters(27))->toBe('AA');
    expect(LocationService::numberToLetters(53))->toBe('BA');
});

// Test de stock negativo
it('no permite stock negativo', function () {
    $product = Product::factory()->create(['stock' => 5]);

    expect(fn() => app(InventoryService::class)->removeStock($product, 10))
        ->toThrow(InsufficientStockException::class);
});
```

```yaml
# GitHub Actions — .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with: { php-version: '8.3' }
      - run: composer install --no-interaction
      - run: cp .env.example .env && php artisan key:generate
      - run: php artisan test --coverage
```

---

## 6. Documentación del proyecto

> La buena documentación es lo que convierte un proyecto personal en un producto vendible

### Documentación técnica

- [x] **README completo con setup del proyecto**
  - Requisitos del sistema (PHP 8.3, Node, Redis)
  - Pasos de instalación paso a paso
  - Variables de entorno necesarias con descripción

- [x] **Documentar todos los Services con PHPDoc**
  - `@param`, `@return`, `@throws` en cada método público
  - Descripción del propósito de cada Service

- [ ] **Diagrama entidad-relación actualizado**
  - Herramienta: `dbdiagram.io` o `DrawSQL`
  - Exportar como imagen y versionar en `/docs/erd.png`

- [ ] **Changelog por versión (CHANGELOG.md)**
  - Formato: `## [1.0.0] - 2025-xx-xx`
  - Secciones: Added · Changed · Fixed · Removed

- [ ] **Colección Postman o Bruno de la API**
  - Exportar y versionar junto al código en `/docs/api/`
  - Incluir ejemplos de request y response

### Documentación operativa

- [ ] **Manual de usuario para cajeros**
  - PDF o Notion
  - Cómo usar el POS, buscar productos, cobrar, anular

- [ ] **Manual de administrador**
  - Gestión de productos y categorías
  - Sistema de ubicaciones y cómo asignarlas
  - Cómo interpretar los reportes

- [ ] **Runbook de operaciones**
  - Qué hacer si el sistema no carga
  - Cómo restaurar un backup
  - Contactos de soporte técnico

- [ ] **Notas de arquitectura en Obsidian**
  - Decisiones técnicas tomadas y por qué
  - Alternativas consideradas y descartadas
  - Deuda técnica conocida

### Estructura de docs sugerida

```
docs/
 ├── erd.png                  ← Diagrama entidad-relación
 ├── architecture.md          ← Decisiones de arquitectura
 ├── api/
 │   └── collection.json      ← Postman / Bruno
 ├── manuals/
 │   ├── cajero.pdf
 │   └── administrador.pdf
 └── runbook.md               ← Operaciones y emergencias
```

---

## Paquetes recomendados por área

| Área | Paquete | Uso |
|---|---|---|
| Seguridad | `spatie/laravel-activitylog` | Auditoría de acciones |
| Seguridad | `laravel/sanctum` | Tokens de API con scopes |
| Performance | `laravel/horizon` | Monitor de colas en producción |
| Performance | `laravel/telescope` | Debug en desarrollo |
| Performance | `spatie/laravel-medialibrary` | Imágenes optimizadas |
| UX | `wire:navigate` (Livewire v3) | Navegación SPA sin recargas |
| Integraciones | `maatwebsite/excel` | Exportar reportes a Excel |
| Integraciones | `barryvdh/laravel-dompdf` | Tickets y facturas en PDF |
| Testing | `pestphp/pest` | Tests con sintaxis moderna |
| Testing | `nunomaduro/larastan` | Análisis estático PHP |
| Código | `laravel/pint` | Formateo automático PSR-12 |

---

## Orden de implementación recomendado

```
Fase 1  →  Seguridad base (roles, 2FA, activitylog)
Fase 2  →  Índices BD + eager loading + paginación
Fase 3  →  Tests de Services críticos
Fase 4  →  UX del POS (atajos, feedback, búsqueda)
Fase 5  →  Integraciones (Bancard QR, Excel, WhatsApp)
Fase 6  →  Documentación técnica y operativa
Fase 7  →  CI/CD + Horizon en producción
```

---

## Notas para ropería (futura)

Cuando escales el sistema para la ropería, estos puntos cambian:

- **UX:** El POS necesita selector de talla y color antes de agregar al carrito
- **Integraciones:** Importación masiva de productos desde Excel (tallas × colores)
- **Performance:** Las variantes multiplican los registros — revisar índices en `product_variants`
- **Testing:** Agregar tests para flujo de venta con variantes

---

*Documento generado con Claude · Sistema POS Ferretería — Laravel + Filament*