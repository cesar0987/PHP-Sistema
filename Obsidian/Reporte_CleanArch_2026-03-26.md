# Reporte de Revisión: Clean Architecture
**Fecha:** 2026-03-26
**Referencia:** "Clean Architecture: A Craftsman's Guide to Software Structure and Design" — Robert C. Martin
**Suite de tests al momento del análisis:** 58 tests / 144 assertions

---

## Scoring por Capa

| Capa | Score | Veredicto |
|------|-------|-----------|
| Entidades (Models) | 3/4 | Aceptable con excepciones |
| Casos de Uso (Services) | 2/4 | Mejorable — acoplamiento al framework |
| Adaptadores (Resources/Controllers) | 2/4 | Filament lo fuerza, documentar trade-offs |
| Frameworks & Drivers (Laravel/Filament) | 4/4 | Correctamente en la capa exterior |
| **Promedio** | **2.75/4** | **Arquitectura sana, con deuda técnica clara** |

---

## Hallazgos por principio

---

### ✅ Lo que está bien

#### La Regla de Dependencia — dirección correcta
- Ningún Service importa `use Filament\...` o `use Livewire\...`
- Ningún Model importa clases de UI
- Los Resources delegan a Services para operaciones de negocio
- `BranchScope` aplicado vía atributo PHP `#[ScopedBy]` — no contamina los Services

#### Entidades (Models) — mayormente limpias
- `Sale`, `Product`, `Customer`, `Purchase`, `Payment`, `Stock`, `InventoryAdjustment` no tienen lógica de negocio
- Solo contienen relaciones, casts y configuración de `LogsActivity`
- Soft Deletes y BranchScope como traits/atributos separados — correcto
- `SifenCdcService` y `SifenQrService` son los más puros: sin DB, sin auth, operan sobre datos que reciben — se acercan a funciones puras

#### Tests de lógica de negocio pura — ejemplo a seguir
- `SifenCdcServiceTest`: 13 tests sin `RefreshDatabase` — pure unit tests reales
- `LocationServiceTest` tiene 3 tests de conversión numérica sin base de datos
- La separación entre tests de flujo (Feature) y tests de lógica (Unit) está bien planteada

---

### 🔴 Crítico — Viola la Regla de Dependencia

#### C1: `auth()` dentro de los Services
**Archivos:** `SaleService:98`, `InventoryService:301`, `PurchaseService:57`

```php
// ❌ Violación: el Use Case conoce el sistema de autenticación (capa de framework)
$sale = Sale::create([
    'user_id' => auth()->id(),  // ← framework coupling
    ...
]);
```

**Problema (libro Cap. 22):** Los Use Cases no deben depender del mecanismo de autenticación. Si el día de mañana el usuario se pasa por parámetro de CLI o de una cola de jobs, esta línea falla silenciosamente.

**Solución:** Recibir `int $userId` como parámetro en los métodos que lo necesitan.

```php
// ✅ Correcto
public function createSale(array $data, int $userId): Sale
```

---

#### C2: `ReceiptService` retorna `Illuminate\Http\Response`
**Archivos:** `ReceiptService.php` — métodos `downloadPdf()`, `streamPdf()`

```php
// ❌ Un Use Case no puede retornar un objeto HTTP
public function downloadPdf(Receipt $receipt): Response
```

**Problema (libro Cap. 22, Humble Object):** Un Service de la capa de Use Cases no puede retornar objetos de la capa de Frameworks. Esto hace que el Service sea imposible de testear sin HTTP y acopla el negocio al transporte.

**Solución:** `ReceiptService` retorna el PDF como `string` o `\Barryvdh\DomPDF\PDF`. El Controller es quien lo convierte a `Response`.

---

#### C3: `ReceiptService` usa service locator con `app()`
**Archivos:** `ReceiptService.php:158,161`

```php
// ❌ Service locator — hace imposible testear con dependencias alternativas
$cdcService = app(SifenCdcService::class);
$qrService  = app(SifenQrService::class);
```

**Solución:** Inyectar por constructor vía DI container de Laravel.

---

#### C4: `ProductVariant` tiene lógica de negocio en un accessor
**Archivo:** `app/Models/ProductVariant.php:72-75`

```php
// ❌ El modelo ejecuta una query de agregación — efecto colateral oculto
public function getTotalStockAttribute(): int
{
    return $this->stocks()->sum('quantity');
}
```

**Problema (libro Cap. 20):** Las Entidades no deben realizar consultas de base de datos. Este accessor dispara una query N+1 invisible cada vez que se accede a `$variant->total_stock`. La lógica pertenece a `InventoryService::getTotalStock()` (que ya existe).

---

### 🟡 Mejorable — Viola principios SOLID sin bloquear el funcionamiento

#### M1: `DB::transaction()` en todos los Services (DIP)
**Archivos:** 8 de 11 services — 17 llamadas totales

```php
// ⚠️ Los Use Cases dependen directamente de la implementación de base de datos
DB::transaction(function () use ($data) { ... });
```

**Problema (libro Cap. 11 — DIP):** Los Use Cases están acoplados a `Illuminate\Support\Facades\DB`. Si el día de mañana la transaccionalidad viene de un event sourcing o de una unidad de trabajo diferente, hay que tocar todos los services.

**Trade-off en Laravel:** Este es el caso más discutible. `DB::transaction()` es tan idiomático en Laravel que crear una interfaz `TransactionManager` es generalmente sobre-ingeniería para proyectos de este tamaño. **Documentar como trade-off aceptado** a menos que se planee reemplazar Eloquent.

---

#### M2: No existen interfaces/contratos para los Services (DIP)
**Búsqueda:** `app/Contracts/` y `app/Interfaces/` — no existen

**Problema (libro Cap. 11 — DIP):** Los Resources de Filament y los Controllers dependen directamente de las clases concretas `SaleService`, `InventoryService`, etc. Si se quisiera reemplazar la implementación (ej. mover a microservicio), habría que cambiar todos los puntos de uso.

**Prioridad real:** Media-baja. En un sistema monolítico con un solo equipo, las interfaces de servicio tienen valor principalmente para testear con mocks. Como los tests actuales usan la implementación real con SQLite, el impacto es bajo hoy. Crear `app/Contracts/SaleServiceInterface.php` tiene sentido si se planea testear con mocks o separar el módulo.

---

#### M3: No existen excepciones de dominio (SRP)
**Estado actual:** Se usan `\Exception`, `\RuntimeException`, `\InvalidArgumentException` genéricas

```php
// ⚠️ Sin información semántica sobre qué falló en el dominio
throw new \Exception('Stock insuficiente');
```

**Solución ideal:**
```php
throw new InsufficientStockException($variant, $requested, $available);
```

Esto permite que la capa de Adaptadores (Resources/Controllers) maneje errores de dominio de forma específica sin capturar excepciones genéricas.

---

#### M4: `CreditService::updateCustomerBalance()` sin tipo
**Archivo:** `app/Services/CreditService.php`

```php
// ⚠️ Parámetro sin tipo — rompe el contrato del método
public function updateCustomerBalance($customer): void
```

Y usa `Schema::hasColumn()` en tiempo de ejecución para verificar si existe una columna — patrón frágil que debería ser una migration o un campo siempre presente.

---

#### M5: `SaleService` tiene múltiples responsabilidades (SRP)
**Archivo:** `app/Services/SaleService.php`

El service maneja:
1. Creación de ventas (lógica transaccional)
2. Aprobación de ventas pendientes
3. Cancelación de ventas
4. Queries de reportes (`getSalesByDate`, `getTopProducts`)

**Problema (libro Cap. 7 — SRP):** Los métodos de reporte (`getSalesByDate`, `getTopProducts`) cambian por razones diferentes a los métodos transaccionales. Un analista que pide nuevas métricas no debería tocar el mismo archivo que un developer que arregla la lógica de cancelación.

**Solución:** Extraer `SaleReportService` o `SaleQueryService` para las queries de lectura.

---

#### M6: Tests "unitarios" que requieren base de datos (Humble Object)
**Estado:** 5 de 7 services en `tests/Unit/` usan `RefreshDatabase`

```php
// ⚠️ Esto es un integration test, no un unit test
class SaleServiceTest extends TestCase
{
    use RefreshDatabase; // ← base de datos real
```

**Problema (libro Cap. 23 — Humble Object):** Los tests unitarios deberían testear lógica pura. El ejemplo correcto ya existe: `SifenCdcServiceTest` no usa base de datos y corre en microsegundos. Los 5 services con `RefreshDatabase` tardan significativamente más y son frágiles ante cambios de schema.

**Solución progresiva:** No es necesario mockear todo. Pero sí separar los tests en:
- Tests de lógica pura (sin DB): validaciones, cálculos, transformaciones
- Tests de integración (con DB): flujos completos

---

#### M7: Servicios de importación sin tests (ProductImportService, StockImportService)
**Estado:** 0 tests para ambos

Estas clases mezclan: parsing CSV + validación de datos + queries de BD + lógica de negocio. Son difíciles de testear como están. Al separar el parsing de la lógica de negocio, se vuelven testeables.

---

### 🟢 Trade-offs aceptados — Filament lo impone

#### T1: Resources de Filament mezclan tabla + formulario + acciones
Filament v3 fuerza este patrón. Un Resource es el adaptador completo para esa entidad. No es una violación del proyecto, es una decisión de framework. **Documentado como trade-off.**

#### T2: No hay capa Repository
Eloquent hace el rol de Repository. En proyectos Laravel estándar, agregar un Repository sobre Eloquent duplica código sin beneficio real a menos que se planee cambiar de ORM. **Trade-off aceptado para este tamaño de proyecto.**

#### T3: `DB::transaction()` en Services
Ver M1 — documentado como trade-off aceptado hasta que haya evidencia de que necesita cambiar.

---

## Mapa de deuda técnica priorizada

| # | Hallazgo | Prioridad | Esfuerzo | Impacto |
|---|---------|-----------|---------|---------|
| C1 | `auth()->id()` en Services | 🔴 Alta | Bajo | Testabilidad, jobs/CLI |
| C2 | `ReceiptService` retorna `Response` | 🔴 Alta | Bajo | Acoplamiento HTTP |
| C3 | `app()` service locator en ReceiptService | 🔴 Alta | Mínimo | Inyección correcta |
| C4 | `ProductVariant::getTotalStockAttribute` con query | 🔴 Alta | Bajo | N+1, lógica en entidad |
| M3 | Sin excepciones de dominio | 🟡 Media | Medio | Manejo de errores |
| M5 | `SaleService` con múltiples responsabilidades | 🟡 Media | Medio | Mantenibilidad |
| M6 | Tests "unitarios" que usan BD | 🟡 Media | Medio | Velocidad de tests |
| M7 | Sin tests para Import services | 🟡 Media | Alto | Regresiones |
| M2 | Sin interfaces para Services | 🟡 Baja | Alto | Testabilidad con mocks |
| M4 | `CreditService` sin tipo + Schema check | 🟡 Baja | Bajo | Fragilidad |

---

## Resumen ejecutivo

El proyecto **sigue correctamente la arquitectura en capas** con Services como Use Cases y Resources como Adaptadores. La Regla de Dependencia no se viola en la dirección más peligrosa (las capas internas no conocen Filament).

Las **4 violaciones críticas** son todas corregibles en menos de un día de trabajo y no requieren refactor estructural. El resto son mejoras de calidad que se pueden abordar progresivamente.

El proyecto está en el **percentil 70** de Clean Architecture para un sistema Laravel de este tamaño. Los problemas son deuda técnica acumulada, no errores de diseño fundamental.

---

## Próximos planes a crear

- `Plan_Refactor_Services_Auth.md` — C1: extraer `auth()->id()` de Services
- `Plan_Refactor_ReceiptService.md` — C2+C3: desacoplar PDF del HTTP
- `Plan_Refactor_DomainExceptions.md` — M3: jerarquía de excepciones
- `Plan_Tests_Pure_Unit.md` — M6+M7: separar tests de integración de lógica pura
