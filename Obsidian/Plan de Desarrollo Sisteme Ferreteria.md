# Sistema POS + Inventario — Ferretería (Laravel + Filament)

> **Stack:** PHP · Laravel 12 · Filament v3 · Livewire v3 · SQLite (dev)  
> **Método de desarrollo:** IA + MCP (opencode)  
> **Negocio base:** Ferretería → escalable a ropería y SaaS

---

## Estado del Sistema ✅ OPERATIVO

- Base de datos con datos de prueba
- Usuario admin: `admin@ferreteria.com` / `password`
- 10 productos de ejemplo
- 24 categorías
- 3 proveedores, 4 clientes
- 1 sucursal, 1 almacén

---

## Arquitectura del proyecto

```
app/
 ├── Actions/
 ├── Domains/
 │   ├── Inventory/
 │   ├── Sales/
 │   ├── Purchases/
 │   ├── POS/
 │   └── Finance/
 ├── Models/
 ├── Services/
 │   ├── SaleService.php
 │   ├── InventoryService.php
 │   ├── PurchaseService.php
 │   └── LocationService.php
 ├── Repositories/
 └── Filament/
     ├── Resources/
     ├── Widgets/
     ├── Pages/
     └── Clusters/
```

---

## Fases de desarrollo

### Fase 1 — Base del sistema (Semana 1) ✅ COMPLETADO

- [x] Usuarios y roles (Spatie Permissions)
- [x] Productos y variantes
- [x] Categorías (árbol padre/hijo)
- [x] Proveedores
- [x] Clientes
- [x] Empresa y sucursales

### Fase 2 — Inventario + Ubicaciones (Semana 1–2) ✅ COMPLETADO

- [x] Almacenes / depósitos
- [x] Sistema de ubicaciones A → Z → AA → AB
- [x] Estanterías, filas y niveles
- [x] Stock movements (nunca modificar stock directo)
- [x] Ajustes de inventario
- [ ] Etiquetas QR por ubicación

### Fase 3 — Compras y Ventas (Semana 2–3) ✅ COMPLETADO

- [x] Compras a proveedores
- [x] POS con Livewire (interfaz punto de venta)
- [x] Lector de código de barras (input autofocus)
- [x] Métodos de pago: efectivo, tarjeta, transferencia, QR
- [x] Caja diaria (apertura / cierre)

### Fase 4 — Comprobantes y Reportes (Semana 3–4) ✅ COMPLETADO

- [x] Ticket / Factura / Recibo en PDF (DomPDF)
- [x] Dashboard Filament con widgets
- [x] Reporte ventas por día / producto / vendedor
- [x] Reporte stock bajo mínimo
- [x] Reporte productos sin ubicación asignada

### Fase 5 — Escalado futuro (pendiente)

- [ ] Multi sucursal
- [ ] Ropería: activar variantes talla/color
- [ ] Facturación electrónica Paraguay
- [ ] SaaS multi-tenant
- [ ] App móvil

> ⚠️ **Pausado** - Multi-sucursal y ropería por el momento

---

## Mejoras implementadas ✅

### Seguridad
- [x] Activity log (spatie/laravel-activitylog)
- [x] Rate limiting en endpoints (POS, login, API)
- [x] LogsActivity trait en 6 modelos (Sale, Purchase, InventoryAdjustment, Product, Customer, Supplier)
- [x] Sesión expira a los 120 min de inactividad
- [x] RUC de proveedores encriptado en BD (cast `encrypted`)

### Performance
- [x] Índices en BD (barcode, sku, sale_date, etc.)
- [x] Paginación por defecto (25 registros)
- [x] Paginación configurable en recursos
- [x] Soft deletes en 5 modelos (Product, Sale, Purchase, Customer, Supplier)
- [x] Cache en widget SalesTodayWidget (TTL 5 min)

### UX
- [x] Modo oscuro habilitado
- [x] Badge de stock en listado de productos (verde/amarillo/rojo) usando min_stock del producto
- [x] Filtros rápidos en ProductResource: sin stock, bajo mínimo, sin ubicación, eliminados
- [x] Anulación de ventas con motivo obligatorio y confirmación modal
- [x] Filtro de eliminados (TrashedFilter) + restaurar en Product, Sale, Purchase, Customer, Supplier
- [x] Escáner de código de barras en SaleResource (busca por barcode/SKU)

### Documentación
- [x] README.md actualizado
- [x] PHPDoc completo en los 5 Services (InventoryService, SaleService, PurchaseService, ReceiptService, LocationService)

---

## Base de datos completa (~45 tablas)

### Usuarios y permisos

```
users
roles
permissions
model_has_roles
model_has_permissions
role_has_permissions
```

### Empresa

```
companies
 - id, name, ruc, address, phone, email

branches
 - id, company_id, name, address
```

### Productos

```
products
 - id, name, description, brand, category_id
 - barcode, sku, cost_price, sale_price, tax, active

product_variants
 - id, product_id, sku, barcode, color, size, price

categories
 - id, name, parent_id
```

### Ubicaciones físicas

```
warehouses
 - id, branch_id, name

warehouse_aisles
 - id, warehouse_id, code (A, B, ... Z, AA, AB...)

shelves
 - id, aisle_id, number

shelf_rows
 - id, shelf_id, number

shelf_levels
 - id, row_id, number

product_locations
 - id, product_variant_id, shelf_level_id, quantity
```

**Código de ubicación:** `PASILLO-ESTANTE-FILA-NIVEL`  
Ejemplo: `C-05-03-02` = Pasillo C · Estante 05 · Fila 03 · Nivel 02

### Inventario

```
stocks
 - id, product_variant_id, warehouse_id, quantity

stock_movements
 - id, product_variant_id, warehouse_id
 - type (purchase | sale | adjustment | return | transfer)
 - quantity, reference_type, reference_id, created_at

inventory_adjustments
 - id, user_id, warehouse_id, reason, created_at

inventory_adjustment_items
 - id, adjustment_id, product_variant_id, quantity
```

### Compras

```
suppliers
 - id, name, ruc, phone, email, address

purchases
 - id, supplier_id, branch_id, total, status, purchase_date

purchase_items
 - id, purchase_id, product_variant_id, quantity, cost
```

### Ventas

```
customers
 - id, name, document, phone, email

sales
 - id, customer_id, user_id, branch_id
 - total, discount, tax, sale_date

sale_items
 - id, sale_id, product_variant_id, quantity, price, subtotal

payments
 - id, sale_id, method, amount, payment_date
 - method: cash | card | transfer | qr
```

### Caja

```
cash_registers
 - id, branch_id, opened_by, closed_by
 - opening_amount, closing_amount, opened_at, closed_at

cash_movements
 - id, cash_register_id, type, amount, description
```

### Comprobantes

```
receipts
 - id, sale_id, type, number, generated_at
 - type: ticket | invoice | receipt
```

---

## Sistema de ubicaciones A → Z → AA → AB

### Función PHP (generador de letras)

```php
function numberToLetters(int $num): string
{
    $result = '';
    while ($num > 0) {
        $num--;
        $result = chr($num % 26 + 65) . $result;
        $num = intdiv($num, 26);
    }
    return $result;
}

// 1  → A
// 26 → Z
// 27 → AA
// 28 → AB
// 52 → AZ
// 53 → BA
```

### Estructura de ubicación

```
Almacén
 └── Pasillo (A, B, ... AA, AB...)
      └── Estante (01, 02, 03...)
           └── Fila (01, 02, 03...)
                └── Nivel (01, 02, 03...)
```

### Integración en POS

Cuando se busca un producto, mostrar:

- Nombre + SKU + Barcode
- Stock disponible
- **Ubicación física:** `A-03-02-01`
- Si está en múltiples ubicaciones, listar todas

---

## Servicios de negocio

### SaleService

```php
// Métodos principales
createSale()
calculateTotal()
applyDiscount()
confirmSale()       // usa DB::transaction()
generateReceipt()
```

### InventoryService

```php
addStock()
removeStock()
checkMinimum()
adjustStock()
// Nunca modificar stock directamente en la tabla products
// Siempre crear un stock_movement
```

### PurchaseService

```php
createPurchase()
receiveProducts()   // actualiza stock + crea movement
```

### LocationService

```php
numberToLetters()
assignLocation()
getProductLocations()
generateQRCode()
```

---

## Reglas importantes del sistema

1. **Nunca modificar stock directo** — siempre crear un `stock_movement`
2. **Usar transacciones** — `DB::transaction()` en ventas y compras
3. **Separar lógica en Services** — no en controllers ni resources
4. **Logs de auditoría** — registrar quién hizo cada venta/ajuste

---

## Recursos Filament a crear

```
ProductResource
ProductVariantResource
CategoryResource
SupplierResource
CustomerResource
PurchaseResource
SaleResource
WarehouseResource
LocationResource
CashRegisterResource
ReceiptResource
```

### Clusters sugeridos

```
Cluster: Inventory    → Productos, Categorías, Stock, Ubicaciones
Cluster: Sales        → Ventas, Clientes, Pagos
Cluster: Purchases    → Compras, Proveedores
Cluster: Finance      → Caja, Comprobantes, Reportes
```

---

## Dashboard widgets

- Ventas del día (monto + cantidad)
- Ingresos hoy vs ayer
- Productos bajo stock mínimo
- Top 10 productos más vendidos (mes)
- Productos sin ubicación asignada
- Caja actual (abierta/cerrada)

---

## Prompts para generar con IA

### Prompt 1 — Migraciones y modelos

```
Create Laravel models and migrations for a POS system:
products, product_variants, categories, suppliers,
customers, purchases, purchase_items, sales,
sale_items, stock_movements, payments, cash_registers.
Include all relationships and fillable fields.
```

### Prompt 2 — Recursos Filament

```
Create Filament v3 Resources for: Product, Category,
Supplier, Customer, Purchase, Sale.
Include table columns, form fields, filters and search.
Use Spatie Permissions for access control.
```

### Prompt 3 — Sistema de ubicaciones

```
Create a warehouse location system in Laravel.
Tables: warehouses, warehouse_aisles, shelves,
shelf_rows, shelf_levels, product_locations.
Add helper function to generate aisle codes A→Z→AA→AB.
Create Filament resource to manage locations.
```

### Prompt 4 — POS con barcode

```
Create a Livewire POS interface for Laravel + Filament.
Features: barcode scanner autofocus input, product search,
add to cart, quantity adjustment, discount,
payment method (cash/card/transfer/QR),
show product location, confirm sale, print receipt PDF.
```

### Prompt 5 — Servicios

```
Create Laravel Services:
- SaleService: createSale, calculateTotal, applyDiscount
- InventoryService: addStock, removeStock, checkMinimum
- PurchaseService: createPurchase, receiveProducts
- LocationService: assignLocation, numberToLetters
Use DB::transaction() on all write operations.
```

### Prompt 6 — Reportes

```
Create Filament dashboard widgets:
- Sales today (number + chart)
- Low stock products (below minimum)
- Revenue today vs yesterday
- Top 10 products by sales this month
- Products without warehouse location assigned
```

---

## Estado Actual del Proyecto

### Instalado y configurado
- Laravel 12.x
- Filament v3.3.49
- Livewire v3.7.11
- Spatie Permissions v7.2.2
- DomPDF v3.1.1

### Migraciones ejecutadas (25+ tablas)
- companies, branches
- categories (árbol jerárquico)
- products, product_variants
- suppliers, customers
- warehouses, warehouse_aisles, shelves, shelf_rows, shelf_levels
- stocks, stock_movements, inventory_adjustments
- purchases, purchase_items
- sales, sale_items, payments
- cash_registers, cash_movements, receipts

### Recursos Filament creados
- UserResource (con roles)
- CategoryResource
- ProductResource
- SupplierResource
- CustomerResource
- WarehouseResource
- LocationResource (pasillos, estantes)
- StockResource
- InventoryAdjustmentResource
- PurchaseResource
- SaleResource
- CashRegisterResource
- Page: POS (Livewire)

### Servicios creados
- LocationService (generador de códigos A→Z→AA, asignar ubicaciones)
- InventoryService (addStock, removeStock, adjustStock, transferStock, checkMinimum)
- SaleService (createSale, calculateTotal, cancelSale)
- PurchaseService (createPurchase, receiveProducts)
- ReceiptService (generateReceipt, generatePdf, downloadPdf)

### Dashboard Widgets
- SalesTodayWidget (ventas del día vs ayer)
- LowStockWidget (productos bajo stock mínimo)
- TopProductsWidget (top 10 productos del mes)
- ProductsWithoutLocationWidget (productos sin ubicación)

### PDF Templates
- Ticket (80mm)
- Factura (A4)
- Recibo (A4)

---

## Paquetes recomendados

|Paquete|Uso|
|---|---|
|`filament/filament` v3|Panel admin|
|`livewire/livewire` v3|POS y componentes reactivos|
|`spatie/laravel-permission`|Roles y permisos|
|`barryvdh/laravel-dompdf`|Generación de PDF|
|`simplesoftwareio/simple-qrcode`|QR para ubicaciones|
|`doctrine/dbal`|Migraciones avanzadas|

---

## Ropería — qué considerar desde ahora

La tabla `product_variants` ya soporta tallas y colores.  
Cuando actives la ropería, simplemente:

1. Habilitar campos `size` y `color` en el formulario de producto
2. Agregar atributos dinámicos si hay muchas combinaciones
3. El inventario, ubicaciones, ventas y compras funcionan igual

**Ejemplo:**

```
Remera Nike (product)
 ├── rojo S  (variant)
 ├── rojo M  (variant)
 ├── rojo L  (variant)
 └── azul M  (variant)
```

---

## Estimación de tiempo (con IA + MCP)

|Fase|Semana|Entregable|
|---|---|---|
|Base del sistema|1|CRUD completo en Filament|
|Inventario + Ubicaciones|1–2|Stock movements + ubicaciones A→Z|
|Compras + POS|2–3|POS funcional con barcode|
|Comprobantes + Reportes|3–4|PDF + dashboard|
|Escalado|5+|Ropería, multi sucursal, SaaS|

**Total estimado: 4–5 semanas trabajando con IA**

---

## Herramientas de desarrollo

- **IDE:** Cursor o Windsurf (con agente IA integrado)
- **IA:** Claude Code / Claude Sonnet
- **BD:** PostgreSQL (recomendado) o MySQL
- **Control de versiones:** Git + GitHub
- **Deploy:** Laravel Forge / Coolify / Railway

---

## Auditoria y Correcciones (Sesion 2)

### PSR-4 Corregido
- [x] Divididos 9 archivos `*Pages.php` en archivos individuales (27 archivos nuevos)
  - SupplierResource, CustomerResource, WarehouseResource
  - PurchaseResource, SaleResource, CashRegisterResource
  - ReceiptResource, LocationResource, InventoryAdjustmentResource
- [x] Verificado que los 41 routes de admin cargan sin errores

### Widgets Corregidos (Filament v3 API)
- [x] LowStockWidget: migrado de `getTableQuery()`/`getColumns()` a `table(Table $table)`
- [x] TopProductsWidget: migrado a Filament v3 `TableWidget` API
- [x] ProductsWithoutLocationWidget: recreado correctamente
- [x] AdminPanelProvider: limpiado widget duplicados (auto-discovery + explicit)

### API Deprecadas Corregidas
- [x] `BooleanColumn` -> `IconColumn::make()->boolean()` (5 recursos)
- [x] `MultiSelect` -> `Select::make()->multiple()` (UserResource)
- [x] `TextColumn->nullable()` -> `->placeholder('-')` (ProductResource, CategoryResource)

### POS Mejorado
- [x] Agregado metodo `processSale()` usando `SaleService`
- [x] Agregado selector de caja registradora
- [x] Agregado selector de cliente
- [x] Botones de monto rapido en modal de pago (1.000 a 100.000)
- [x] Soporte dark mode completo
- [x] Validaciones de monto y carrito vacio
- [x] Notificaciones Filament al completar/cancelar venta

### Otros
- [x] `npm install && npm run build` ejecutado (Vite assets compilados)
- [x] PHPDoc `@property` agregados a modelos (Sale, Stock, ProductVariant, Warehouse, InventoryAdjustment)
- [x] Caches limpiados (`php artisan optimize:clear`)

### Pendiente
- [ ] Etiquetas QR por ubicacion (Fase 2)
- [ ] Testing automatizado (PHPUnit/Pest)
- [ ] Facturacion electronica Paraguay (Fase 5)

---

## Mejoras del Sistema — Sesion 4

### Seguridad
- [x] `LogsActivity` trait de Spatie agregado a 6 modelos: Sale, Purchase, InventoryAdjustment, Product, Customer, Supplier
  - Cada modelo tiene `getActivitylogOptions()` configurado con campos específicos
  - Log names en español: `venta`, `compra`, `ajuste_inventario`, `producto`, `cliente`, `proveedor`
- [x] Sesión configurada a 120 minutos (`SESSION_LIFETIME=120`)
- [x] RUC de proveedores encriptado en BD con cast `encrypted` de Laravel
  - Migración de datos existentes ejecutada

### Performance
- [x] Soft deletes en 5 modelos principales: Product, Sale, Purchase, Customer, Supplier
  - Migración: `add_soft_deletes_and_cancellation_reason` (agrega `deleted_at` + `cancellation_reason`)
- [x] Cache en SalesTodayWidget con TTL de 5 min (`Cache::remember`)

### UX
- [x] Badge de stock mejorado en ProductResource:
  - Ahora compara contra `min_stock` del producto (no valor fijo de 10)
  - Muestra "Sin stock" (rojo), "X (Bajo)" (amarillo), o cantidad (verde)
- [x] Filtros rápidos en ProductResource:
  - Sin stock, Bajo stock mínimo, Sin ubicación, Solo activos (default), Eliminados
- [x] Anulación de ventas con confirmación:
  - Botón "Anular" visible solo en ventas completadas
  - Modal con motivo obligatorio (select de opciones + texto libre)
  - Ejecuta `SaleService::cancelSale()` y registra `cancellation_reason`
  - Devuelve stock automáticamente
- [x] TrashedFilter + RestoreAction en 5 recursos: Product, Sale, Purchase, Customer, Supplier
  - `getEloquentQuery()` con `withoutGlobalScopes` para mostrar eliminados
  - Acciones de restaurar individual y masivo

### Documentación
- [x] PHPDoc completo en los 5 Services:
  - InventoryService (9 métodos), SaleService (6 métodos), PurchaseService (4 métodos)
  - ReceiptService (4 métodos), LocationService (9 métodos)
  - Descripciones en español, `@param`, `@return`, `@throws`

---

## Comprobantes y Plantillas — Sesión 5a

### Facturas A4
- [x] Diseño de factura A4 para ventas (`invoice.blade.php`) — esquema amber
- [x] Diseño de factura A4 para compras (`purchase_invoice.blade.php`) — esquema verde, sello "DOCUMENTO INTERNO"
- [x] Soporte de logo dinámico desde BD (`companies.logo`)
- [x] Migración: `add_logo_to_companies_table`

### Impresión inteligente
- [x] SaleResource: imprime factura A4 o ticket 80mm según `document_type`
- [x] PurchaseResource: dos acciones separadas — "Imprimir Factura" e "Imprimir Ticket"
- [x] ReceiptService: crea directorio `storage/app/receipts/` automáticamente

### Plantillas de comprobantes
- [x] ReceiptTemplateResource refactorizado: páginas List/Create/Edit separadas
- [x] 5 tipos de plantillas: `sale_ticket`, `sale_invoice`, `purchase_ticket`, `purchase_invoice`, `cash_register_report`
- [x] Categorización Ventas/Compras/Reportes con badges de colores
- [x] Editor de código monoespaciado a pantalla completa
- [x] Variables disponibles por tipo de plantilla
- [x] Acción "Restaurar a Default"

---

## Auditoría, SoftDeletes y Manejo de Errores — Sesión 5b

### Manejo global de errores
- [x] Handler global en `AppServiceProvider::configureFilamentExceptionHandling()`
  - Intercepta `QueryException` en rutas Livewire/Filament
  - FK Constraint → "No se puede eliminar este registro"
  - UNIQUE → "Registro duplicado"
  - NOT NULL → "Campo obligatorio vacío"
  - Database locked → "Base de datos ocupada"
  - Fallback genérico con detalle técnico resumido
  - Traducciones automáticas de tablas y campos al español

### SoftDeletes expandido (10 modelos → 13 total)
- [x] Soft deletes agregados a: Warehouse, Branch, Category, CashRegister, ExpenseCategory
  - Migración: `add_soft_deletes_to_critical_models`
- [x] TrashedFilter + RestoreAction en 4 recursos adicionales: Warehouse, Category, CashRegister, ExpenseCategory

### LogsActivity expandido (7 modelos → 15 total)
- [x] LogsActivity agregado a 8 modelos adicionales:
  - Warehouse (`almacen`), Branch (`sucursal`), Category (`categoria`)
  - CashRegister (`caja`), ExpenseCategory (`categoria_gasto`)
  - Expense (`gasto`), InventoryCount (`conteo_inventario`), Stock (`stock`)
  - Todos con `logOnlyDirty()` y `dontSubmitEmptyLogs()`

### ActivityResource mejorado
- [x] 15 módulos con emojis y colores en filtro multi-select
- [x] Filtro por rango de fechas (desde/hasta)
- [x] Filtro por usuario (query directa a Users)
- [x] Pestañas por categoría: Todos, Ventas, Compras, Inventario, Finanzas, Configuración
- [x] Subject type traducido al español (Sale→Venta, etc.)
- [x] Auto-refresh cada 30 segundos
- [x] Vista detallada mejorada con secciones old/new separadas

### InventoryCountResource
- [x] Movido a grupo "Inventario" en navegación
- [x] Labels traducidos: "Conteo de Inventario" / "Conteos de Inventario"
- [x] Icono: `heroicon-o-clipboard-document-check`
- [x] TrashedFilter + RestoreAction agregados
- [x] Columna de conteo de ítems con badge

### Documentación
- [x] `Obsidian/Errores_Sistema_Referencia.md` — guía completa de errores del sistema
- [x] `Obsidian/Plan_SoftDeletes_Auditoria_Mejoras.md` — plan consolidado

### Pendiente
- [ ] Etiquetas QR por ubicación (Fase 2)
- [ ] Testing automatizado (PHPUnit/Pest)
- [ ] Facturación electrónica Paraguay (Fase 5)
- [ ] Eager loading optimizado en Resources (N+1 en relaciones de tabla)
- [ ] Atajos de teclado en SaleResource (F2 nueva venta, F4 cobrar)
- [ ] Notificaciones en tiempo real para stock crítico
- [ ] Exportar reportes a Excel (`maatwebsite/excel`)
- [ ] Pagos múltiples combinados en una misma venta

---

_Plan generado con Claude · Proyecto: Sistema POS Ferreteria_