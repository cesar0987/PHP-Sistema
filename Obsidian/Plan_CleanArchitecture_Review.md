# Plan: Revisión de Clean Architecture

**Fecha:** 2026-03-26
**Referencia:** "Clean Architecture: A Craftsman's Guide to Software Structure and Design" — Robert C. Martin
**Estado:** Pendiente

---

## Objetivo

Auditar el proyecto Terracota contra los principios de Clean Architecture para identificar deudas técnicas estructurales y establecer un roadmap de mejoras priorizadas.

---

## Contexto del proyecto actual

```
Capa actual (informal)
├── Filament Resources     ← UI + lógica de formularios
├── Services               ← Lógica de negocio (bien intencionada)
├── Models (Eloquent)      ← Entidades + ORM + relaciones
├── Migrations             ← Esquema de BD
└── Controllers            ← Solo CashRegisterController (PDF)
```

---

## Mapa de principios a revisar

### 1. La Regla de Dependencia (Cap. 22)

> "Las dependencias del código fuente deben apuntar solo hacia adentro, hacia las políticas de nivel superior."

**Capas en Clean Architecture:**
```
Entities → Use Cases → Interface Adapters → Frameworks & Drivers
```

**Preguntas a responder:**

| # | Pregunta | Archivo a revisar |
|---|---------|-------------------|
| 1.1 | ¿Los Services conocen clases de Filament? (violación hacia afuera) | `app/Services/*.php` |
| 1.2 | ¿Los Models tienen `use Filament\...` o `use Livewire\...`? | `app/Models/*.php` |
| 1.3 | ¿Los Services dependen de `Request` de Laravel? | `SaleService`, `PurchaseService` |
| 1.4 | ¿Los Resources llaman lógica de negocio directamente sin pasar por Service? | `app/Filament/Resources/*.php` |
| 1.5 | ¿Los Services dependen de `Eloquent` directamente? ¿Hay repositorios? | `app/Services/*.php` |

---

### 2. Entidades — Enterprise Business Rules (Cap. 20)

> "Una entidad encapsula las reglas de negocio críticas de la empresa."

**En este proyecto:** Los modelos Eloquent hacen el rol de entidades, pero mezclan persistencia con reglas.

**Preguntas a responder:**

| # | Pregunta | Archivo a revisar |
|---|---------|-------------------|
| 2.1 | ¿Los modelos tienen métodos con lógica de negocio además de relaciones? | `Sale.php`, `Product.php`, `Stock.php` |
| 2.2 | ¿Los modelos conocen detalles de UI (casting, labels, enum descriptions)? | Todos los modelos |
| 2.3 | ¿Los enums con `getLabel()` mezclan presentación con dominio? | Buscar `enum` en `app/` |
| 2.4 | ¿`LogsActivity`, `SoftDeletes` y `BranchScope` son concerns separados o acoplados? | Traits en modelos |
| 2.5 | ¿Hay lógica de validación de negocio dentro de los modelos? | Buscar `boot()`, observers |

---

### 3. Casos de Uso — Application Business Rules (Cap. 21)

> "Un caso de uso describe la secuencia de interacciones entre un actor y el sistema."

**En este proyecto:** Los Services son los candidatos a ser Use Cases.

**Preguntas a responder:**

| # | Pregunta | Archivo a revisar |
|---|---------|-------------------|
| 3.1 | ¿Cada Service tiene responsabilidad única o mezcla múltiples casos de uso? | `SaleService.php` |
| 3.2 | ¿Los métodos de Service reciben datos primitivos/DTOs o modelos de Eloquent? | Firmas de métodos |
| 3.3 | ¿`InventoryService` es el único punto de modificación de stock? (ya está en CLAUDE.md) | `InventoryService.php` |
| 3.4 | ¿Los Services lanzan excepciones de dominio propias o usan excepciones de Laravel? | Buscar `throw new` en Services |
| 3.5 | ¿`SaleService::createSale()` es un Use Case o un controller gordo disfrazado? | `SaleService.php` |
| 3.6 | ¿Los Services son testeables sin HTTP, sin Filament, sin sesión? | `tests/Unit/` |

---

### 4. Adaptadores de Interfaz (Cap. 22)

> "Convierten datos del formato más conveniente para los casos de uso y entidades, al formato más conveniente para agentes externos."

**En este proyecto:** Filament Resources, Controllers y ReceiptService hacen este rol.

**Preguntas a responder:**

| # | Pregunta | Archivo a revisar |
|---|---------|-------------------|
| 4.1 | ¿Los Resources Filament hacen conversión de datos o también ejecutan lógica? | `SaleResource.php`, `PurchaseResource.php` |
| 4.2 | ¿`ReceiptService` mezcla generación de PDF con lógica de negocio? | `ReceiptService.php` |
| 4.3 | ¿Los Controllers (HTTP) son delgados — solo delegan al Service? | `CashRegisterController.php` |
| 4.4 | ¿Las Policies de autorización están como capa separada o mezcladas? | `app/Policies/` |
| 4.5 | ¿Los Widgets del dashboard tienen lógica de negocio o solo presentación? | `app/Filament/Widgets/` |

---

### 5. Principios SOLID (Cap. 7–11)

#### SRP — Single Responsibility Principle
> "Un módulo debe ser responsable de un, y solo un, actor."

| # | Pregunta | Archivo |
|---|---------|---------|
| 5.1 | ¿`SaleService` tiene más de una razón para cambiar? (creación, pagos, stock, crédito) | `SaleService.php` |
| 5.2 | ¿`InventoryService` maneja tanto ajustes como conteos como movimientos? | `InventoryService.php` |
| 5.3 | ¿Los Resources mezclan definición de tabla, formulario y acciones en una clase? | (Filament lo fuerza — documentar trade-off) |

#### OCP — Open/Closed Principle
> "Un artefacto de software debe estar abierto para extensión pero cerrado para modificación."

| # | Pregunta | Archivo |
|---|---------|---------|
| 5.4 | ¿Agregar un nuevo método de pago requiere modificar `SaleService`? | `SaleService.php` |
| 5.5 | ¿El cálculo de impuestos/precios está hardcodeado o es extensible? | Buscar cálculos en Services |
| 5.6 | ¿Los tipos de ajuste de inventario están en enum o en condicionales `if/switch`? | `InventoryService.php` |

#### DIP — Dependency Inversion Principle
> "Los módulos de alto nivel no deben depender de módulos de bajo nivel. Ambos deben depender de abstracciones."

| # | Pregunta | Archivo |
|---|---------|---------|
| 5.7 | ¿Los Services dependen de implementaciones concretas de Eloquent o de interfaces? | `app/Services/*.php` |
| 5.8 | ¿Hay interfaces/contratos en `app/Contracts/` o `app/Interfaces/`? | `app/` |
| 5.9 | ¿`SifenXmlService` depende de implementación concreta o de una interfaz `FiscalDocument`? | `SifenXmlService.php` |

---

### 6. Límites de componentes y cohesión (Cap. 13–14)

> "REP, CCP, CRP — las clases que cambian juntas, se agrupan juntas."

**Preguntas a responder:**

| # | Pregunta | Qué buscar |
|---|---------|-----------|
| 6.1 | ¿Los 3 servicios SIFEN (`Cdc`, `Qr`, `Xml`) deberían ser un componente `Sifen/`? | Cohesión funcional |
| 6.2 | ¿Los modelos de bodega (`Warehouse`, `Stock`, `StockMovement`) forman un bounded context? | Agrupación de modelos |
| 6.3 | ¿`ProductImportService` y `StockImportService` siguen la misma interfaz? | Consistencia |
| 6.4 | ¿Hay clases utilitarias globales que no pertenecen a ningún componente claro? | `app/Helpers/` si existe |

---

### 7. Límites y el Humble Object Pattern (Cap. 23)

> "Separar comportamiento difícil de testear en objetos humildes y comportamiento testeable en objetos separados."

**Preguntas a responder:**

| # | Pregunta | Archivo |
|---|---------|---------|
| 7.1 | ¿Las vistas Blade/PDF tienen lógica que debería estar en un Presenter? | `resources/views/pdf/` |
| 7.2 | ¿`ReceiptService` genera datos Y renderiza el PDF? (dos responsabilidades) | `ReceiptService.php` |
| 7.3 | ¿Los Widgets de Filament son humildes (solo presentan) o calculan métricas? | `app/Filament/Widgets/` |
| 7.4 | ¿Qué parte del código es imposible testear sin Filament levantado? | Tests existentes |

---

### 8. Independencia del Framework (Cap. 15, 34)

> "La arquitectura no debe depender de la existencia de alguna librería. Los frameworks son detalles."

**Preguntas a responder:**

| # | Pregunta | Qué buscar |
|---|---------|-----------|
| 8.1 | ¿Los Services pueden ejecutarse sin `artisan serve` ni Filament? | Tests unitarios |
| 8.2 | ¿Si cambiamos Filament por Livewire puro, ¿cuánto código de negocio cambia? | Acoplamiento |
| 8.3 | ¿Si migramos de SQLite a PostgreSQL, ¿algo en los Services cambia? | Dependencia de BD |
| 8.4 | ¿El uso de `app()` y facades en Services dificulta el testing? | Buscar `app(`, `Auth::`, `DB::` en Services |

---

## Metodología de revisión

### Fase A — Análisis estático del código (Sin ejecutar)
1. Leer cada Service y mapear sus dependencias externas
2. Revisar cada Model y catalogar métodos que no sean relaciones/casts
3. Buscar violaciones de la Regla de Dependencia con grep

### Fase B — Análisis de tests existentes
1. Revisar `tests/Unit/` — ¿testean lógica de negocio pura?
2. Revisar `tests/Feature/` — ¿qué tan pesados son los fixtures?
3. Identificar código sin cobertura de tests

### Fase C — Scoring por capa
Puntuar cada capa (1=crítico, 2=mejorable, 3=aceptable, 4=bien):

| Capa | Score | Hallazgos principales |
|------|-------|----------------------|
| Entidades (Models) | ? | |
| Casos de Uso (Services) | ? | |
| Adaptadores (Resources/Controllers) | ? | |
| Frameworks (Filament/Laravel) | ? | |

### Fase D — Priorización de mejoras
Clasificar hallazgos en:
- 🔴 **Crítico** — Viola la Regla de Dependencia o impide testear lógica de negocio
- 🟡 **Mejorable** — Viola un principio SOLID pero no impide el funcionamiento
- 🟢 **Trade-off aceptado** — Filament impone el patrón, documentar la decisión

---

## Checklist de ejecución

- [x] A1: Mapear dependencias de cada Service (qué importa, qué llama)
- [x] A2: Catalogar métodos de negocio en Models
- [x] A3: Grep de `use Filament` en Services y Models
- [x] A4: Grep de `DB::`, `Auth::`, `request()` en Services
- [x] A5: Revisar si existen interfaces/contratos en `app/`
- [x] B1: Revisar `tests/Unit/` — ¿son tests unitarios reales?
- [x] B2: Identificar código sin tests
- [x] C1: Completar tabla de scoring por capa
- [x] D1: Crear lista de mejoras priorizadas en nuevo plan
- [x] D2: Documentar trade-offs de Filament como decisiones de arquitectura

---

## Output esperado

Al terminar la revisión, crear:
1. `Obsidian/Reporte_CleanArch_[fecha].md` con hallazgos detallados y scoring ✅ → [Reporte_CleanArch_2026-03-26.md](Reporte_CleanArch_2026-03-26.md)
2. `Obsidian/Plan_Refactor_[area].md` para cada área crítica identificada
3. Actualizar este checklist con los resultados ✅

---

## Referencias del libro aplicadas al stack PHP/Laravel

| Concepto del libro | Equivalente en este proyecto |
|-------------------|------------------------------|
| Entities | Models Eloquent (sin lógica de persistencia) |
| Use Cases / Interactors | `app/Services/` |
| Interface Adapters | `app/Filament/Resources/`, `app/Http/Controllers/` |
| Frameworks & Drivers | Laravel, Filament, SQLite/PostgreSQL |
| Humble Object | Vistas Blade, Widgets |
| Plugin/Boundary | Módulo SIFEN (`SifenCdcService`, `SifenQrService`, `SifenXmlService`) |
| Contracts/Interfaces | Ausentes actualmente — candidatos a crear |
| Repository Pattern | Ausente — Eloquent hace este rol directamente |
