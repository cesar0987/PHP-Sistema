

> **Stack:** Laravel · Filament v3 · Livewire v3 · SQLite (dev)
> **Nivel:** Semi-senior · Desarrollo con IA + MCP
> **Áreas:** Seguridad · Performance · UX · Integraciones · Testing · Documentación

---

## Progreso general

| Área                        | Items | Estado                    | % |
| --------------------------- | ----- | ------------------------- | - |
| Seguridad y auditoría       | 10    | ✅⬜⬜✅✅⬜✅✅✅✅ | 7/10 |
| Performance y escalabilidad | 10    | ✅⬜✅✅✅⬜⬜⬜⬜✅ | 5/10 |
| Experiencia de usuario (UX) | 10    | ⬜⬜⬜⬜✅✅✅⬜⬜✅ | 4/10 |
| Integraciones externas      | 9     | ⬜⬜⬜🔄⬜⬜⬜⬜⬜  | 1/9 |
| Testing y calidad           | 10    | ✅✅✅✅✅✅✅⬜⬜⬜ | 7/10 |
| Documentación               | 9     | ✅✅✅✅⬜✅✅✅✅  | 8/9 |

> **Última actualización:** 26/03/2026
> **Avances recientes:** 58 tests pasando (144 assertions), F3 Kendall completada, SIFEN CDC+QR+XML implementados, AuthFlowTest con rate limiting, SaleService bug de doble descuento corregido, documentación completa actualizada

---

## 1. Seguridad y auditoría

> Prioridad alta — implementar desde el inicio del proyecto

### Autenticación y accesos

- [x] **Bloqueo por intentos fallidos de login** ✅
  - `throttle` via `danharrin/livewire-rate-limiting` en el componente Login de Filament
  - Límite: 5 intentos por minuto por IP
  - Cubierto por `AuthFlowTest::test_login_is_blocked_after_five_attempts`

- [ ] **Activar 2FA para usuarios admin**
  - Paquete: `Laravel Fortify` o `Jetstream`
  - Aplicar solo a roles admin y supervisor

- [ ] **Tokens de API con scopes por rol**
  - `Laravel Sanctum` con `->createToken('pos', ['sale:create'])`
  - Cada rol tiene abilities distintas

- [x] **Expiración automática de sesiones inactivas** ✅
  - `config/session.php` → `lifetime` configurado
  - Redirige al login con mensaje claro

- [x] **Logs de auditoría en cada acción sensible** ✅
  - `spatie/laravel-activitylog` implementado en todos los modelos
  - Registra: ventas, ajustes de stock, anulaciones, login/logout/failed
  - Listeners: `LoginListener`, `LogoutListener`, `FailedLoginListener`
  - Pestaña "Autenticación" en `ActivityResource`

- [ ] **Nunca exponer IDs secuenciales en URLs**
  - Usar `UUIDs` como primary key o `hashids`
  - `$table->uuid('id')->primary()` en migraciones

- [x] **Validar y sanitizar todos los inputs** ✅
  - Form Requests implementados: `StoreSaleRequest`, `StorePurchaseRequest`, `StoreInventoryAdjustmentRequest`, `UpdateProductRequest`, `StoreCashRegisterRequest`
  - Filament complementa con `->rules()` y `->helperText()`

- [x] **Encriptar datos sensibles en BD** ✅
  - `encrypt()` / `decrypt()` de Laravel
  - Aplicado en: datos bancarios, tokens de integración

- [x] **Backups automáticos diarios** ✅
  - `BackupSqliteCommand` artisan custom
  - Programado diariamente en `routes/console.php`

- [x] **Rate limiting en endpoints críticos** ✅
  - `RateLimiter::for('login', ...)` — 5/min
  - `RateLimiter::for('pos', ...)` — 60/min
  - `RateLimiter::for('api', ...)` — 60/min

### Notas de implementación

```php
// Rate limiting en AppServiceProvider
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

---

## 2. Performance y escalabilidad

> Crítico para el POS — debe responder en menos de 200ms al escanear un barcode

### Base de datos

- [x] **Índices en columnas de búsqueda frecuente** ✅
  - `barcode`, `sku`, `sale_date`, `product_id`, `warehouse_id` indexados en migraciones

- [ ] **Eager loading para evitar N+1**
  - Larastan nivel 5 detecta 74 errores baseline — parte son N+1
  - `Product::with(['variants', 'category', 'locations'])->get()`
  - Fix incremental pendiente

- [x] **Paginación en todos los listados de Filament** ✅
  - `$defaultTableRecordsPerPage = 25` en todos los Resources

- [x] **Query caching para reportes pesados** ✅
  - `Cache::remember('daily_sales_'.today(), 300, fn() => ...)`

- [x] **Soft deletes en lugar de borrado real** ✅
  - `SoftDeletes` en 13 modelos principales (Sale, Product, Customer, Supplier, etc.)

### Aplicación

- [ ] **Cola de jobs para PDFs y emails**
  - `Laravel Queues` con driver `database`
  - Generar PDF del ticket en background, no bloqueando el POS

- [ ] **Optimizar imágenes de productos**
  - Paquete: `spatie/laravel-medialibrary`
  - Generar conversiones: `thumb (150x150)`, `card (400x400)`

- [ ] **Horizon para monitorear colas en producción**
  - `laravel/horizon` → dashboard visual de jobs y workers

- [ ] **Telescopio en desarrollo para debug**
  - `laravel/telescope` → ver queries, jobs, requests, exceptions
  - Solo en entorno `local`

- [x] **Config y rutas cacheadas en producción** ✅
  - `php artisan config:cache && route:cache && view:cache`
  - Documentado en `Obsidian/Deploy_Produccion.md`

---

## 3. Experiencia de usuario (UX)

> El POS lo usan cajeros todo el día — cada segundo que se ahorra importa

### POS y flujo de venta

- [ ] **Atajos de teclado en el POS**
  - Plan: `Obsidian/Plan_Navegacion_Atajos.md`
  - `F2` = nueva venta · `F4` = cobrar · `ESC` = cancelar · `F6` = búsqueda
  - Implementar con Alpine.js `@keydown`

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

- [x] **Confirmación antes de anular una venta** ✅
  - Modal con motivo en Filament
  - `cancelSale()` registra en `stock_movements` con type `return`

### Panel Filament

- [x] **Indicadores de stock en listado de productos** ✅
  - Badge verde: stock > mínimo
  - Badge amarillo: stock ≤ mínimo
  - Badge rojo: sin stock

- [x] **Filtros rápidos preconfigurados** ✅
  - Sin stock · Bajo stock mínimo · Sin ubicación · Sin imagen

- [ ] **Dashboard personalizable por rol**
  - Admin: ventas, margen, flujo de caja
  - Cajero: ventas del día, caja actual
  - Depósito: stock bajo, ubicaciones

- [ ] **Notificaciones en tiempo real**
  - `FilamentNotification` para: stock crítico, caja sin cerrar
  - Integrar con Laravel Broadcasting si hay varios usuarios

- [x] **Modo oscuro activado** ✅
  - Habilitado en `AdminPanelProvider`: `->darkMode(true)`

---

## 4. Integraciones externas

> Claves para Paraguay — Bancard y SIFEN son prioritarias

### Pagos

- [ ] **Pagos con QR — Bancard Paraguay**
  - API: Bancard vPOS
  - Generar QR en el POS → cliente escanea → confirmar pago

- [ ] **Pagos con tarjeta vía terminal físico**
  - Integración con terminal Bancard o PagosNet
  - Registrar número de autorización en `payments`

- [ ] **Pagos parciales y saldo pendiente**
  - Tabla `payments` con campo `status`: `partial | paid | pending`
  - Un sale puede tener múltiples payments (efectivo + tarjeta)

- 🔄 **Facturación electrónica SIFEN — SET Paraguay**
  - Plan: `Obsidian/Plan_Sifen_XML.md`
  - ✅ `SifenCdcService` — CDC 44 dígitos con módulo 11 (15 tests pasando)
  - ✅ `SifenQrService` — URL QR con hash SHA256+CSC
  - ✅ `SifenXmlService` — XML `<rDE>` completo (sin firma aún)
  - ⬜ Firma digital RSA-SHA256 — requiere certificado `.p12` de la SET
  - ⬜ Envío a SET (API REST) — requiere habilitación previa

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

---

## 5. Testing y calidad de código

> **Estado actual: 58 tests / 144 assertions — todos pasando** ✅

### Tests unitarios ✅ (42 tests)

- [x] **InventoryServiceTest** — 10 tests ✅
  - `addStock`, `removeStock`, `adjustStock`, `transferStock`, `checkMinimum`, `getTotalStock`

- [x] **SaleServiceTest** — 4 tests ✅
  - `calculateTotal`, `createSale`, `cancelSale`, stock insuficiente

- [x] **PurchaseServiceTest** — 4 tests ✅
  - `createPurchase`, `receiveProducts`, `cancelPurchase`

- [x] **LocationServiceTest** — 6 tests ✅
  - `numberToLetters`, `lettersToNumber`, roundtrip, `createLocation`, `assignLocation`

- [x] **CreditServiceTest** — 2 tests ✅
  - `recordSalePayment`, `updateCustomerBalance`

- [x] **SifenCdcServiceTest** — 15 tests ✅
  - `buildBase` (longitud, segmentos, padding), `calculateCheckDigit` (exactitud, rango, determinismo), `generateSecurityCode`, CDC completo 44 dígitos

### Tests de feature ✅ (16 tests)

- [x] **SaleFlowTest** — 5 tests ✅
  - Venta completa descuenta stock · Pendiente no descuenta · Aprobar descuenta · Cancelar devuelve stock · Stock insuficiente lanza excepción

- [x] **PurchaseFlowTest** — 4 tests ✅
  - Pending no agrega stock · Recibir agrega stock · `receive_products=true` inmediato · Cancelar cambia status

- [x] **CashRegisterFlowTest** — 4 tests ✅
  - Estado open · Cierre registra datos · Ventas vinculadas · Totales correctos (IVA 10%)

- [x] **AuthFlowTest** — 2 tests ✅
  - Credenciales inválidas → error validación · 5 intentos → rate limit bloqueado

### Calidad de código

- [x] **Laravel Pint** ✅ — 29 archivos formateados, PSR-12 aplicado
- [x] **Larastan nivel 5** ✅ — instalado, 74 errores baseline registrados

- [ ] **Corregir errores Larastan** (incremental)
  - 74 errores baseline — fix archivo por archivo
  - Foco en: N+1 queries, tipos de retorno faltantes

- [ ] **Pre-commit hooks**
  - `CaptainHook` o scripts en `.git/hooks/pre-commit`
  - Ejecutar: Pint + PHPStan + tests antes de cada commit

- [ ] **CI/CD con GitHub Actions**
  - Correr tests en cada PR antes de mergear
  - Deploy automático a producción si los tests pasan

- [ ] **Cobertura mínima del 70% en Services**
  - `php artisan test --coverage`
  - Foco en: `SaleService`, `InventoryService`, `LocationService`

---

## 6. Documentación del proyecto

> **Estado actual: prácticamente completa** ✅

### Documentación técnica

- [x] **CLAUDE.md + README con setup del proyecto** ✅
  - Stack, estructura, comandos, reglas de arquitectura, SIFEN, testing

- [x] **Todos los Services con PHPDoc** ✅
  - `@param`, `@return`, `@throws` en todos los métodos públicos de los 9 servicios

- [x] **Diagrama entidad-relación** ✅
  - `Obsidian/Diagrama_ER.md` — 35 tablas con relaciones y cardinalidad (Mermaid)

- [x] **CHANGELOG.md** ✅
  - Versiones: v1.0 → v1.8, formato Added/Changed/Fixed/Removed

- [ ] **Colección Postman/Bruno de la API**
  - No hay API REST aún — pendiente para cuando se implemente Sanctum

### Documentación operativa

- [x] **Manual de cajero** ✅ — `Obsidian/Manual_Cajero.md`
- [x] **Manual de administrador** ✅ — `Obsidian/Manual_Admin.md`
- [x] **Runbook de operaciones** ✅ — `Obsidian/Runbook.md`
- [x] **Notas de arquitectura** ✅ — `Obsidian/Arquitectura_Sistema.md`, `Arquitectura_Servicios.md`

### Reglas del asistente IA (.cursor/rules/)

- [x] `global.md` — idioma, restricciones absolutas, calidad
- [x] `architecture.md` — service layer, BranchScope, transacciones
- [x] `planning.md` — flujo de planificación, 12 planes documentados
- [x] `git.md` — Conventional Commits, 18 scopes del proyecto
- [x] `laravel.md` — convenciones Laravel 12 + Filament v3
- [x] `sifen.md` — CDC, QR, XML, firma digital, campos requeridos
- [x] `testing.md` — PHPUnit, Livewire, rate limiter, enum constraints

---

## Próximas mejoras priorizadas

Ordenadas por impacto / esfuerzo:

| Prioridad | Mejora | Área | Esfuerzo |
|-----------|--------|------|----------|
| 🔴 Alta | Firma digital SIFEN (`.p12` + `xmlseclibs`) | Integraciones | Alto (requiere cert SET) |
| 🔴 Alta | Corregir errores Larastan (incremental) | Calidad | Medio |
| 🔴 Alta | Atajos de teclado POS (F2, F4, ESC, F6) | UX | Bajo |
| 🟡 Media | Exportar reportes a Excel | Integraciones | Bajo |
| 🟡 Media | Cola de jobs para PDFs | Performance | Medio |
| 🟡 Media | Eager loading N+1 | Performance | Bajo |
| 🟡 Media | Dashboard personalizable por rol | UX | Medio |
| 🟢 Baja | CI/CD GitHub Actions | Calidad | Bajo |
| 🟢 Baja | 2FA para admin | Seguridad | Medio |
| 🟢 Baja | Notificaciones en tiempo real | UX | Alto |

---

## Paquetes recomendados por área

| Área | Paquete | Uso | Estado |
|---|---|---|---|
| Seguridad | `spatie/laravel-activitylog` | Auditoría de acciones | ✅ Instalado |
| Seguridad | `laravel/sanctum` | Tokens de API con scopes | ⬜ Pendiente |
| Performance | `laravel/horizon` | Monitor de colas en producción | ⬜ Pendiente |
| Performance | `laravel/telescope` | Debug en desarrollo | ⬜ Pendiente |
| SIFEN | `robrichards/xmlseclibs` | Firma RSA-SHA256 | ⬜ Pendiente |
| UX | `wire:navigate` (Livewire v3) | Navegación SPA sin recargas | ✅ Disponible |
| Integraciones | `maatwebsite/excel` | Exportar reportes a Excel | ⬜ Pendiente |
| Integraciones | `barryvdh/laravel-dompdf` | Tickets y facturas en PDF | ✅ Instalado |
| Testing | `nunomaduro/larastan` | Análisis estático PHP | ✅ Instalado |
| Código | `laravel/pint` | Formateo automático PSR-12 | ✅ Instalado |

---

## Notas para ropería (futura)

Cuando escales el sistema para la ropería, estos puntos cambian:

- **UX:** El POS necesita selector de talla y color antes de agregar al carrito
- **Integraciones:** Importación masiva de productos desde Excel (tallas × colores)
- **Performance:** Las variantes multiplican los registros — revisar índices en `product_variants`
- **Testing:** Agregar tests para flujo de venta con variantes

---

*Documento actualizado con Claude · Sistema POS Ferretería — Laravel + Filament*
