---
description: Architectural constraints — clean architecture, service layer, Filament patterns
alwaysApply: true
scope: architecture
---

# Architecture Rules

## Principios Generales
- Seguir principios de clean architecture: separar dominio, aplicación e infraestructura.
- Funciones pequeñas y enfocadas (Single Responsibility).
- Composición sobre herencia.

## Service Layer (REGLA CRÍTICA)
Toda la lógica de negocio vive en `app/Services/`. Esta es la regla más importante del proyecto.

```
Controllers / Filament Resources → Services → Models / DB
```

**Servicios existentes y sus responsabilidades:**

| Servicio | Responsabilidad |
|----------|----------------|
| `SaleService` | Crear, aprobar, cancelar ventas + calcular totales |
| `InventoryService` | Agregar, remover, ajustar y transferir stock |
| `PurchaseService` | Órdenes de compra + recepción de mercadería |
| `LocationService` | Asignación de ubicaciones en almacén (códigos A-01-02-03) |
| `CreditService` | Saldos de crédito de clientes |
| `ReceiptService` | Generación de PDFs (tickets, facturas) |
| `SifenCdcService` | CDC de 44 dígitos para SIFEN v150 |
| `SifenQrService` | URL del código QR con hash CSC |
| `SifenXmlService` | Generación del XML `<rDE>` completo |

**Regla:** Si la lógica requiere más de un modelo o una decisión de negocio, va en un servicio.

## Filament Resources
- Los Resources **solo** definen formularios (`form()`) y tablas (`table()`).
- Las acciones personalizadas delegan al servicio correspondiente.
- No colocar consultas complejas directamente en Resources; usar scopes o servicios.

## Modelos Eloquent
- Definir relaciones con tipos de retorno explícitos (`BelongsTo`, `HasMany`, etc.).
- Usar `$casts` para garantizar tipos correctos (especialmente `decimal:2` para montos).
- Implementar `getActivitylogOptions()` en todos los modelos nuevos.
- Aplicar `SoftDeletes` en modelos que representan entidades de negocio.
- Aplicar `#[ScopedBy([BranchScope::class])]` en modelos que pertenecen a una sucursal.

## BranchScope — Multi-Sucursal
El scope `App\Models\Scopes\BranchScope` se aplica automáticamente. Los usuarios sin rol admin **solo ven datos de su sucursal**.

- **Nunca** filtrar manualmente por `branch_id` en consultas normales.
- Los seeders y tests deben crear datos con `branch_id` válido.
- El scope se bypasea en contexto de comandos de consola sin usuario autenticado.

## Transacciones de Base de Datos
Toda operación que modifique más de una tabla debe usar `DB::transaction()`.

```php
// ✅ Siempre así para operaciones compuestas
return DB::transaction(function () use ($data) {
    $sale = Sale::create([...]);
    SaleItem::create([...]);
    $inventoryService->removeStock(...);
    return $sale;
});
```

## Controladores HTTP
Solo `CashRegisterController` existe actualmente. Solo maneja request → PDF response.
No agregar controladores para funcionalidades que Filament puede manejar.

## Estructura de Directorios — Qué Va Dónde
```
app/Services/          ← Lógica de negocio (cálculos, reglas, transacciones)
app/Models/            ← Eloquent + relaciones + casts + scopes
app/Filament/          ← UI del panel (forms, tables, widgets, pages)
app/Http/Controllers/  ← Solo respuestas HTTP que no son del panel (PDFs, API)
app/Policies/          ← Autorización por modelo
resources/views/pdf/   ← Templates Blade para PDFs (no lógica aquí)
database/migrations/   ← Solo estructura de BD, sin datos
database/seeders/      ← Datos iniciales y de prueba
```
