# Arquitectura de Servicios — Terracota POS

Este documento describe la capa de servicios del sistema, sus responsabilidades, dependencias y patrones de uso.

## Diagrama General

```
Filament Resources / Controllers
            │
            ▼
      ┌─────────────┐
      │  Services   │ ← Toda la lógica de negocio vive aquí
      └─────────────┘
            │
     ┌──────┴──────┐
     ▼             ▼
  Models        DB::transaction()
  (Eloquent)
```

## Servicios y Responsabilidades

---

### SaleService
**Archivo:** `app/Services/SaleService.php`
**Depende de:** `InventoryService`

| Método | Descripción |
|--------|-------------|
| `createSale(array $data)` | Crea venta, items, descuenta stock y registra pagos |
| `calculateTotal(array $items, float $discount)` | Calcula subtotal, IVA y total sin persistir |
| `approveSale(Sale, array $payments)` | Aprueba nota de pedido pendiente |
| `cancelSale(Sale)` | Cancela venta y devuelve stock |
| `getSalesByDate($date, $branchId)` | Ventas completadas de una fecha |
| `getTopProducts($start, $end, $limit)` | Productos más vendidos |

**Notas:**
- Todo `createSale` y `cancelSale` usan `DB::transaction()`.
- El cálculo de IVA usa `price * (tax_percentage / 100)` sobre el precio antes del descuento de ítem.

---

### InventoryService
**Archivo:** `app/Services/InventoryService.php`
**Depende de:** Nada

| Método | Descripción |
|--------|-------------|
| `addStock(ProductVariant, Warehouse, int, array)` | Agrega stock y registra movimiento |
| `removeStock(ProductVariant, Warehouse, int, array)` | Remueve stock — lanza excepción si insuficiente |
| `adjustStock(ProductVariant, Warehouse, int, string, ?int)` | Ajuste directo a cantidad específica |
| `processAdjustment(InventoryAdjustment)` | Procesa ajuste ya existente al aprobarse |
| `transferStock(ProductVariant, Warehouse, Warehouse, int, ?int)` | Transfiere entre almacenes |
| `checkMinimum(ProductVariant, ?Warehouse)` | Verifica si el stock está bajo el mínimo |
| `getLowStockProducts()` | Lista variantes con stock bajo |
| `getStockByWarehouse(ProductVariant, Warehouse)` | Stock en almacén específico |
| `getTotalStock(ProductVariant)` | Total de stock en todos los almacenes |

**Regla crítica:** Este es el único punto de entrada para modificar stock. Nunca modificar `Stock::quantity` directamente.

---

### PurchaseService
**Archivo:** `app/Services/PurchaseService.php`
**Depende de:** `InventoryService`

| Método | Descripción |
|--------|-------------|
| `createPurchase(array $data)` | Crea OC, items, opcionalmente recibe productos |
| `receiveProducts(Purchase)` | Recibe OC pendiente y agrega stock |
| `cancelPurchase(Purchase)` | Cancela OC (solo si no fue recibida) |

**Nota:** `cancelPurchase` no revierte stock si la OC ya fue recibida. Pendiente de implementación de reversa.

---

### LocationService
**Archivo:** `app/Services/LocationService.php`
**Depende de:** Nada

Gestiona el sistema de ubicaciones de almacén: pasillo (A, B, ..., AA, AB) → estantería → fila → nivel.

| Método | Descripción |
|--------|-------------|
| `generateNextAisleCode(Warehouse)` | Auto-genera código de pasillo (A→Z→AA→AB...) |
| `createLocation(Warehouse, array)` | Crea pasillo con estanterías/filas/niveles |
| `getFullLocationCode(ShelfLevel)` | Código completo: A-01-02-03 |
| `assignLocation(ProductVariant, ShelfLevel, int)` | Asigna producto a ubicación |
| `getProductLocations(ProductVariant)` | Ubicaciones de un producto |
| `getProductsWithoutLocation()` | Productos sin ubicación asignada |

---

### CreditService
**Archivo:** `app/Services/CreditService.php`
**Depende de:** Nada

| Método | Descripción |
|--------|-------------|
| `recordSalePayment(Sale, float)` | Registra pago/entrega a cuenta |
| `updateCustomerBalance($customer)` | Recalcula saldo actual del cliente |

**Nota:** El saldo se calcula como `Σ ventas a crédito - Σ pagos del cliente`.

---

### ReceiptService
**Archivo:** `app/Services/ReceiptService.php`
**Depende de:** `barryvdh/laravel-dompdf`

| Método | Descripción |
|--------|-------------|
| `generateReceipt(Model, string $type)` | Genera PDF para venta/compra/caja |
| `generatePdf(Model, Receipt, string)` | Crea el archivo PDF físico |

Templates en `resources/views/pdf/`: `ticket.blade.php`, `invoice.blade.php`, `purchase_ticket.blade.php`, `cash_register_report.blade.php`.

---

### SifenCdcService
**Archivo:** `app/Services/SifenCdcService.php`
**Depende de:** Nada (puro, sin DB)

| Método | Descripción |
|--------|-------------|
| `generateSecurityCode()` | 9 dígitos aleatorios (`dCodSeg`) |
| `buildBase(...)` | Concatena los 43 chars base del CDC |
| `calculateCheckDigit(string)` | Módulo 11 → dígito verificador (0-9) |
| `generateFromSale(Sale, string)` | CDC completo de 44 chars desde una venta |

**Algoritmo CDC:** `iTiDE(2) + RUC(8) + DV(1) + Est(3) + Punto(3) + NumDoc(7) + SisFact(1) + Fecha(8) + TipoEmi(1) + CodSeg(9) + DV(1)`

---

### SifenQrService
**Archivo:** `app/Services/SifenQrService.php`
**Depende de:** Nada (puro, sin DB)

| Método | Descripción |
|--------|-------------|
| `generate(Sale, string $cdc, string $digestValue)` | URL QR completa con cHashQR |

**Hash:** `SHA256(queryString + CSC_VAL)` donde la fecha va en hex y el DigestValue XML también en hex.

---

### SifenXmlService
**Archivo:** `app/Services/SifenXmlService.php`
**Depende de:** `SifenCdcService`, `SifenQrService`

| Método | Descripción |
|--------|-------------|
| `generate(Sale)` | XML completo `<rDE>` listo para firma |

**Secciones del XML generadas:**
- `<gOpeDE>` — operación del documento
- `<gTimb>` — datos de timbrado
- `<gDatGralOpe>` — fecha, operación comercial, emisor, receptor
- `<gDtipDE>` — tipo de documento, condición pago, ítems con IVA
- `<gTotSub>` — totales y subtotales por tasa de IVA
- `<gCamFuFD>` — URL del código QR

**Pendiente:** Bloque `<Signature>` con firma RSA-SHA256 usando `robrichards/xmlseclibs`.

---

## Dependencias entre Servicios

```
SifenXmlService
    ├── SifenCdcService (CDC 44 dígitos)
    └── SifenQrService (URL QR)

SaleService
    └── InventoryService (gestión de stock)

PurchaseService
    └── InventoryService (gestión de stock)
```

---

## Patrones Comunes

### Transacciones
```php
return DB::transaction(function () use ($data) {
    $model = Model::create([...]);
    $this->otherService->doSomething($model, ...);
    return $model;
});
```

### Movimientos de Stock
Cada operación de stock registra un `StockMovement` con:
- `type`: sale, purchase, adjustment, transfer, return
- `quantity`: positivo (entrada) o negativo (salida)
- `reference_id` + `reference_type`: el objeto que originó el movimiento
- `user_id`: el usuario responsable

### Audit Log
Todos los modelos implementan `LogsActivity` de Spatie. El log registra automáticamente `created`, `updated`, `deleted` en los campos especificados en `logOnly()`.
