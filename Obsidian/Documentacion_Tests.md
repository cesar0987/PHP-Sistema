# Documentación de Tests — POS Ferretería

Este documento describe la suite de tests automatizados del sistema, su propósito, configuración necesaria y el detalle de cada caso de prueba.

---

## Configuración General

| Clave | Valor |
|---|---|
| **Framework** | PHPUnit 11.x vía Laravel |
| **Base de datos** | SQLite `:memory:` (efímera por test) |
| **Trait principal** | `RefreshDatabase` — migra y limpia la BD en cada test |
| **Ejecución** | `./vendor/bin/phpunit --no-coverage` o `php artisan test` |

### Datos de Prueba Estándar (setUp)
La mayoría de los tests crean este entorno base:
- **Company** → `Test Co`
- **Branch** → `Sucursal Test`
- **Warehouse** → `Almacén Test` (default, activo)
- **Category** → `Test Cat`
- **Product** → `Producto Test` (costo: 5000, venta: 10000, min_stock: 5, IVA: 10%)
- **ProductVariant** → `Default` (SKU: `TEST-001`)
- **User** → Factory, autenticado con `actingAs()`

> [!IMPORTANT]
> El usuario **debe estar autenticado antes** de cualquier operación de inventario, ya que `InventoryService` requiere `auth()->id()` para registrar el responsable en `stock_movements`. Si no hay usuario, se lanza `RuntimeException`.

---

## Tests Unitarios

### 1. `InventoryServiceTest` (10 tests)

**Archivo:** `tests/Unit/InventoryServiceTest.php`
**Servicio bajo prueba:** `App\Services\InventoryService`

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_add_stock_creates_stock_record` | Agregar stock crea el registro en `stocks` con la cantidad correcta |
| 2 | `test_add_stock_increments_existing_stock` | Agregar stock múltiples veces acumula las cantidades (30+20=50) |
| 3 | `test_remove_stock_decrements_quantity` | Remover stock descuenta correctamente (50-20=30) |
| 4 | `test_remove_stock_throws_on_insufficient_stock` | Lanza `Exception` con mensaje *"Stock insuficiente"* si se intenta remover más del disponible |
| 5 | `test_adjust_stock_sets_exact_quantity` | Ajuste fija la cantidad exacta (50→35) y crea un `InventoryAdjustment` aprobado |
| 6 | `test_transfer_stock_moves_between_warehouses` | Transferencia resta del origen y suma al destino (50→30/20) |
| 7 | `test_transfer_stock_throws_on_insufficient_stock` | Lanza `Exception` si el origen no tiene stock suficiente para transferir |
| 8 | `test_check_minimum_detects_low_stock` | Detecta stock bajo mínimo (cantidad 3 ≤ min_stock 5) |
| 9 | `test_check_minimum_passes_when_above` | No dispara alerta cuando stock (50) supera el mínimo (5) |
| 10 | `test_get_total_stock_sums_all_warehouses` | Suma stock de múltiples almacenes correctamente (30+20=50) |

---

### 2. `SaleServiceTest` (4 tests)

**Archivo:** `tests/Unit/SaleServiceTest.php`
**Servicio bajo prueba:** `App\Services\SaleService`
**Stock inicial:** 100 unidades pre-cargadas en setUp

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_calculate_total_computes_correctly` | Cálculo: 3×10000=30000 subtotal, 3000 IVA (10%), 500 descuento → total 32500 |
| 2 | `test_create_sale_creates_sale_and_deducts_stock` | Crea venta con status `completed`, calcula subtotal, y descuenta stock (100-5=95) |
| 3 | `test_cancel_sale_returns_stock` | Cancelar venta devuelve el stock (100→90→100) y cambia status a `cancelled` |
| 4 | `test_create_sale_fails_with_insufficient_stock` | Lanza `Exception` al intentar vender 999 unidades con solo 100 disponibles |

---

### 3. `PurchaseServiceTest` (4 tests)

**Archivo:** `tests/Unit/PurchaseServiceTest.php`
**Servicio bajo prueba:** `App\Services\PurchaseService`

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_create_purchase_without_receiving` | Compra en status `pending`, total correcto (20×5000=100000), stock NO aumenta |
| 2 | `test_create_purchase_with_receiving_adds_stock` | Compra con `receive_products=true` agrega stock inmediatamente (20 unidades) |
| 3 | `test_receive_products_updates_status_and_stock` | Recibir productos cambia status `pending`→`received` y agrega stock (30) |
| 4 | `test_cancel_purchase_changes_status` | Cancelar compra cambia status a `cancelled` |

---

### 4. `ExampleTest` (Unit) (1 test)

**Archivo:** `tests/Unit/ExampleTest.php`

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_that_true_is_true` | Test trivial de sanidad — verifica que PHPUnit funciona |

---

## Tests de Feature

### 5. `ExampleTest` (Feature) (1 test)

**Archivo:** `tests/Feature/ExampleTest.php`

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_the_application_returns_a_successful_response` | La ruta `/` retorna 302 (redirect a login), confirmando que el middleware auth funciona |

---

## Resumen de Cobertura

| Suite | Tests | Servicios cubiertos |
|---|---|---|
| **InventoryServiceTest** | 10 | `addStock`, `removeStock`, `adjustStock`, `transferStock`, `checkMinimum`, `getTotalStock` |
| **SaleServiceTest** | 4 | `calculateTotal`, `createSale`, `cancelSale` |
| **PurchaseServiceTest** | 4 | `createPurchase`, `receiveProducts`, `cancelPurchase` |
| **LocationServiceTest** | 6 | `numberToLetters`, `lettersToNumber`, roundtrip, `createLocation`, `assignLocation` |
| **CreditServiceTest** | 2 | `recordSalePayment`, `updateCustomerBalance` |
| **ExampleTest (Unit)** | 1 | Sanidad PHPUnit |
| **ExampleTest (Feature)** | 1 | Middleware auth |
| **Total** | **28** | |

---

## Historial de Ejecuciones

| Fecha | Tests | Assertions | Resultado | Tiempo | Memoria |
|---|---|---|---|---|---|
| 14/03/2026 15:57 | 20 | 39 | ✅ OK | 0.806s | 64.50 MB |
| 14/03/2026 16:37 | 28 | 72 | ✅ OK | 0.843s | 66.50 MB |

> [!TIP]
> Ejecutar después de cada cambio en Services o Models: `./vendor/bin/phpunit --no-coverage`

---

## Mejora Aplicada: `resolveUserId()`

Se agregó el método protegido `resolveUserId()` en `InventoryService` para evitar que un `user_id` nulo llegue a la base de datos silenciosamente.

**Antes:**
```php
'user_id' => $data['user_id'] ?? auth()->id(),
// Si no hay usuario autenticado → NULL → violación NOT NULL críptica
```

**Después:**
```php
'user_id' => $this->resolveUserId($data['user_id'] ?? null),
// Si no hay usuario → RuntimeException con mensaje claro
```

Esto protege contra errores en contextos sin sesión activa (colas, seeders, comandos Artisan).

---

## Valores Válidos para `payments.method`

La tabla `payments` usa un enum estricto. Valores permitidos:

| Valor | Descripción |
|---|---|
| `cash` | Efectivo |
| `card` | Tarjeta |
| `transfer` | Transferencia bancaria |
| `qr` | Pago QR |

> [!CAUTION]
> No usar valores en español como `'efectivo'` o `'tarjeta'`. Los tests y el código deben usar las claves en inglés definidas en la migración.

---

## Cómo Ejecutar

```bash
# Todos los tests
./vendor/bin/phpunit --no-coverage

# Un test específico
./vendor/bin/phpunit --filter=test_add_stock_creates_stock_record

# Solo una suite
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Feature
```
