# Documentación de Tests — POS Ferretería

Este documento describe la suite de tests automatizados del sistema, su propósito, configuración necesaria y el detalle de cada caso de prueba.

---

## Configuración General

| Clave | Valor |
|---|---|
| **Framework** | PHPUnit 11.x vía Laravel |
| **Base de datos** | SQLite `:memory:` (efímera por test) |
| **Trait principal** | `RefreshDatabase` — migra y limpia la BD en cada test |
| **Ejecución** | `composer test` o `php artisan test` |

### Datos de Prueba Estándar (setUp)
La mayoría de los tests crean este entorno base:
- **Company** → nombre + RUC mínimo
- **Branch** → vinculada a la Company
- **Warehouse** → vinculado a la Branch
- **Category** → nombre genérico
- **Product** → con `sale_price`, `cost_price`, `tax_percentage`, `active=true`
- **ProductVariant** → con SKU único
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

### 4. `LocationServiceTest` (6 tests)

**Archivo:** `tests/Unit/LocationServiceTest.php`
**Servicio bajo prueba:** `App\Services\LocationService`

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_number_to_letters` | Convierte número a código alfabético (1→A, 26→Z, 27→AA) |
| 2 | `test_letters_to_number` | Convierte código alfabético a número (A→1, Z→26, AA→27) |
| 3 | `test_roundtrip` | Conversión ida y vuelta produce el mismo número |
| 4 | `test_create_location` | Crea ubicación con código correcto (A-01-02) |
| 5 | `test_assign_location` | Asigna variante a ubicación existente |
| 6 | (adicional) | Casos de borde en conversión |

---

### 5. `CreditServiceTest` (2 tests)

**Archivo:** `tests/Unit/CreditServiceTest.php`
**Servicio bajo prueba:** `App\Services\CreditService`

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_record_sale_payment` | Registra pago de crédito y actualiza saldo del cliente |
| 2 | `test_update_customer_balance` | Actualiza balance de crédito disponible correctamente |

---

### 6. `SifenCdcServiceTest` (15 tests)

**Archivo:** `tests/Unit/SifenCdcServiceTest.php`
**Servicio bajo prueba:** `App\Services\SifenCdcService`
**Sin RefreshDatabase** — servicio puro sin base de datos.

**Constante de referencia:**
```
EXAMPLE_BASE = '0100000001900100110000502202005071000000023'  // 43 chars
DV = 3  (módulo 11 sobre EXAMPLE_BASE)
CDC = EXAMPLE_BASE . '3'  // 44 chars
```

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_build_base_produces_43_character_string` | `buildBase()` retorna exactamente 43 caracteres |
| 2 | `test_build_base_matches_reference_example` | Resultado exacto igual a `EXAMPLE_BASE` |
| 3 | `test_build_base_segments_are_correct` | Cada segmento ocupa la posición correcta (iTiDE=0-1, RUC=2-9, etc.) |
| 4 | `test_build_base_pads_ruc_to_8_digits` | RUC corto se rellena con ceros a la izquierda |
| 5 | `test_build_base_pads_numero_doc_to_7_digits` | NumDoc corto se rellena a 7 dígitos |
| 6 | `test_build_base_pads_cod_seg_to_9_digits` | CodSeg corto se rellena a 9 dígitos |
| 7 | `test_check_digit_for_reference_base` | DV para EXAMPLE_BASE es exactamente 3 |
| 8 | `test_check_digit_returns_zero_when_sum_is_multiple_of_11` | 43 ceros → DV=0 |
| 9 | `test_check_digit_is_always_a_single_digit` | DV siempre en rango 0-9 para múltiples bases |
| 10 | `test_check_digit_is_deterministic` | La misma base siempre produce el mismo DV |
| 11 | `test_generate_security_code_has_9_digits` | Código de seguridad tiene exactamente 9 dígitos numéricos |
| 12 | `test_generate_security_code_is_random` | 10 generaciones producen al menos 2 valores distintos |
| 13 | `test_full_cdc_is_44_characters` | CDC completo (base + DV) tiene 44 caracteres |
| 14 | `test_full_cdc_matches_reference_example` | CDC completo = `EXAMPLE_BASE . '3'` |
| 15 | `test_full_cdc_last_digit_matches_calculated_dv` | Último dígito del CDC coincide con DV calculado |

---

### 7. `ExampleTest` (Unit) (1 test)

**Archivo:** `tests/Unit/ExampleTest.php`

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_that_true_is_true` | Test trivial de sanidad — verifica que PHPUnit funciona |

---

## Tests de Feature

Los tests de feature crean un entorno real con base de datos y validan flujos completos de extremo a extremo.

---

### 8. `SaleFlowTest` (5 tests)

**Archivo:** `tests/Feature/SaleFlowTest.php`
**Stock inicial:** 10 unidades. Producto: Martillo, precio 50000, IVA 10%.

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_completed_sale_deducts_stock` | Venta `completed` descuenta stock (10-2=8) y registra `stock_movements` tipo `sale` |
| 2 | `test_pending_sale_does_not_deduct_stock` | Venta `pending` (nota de pedido) NO modifica stock |
| 3 | `test_approve_pending_sale_deducts_stock` | `approveSale()` cambia a `completed` y descuenta stock en ese momento |
| 4 | `test_cancel_sale_returns_stock` | Cancelar venta `completed` devuelve stock al inventario (7+3=10) |
| 5 | `test_create_sale_fails_with_insufficient_stock` | Intentar vender 999 unidades lanza `Exception` |

---

### 9. `PurchaseFlowTest` (4 tests)

**Archivo:** `tests/Feature/PurchaseFlowTest.php`
**Stock inicial:** 10 unidades. Producto: Tornillo 10mm.

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_pending_purchase_does_not_add_stock` | Compra `pending` no modifica stock (sigue en 10) |
| 2 | `test_receive_purchase_adds_stock` | `receiveProducts()` agrega stock (10+50=60) y registra `stock_movements` tipo `purchase` |
| 3 | `test_purchase_with_receive_products_adds_stock_immediately` | Compra con `receive_products=true` agrega stock inmediatamente (10+20=30) |
| 4 | `test_cancel_purchase_changes_status` | Cancelar compra cambia status a `cancelled` sin modificar stock |

---

### 10. `CashRegisterFlowTest` (4 tests)

**Archivo:** `tests/Feature/CashRegisterFlowTest.php`
**Caja abierta:** Caja Principal, `opening_amount=50000`.

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_open_cash_register_has_open_status` | Caja creada tiene `status='open'`, `opened_at` con valor, `closed_at` nulo |
| 2 | `test_close_cash_register_records_closing_data` | Cerrar caja guarda `status='closed'`, `closing_amount` y `closed_at` |
| 3 | `test_sales_are_linked_to_cash_register` | Venta creada con `cash_register_id` queda asociada a la caja |
| 4 | `test_cash_register_total_from_completed_sales` | Suma de ventas completadas es correcta (88000+176000=264000 con IVA 10%) |

---

### 11. `AuthFlowTest` (2 tests)

**Archivo:** `tests/Feature/AuthFlowTest.php`
**Componente Livewire:** `Filament\Pages\Auth\Login`
**Rate limit:** 5 intentos por minuto por IP (via `danharrin/livewire-rate-limiting`)

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_invalid_credentials_return_validation_error` | Credenciales incorrectas retornan error de validación en `data.email` |
| 2 | `test_login_is_blocked_after_five_attempts` | Tras 5 hits al rate limiter, el intento queda bloqueado y el usuario NO se autentica |

**Clave del rate limiter:**
```php
$key = 'livewire-rate-limiter:' . sha1(Login::class . '|authenticate|127.0.0.1');
```

---

### 12. `ExampleTest` (Feature) (1 test)

**Archivo:** `tests/Feature/ExampleTest.php`

| # | Test | Qué valida |
|---|---|---|
| 1 | `test_the_application_returns_a_successful_response` | La ruta `/` retorna 302 (redirect a login), confirmando que el middleware auth funciona |

---

## Resumen de Cobertura

| Suite | Tipo | Tests | Qué cubre |
|---|---|---|---|
| `InventoryServiceTest` | Unit | 10 | `addStock`, `removeStock`, `adjustStock`, `transferStock`, `checkMinimum`, `getTotalStock` |
| `SaleServiceTest` | Unit | 4 | `calculateTotal`, `createSale`, `cancelSale` |
| `PurchaseServiceTest` | Unit | 4 | `createPurchase`, `receiveProducts`, `cancelPurchase` |
| `LocationServiceTest` | Unit | 6 | `numberToLetters`, `lettersToNumber`, `createLocation`, `assignLocation` |
| `CreditServiceTest` | Unit | 2 | `recordSalePayment`, `updateCustomerBalance` |
| `SifenCdcServiceTest` | Unit | 15 | `buildBase`, `calculateCheckDigit`, `generateSecurityCode`, CDC completo |
| `ExampleTest` (Unit) | Unit | 1 | Sanidad PHPUnit |
| `SaleFlowTest` | Feature | 5 | Flujo venta: completar, pendiente, aprobar, cancelar, stock insuficiente |
| `PurchaseFlowTest` | Feature | 4 | Flujo compra: pending, recibir, recibir inmediato, cancelar |
| `CashRegisterFlowTest` | Feature | 4 | Flujo caja: abrir, cerrar, vincular ventas, totales |
| `AuthFlowTest` | Feature | 2 | Login: credenciales inválidas, rate limit 5 intentos |
| `ExampleTest` (Feature) | Feature | 1 | Middleware auth redirige a login |
| **Total** | | **58** | **144 assertions** |

---

## Historial de Ejecuciones

| Fecha | Tests | Assertions | Resultado | Tiempo |
|---|---|---|---|---|
| 14/03/2026 | 20 | 39 | ✅ OK | 0.81s |
| 14/03/2026 | 28 | 72 | ✅ OK | 0.84s |
| 26/03/2026 | 58 | 144 | ✅ OK | 2.07s |

---

## Notas Técnicas Importantes

### `payments.method` — Enum Estricto
La columna usa enum SQL. Valores válidos únicamente:

| Valor | Descripción |
|---|---|
| `cash` | Efectivo |
| `card` | Tarjeta |
| `transfer` | Transferencia bancaria |
| `qr` | Pago QR |

> [!CAUTION]
> Nunca usar `'efectivo'`, `'Efectivo'` ni valores en español. Los tests fallarán con `CHECK constraint failed: method`.

### Ventas Pendientes y Stock
Las ventas con `status='pending'` (notas de pedido) **NO descuentan stock**. El descuento ocurre al aprobar con `approveSale()`. Este comportamiento fue corregido en `SaleService::createSale()` — antes descountaba stock en ambos momentos (bug de doble descuento).

### `resolveUserId()` en InventoryService
Se requiere usuario autenticado para registrar `stock_movements`. Si no hay sesión activa, se lanza `RuntimeException`. En tests siempre llamar `$this->actingAs($user)` en `setUp()`.

### Rate Limiter en Tests de Auth
El componente `Filament\Pages\Auth\Login` usa `danharrin/livewire-rate-limiting` con límite de 5 intentos/minuto por IP. Para testear el bloqueo, pre-cargar el limiter con `RateLimiter::hit($key, 60)` × 5 antes de la aserción.

---

## Cómo Ejecutar

```bash
# Todos los tests
composer test

# Solo unitarios
php artisan test tests/Unit/

# Solo feature
php artisan test tests/Feature/

# Un archivo específico
php artisan test tests/Unit/SifenCdcServiceTest.php

# Un test específico
php artisan test --filter=test_build_base_matches_reference_example
```
