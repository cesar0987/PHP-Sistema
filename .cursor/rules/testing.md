---
description: Testing conventions — PHPUnit, RefreshDatabase, service tests
alwaysApply: false
scope: testing
---

# Testing Conventions

## Stack
- **PHPUnit 11** con Laravel TestCase
- **SQLite en memoria** (`:memory:`) — configurado en `phpunit.xml`
- `RefreshDatabase` trait para reset entre tests
- **Total actual: 58 tests, 144 assertions**

## Estructura de Tests

```
tests/
├── Unit/           # Tests de servicios (sin HTTP, sin Filament)
│   ├── SaleServiceTest.php
│   ├── InventoryServiceTest.php
│   ├── PurchaseServiceTest.php
│   ├── LocationServiceTest.php
│   ├── CreditServiceTest.php
│   └── SifenCdcServiceTest.php
└── Feature/        # Tests de flujos completos (con DB real)
    ├── SaleFlowTest.php
    ├── PurchaseFlowTest.php
    ├── CashRegisterFlowTest.php
    └── AuthFlowTest.php
```

## Patrón Base para Tests de Servicio

```php
<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\MyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MyService $service;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Autenticar usuario (requerido para BranchScope y resolveUserId)
        $user = User::factory()->create();
        $this->actingAs($user);

        // 2. Crear estructura mínima (Company → Branch)
        $company = Company::create(['name' => 'Test Co', 'ruc' => '123']);
        $this->branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Test',
        ]);

        // 3. Instanciar servicio via container (respeta DI)
        $this->service = app(MyService::class);
    }

    public function test_something_does_expected_behavior(): void
    {
        // Arrange
        $input = [...];

        // Act
        $result = $this->service->doSomething($input);

        // Assert
        $this->assertEquals($expected, $result);
    }
}
```

## Patrón para Tests Livewire (Filament)

Los componentes Filament son componentes Livewire. Se testean con `Livewire::test()` pasando la clase de la página directamente.

```php
use Filament\Pages\Auth\Login;
use Livewire\Livewire;

// Test de componente Livewire Filament
Livewire::test(Login::class)
    ->set('data.email', 'user@test.com')
    ->set('data.password', 'wrong')
    ->call('authenticate')
    ->assertHasErrors(['data.email']);
```

Los campos del formulario Filament viven bajo la clave `data.*` (no directamente como `email`). Usar siempre `data.email`, `data.password`, etc.

## Enum constraints en tests

La columna `payments.method` es un **enum** de base de datos con los valores:

```
['cash', 'card', 'transfer', 'qr']
```

**Nunca** usar `'efectivo'`, `'Efectivo'`, `'tarjeta'` ni ningún valor en español en los tests. Insertar un valor fuera del enum produce un error de SQLite/PostgreSQL que no siempre es descriptivo.

```php
// ✅ Correcto
'method' => 'cash'
'method' => 'card'
'method' => 'transfer'
'method' => 'qr'

// ❌ Incorrecto — rompe el constraint de enum
'method' => 'efectivo'
'method' => 'Efectivo'
```

## Rate Limiter en tests

Filament Login aplica rate limiting por IP + clase + acción. En tests, el rate limiter usa la IP `127.0.0.1`. Para testear el comportamiento de lockout hay que pre-llenarlo:

```php
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\RateLimiter;

// Key del rate limiter de Filament Login:
$key = 'livewire-rate-limiter:' . sha1(Login::class . '|authenticate|127.0.0.1');
RateLimiter::hit($key, 60); // x5 para llegar al límite
```

El límite por defecto de Filament es 5 intentos. Llamar `RateLimiter::hit($key, 60)` cinco veces antes de ejecutar el componente simula que ya se agotaron los intentos y dispara el error de `Too many requests`.

## Tests para Servicios SIFEN (Puro / Sin DB)

Los tests de `SifenCdcService` y `SifenQrService` son tests **puros** (sin base de datos):

```php
class SifenCdcServiceTest extends TestCase
{
    // SIN RefreshDatabase — no necesita DB

    public function test_calculate_check_digit_matches_manual_example(): void
    {
        $service = new SifenCdcService();
        $cdcBase = '0100000001900100110000502202005071000000023';  // 43 chars
        $dv = $service->calculateCheckDigit($cdcBase);
        $this->assertEquals(3, $dv);  // Valor del manual SET
    }
}
```

El ejemplo base correcto según el manual SET es `'0100000001900100110000502202005071000000023'` (43 caracteres), cuyo dígito verificador es `3`, dando el CDC completo de 44 dígitos.

## Nomenclatura de Tests

Formato: `test_[que_hace]_[condicion]_[resultado_esperado]()`

```php
test_create_sale_creates_sale_and_deducts_stock()   ✅
test_remove_stock_throws_on_insufficient_stock()    ✅
test_calculate_total_computes_correctly()            ✅

testCreateSale()        ❌ (muy vago, camelCase no preferido)
test_it_works()         ❌ (no describe nada)
```

## Reglas de Tests

### Lo que SÍ testear
- Lógica de cálculo en servicios (totales, IVA, CDC, módulo 11).
- Efectos secundarios críticos (stock se decrementa, balance se actualiza).
- Casos de error explícitos (`expectException`).
- Invariantes de negocio (no vender sin stock, no cancelar lo ya cancelado).

### Lo que NO testear
- Que Eloquent guarda datos (Laravel lo garantiza).
- Validación de formularios Filament (es declarativa).
- Que los modelos tienen relaciones (se ve en el código).
- Código de terceros (Filament, Spatie, etc.).

## Mínimo de Tests por Servicio

| Servicio / Flujo | Tests mínimos requeridos |
|------------------|--------------------------|
| `Unit/SaleService` | calculateTotal, createSale, cancelSale, stock insuficiente — **5 tests** |
| `Unit/InventoryService` | addStock, removeStock, transfer, checkMinimum |
| `Unit/PurchaseService` | createPurchase, receiveProducts, cancelPurchase — **4 tests** |
| `Unit/CreditService` | recordSalePayment, updateCustomerBalance |
| `Unit/SifenCdcServiceTest` | buildBase (longitud=43), calculateCheckDigit (exactitud), generateSecurityCode, CDC completo — **15 tests** |
| `Feature/SaleFlowTest` | crear venta, descuento, pago parcial, cancelar, stock revertido — **5 tests** |
| `Feature/PurchaseFlowTest` | crear compra, recibir productos, cancelar, stock actualizado — **4 tests** |
| `Feature/CashRegisterFlowTest` | estado open, cierre, ventas vinculadas, totales |
| `Feature/AuthFlowTest` | credenciales inválidas, lockout 5 intentos |

## Correr Tests

```bash
composer test                                          # Todos
php artisan test tests/Unit/                           # Solo unitarios
php artisan test tests/Unit/SifenCdcServiceTest.php   # Un archivo
php artisan test --filter=test_calculate_check_digit  # Un test
```
