# Referencia: Reglas del Asistente IA (.cursor/rules)

Este documento resume las reglas configuradas en `.cursor/rules/` para orientar el comportamiento del asistente IA en este proyecto.

## Archivos de Reglas

| Archivo | Alcance | Descripción |
|---------|---------|-------------|
| `global.md` | Siempre activo | Idioma, restricciones, calidad de código |
| `architecture.md` | Siempre activo | Service layer, BranchScope, transacciones |
| `planning.md` | Siempre activo | Flujo de planificación en Obsidian |
| `git.md` | Siempre activo | Conventional Commits, scopes del proyecto |
| `laravel.md` | Siempre activo | Convenciones Laravel 12 + Filament v3 |
| `sifen.md` | Bajo demanda | SIFEN v150, CDC, QR, XML, firma digital |
| `testing.md` | Bajo demanda | PHPUnit, patrones de tests, cobertura mínima |

## Reglas Más Importantes

### ❌ NUNCA
- Escribir lógica de negocio fuera de `app/Services/`
- Modificar stock directamente (sin `InventoryService`)
- Hacer hard delete de modelos con `SoftDeletes`
- Hacer commits en español o sin `<type>(<scope>):`
- Empezar a codear sin crear el plan en `Obsidian/` (para features significativas)
- Introducir dependencias de Composer sin justificación

### ✅ SIEMPRE
- Usar `DB::transaction()` para operaciones multi-tabla
- Aplicar `BranchScope` en modelos de sucursal
- Responder al usuario en español, código en inglés
- Seguir la estructura de servicios existente
- Leer el archivo antes de editarlo
- Correr `composer test` antes de hacer commit (58 tests deben pasar)

## Tests — Estado Actual

| Suite | Tipo | Tests |
|-------|------|-------|
| `InventoryServiceTest` | Unit | 10 |
| `SaleServiceTest` | Unit | 4 |
| `PurchaseServiceTest` | Unit | 4 |
| `LocationServiceTest` | Unit | 6 |
| `CreditServiceTest` | Unit | 2 |
| `SifenCdcServiceTest` | Unit | 15 |
| `ExampleTest` (Unit) | Unit | 1 |
| `SaleFlowTest` | Feature | 5 |
| `PurchaseFlowTest` | Feature | 4 |
| `CashRegisterFlowTest` | Feature | 4 |
| `AuthFlowTest` | Feature | 2 |
| `ExampleTest` (Feature) | Feature | 1 |
| **Total** | | **58 tests / 144 assertions** |

## Scopes de Commits

```
feat, fix, docs, refactor, perf, test, chore
(sale, inventory, purchase, credit, location, sifen, receipt,
 auth, product, customer, cash, branch, company, ui, migration, config, obsidian)
```

## Cuándo Crear Plan en Obsidian

| Situación | ¿Plan requerido? |
|-----------|-----------------|
| Nueva funcionalidad | ✅ Sí |
| Cambio de modelo o migración significativa | ✅ Sí |
| Integración con sistema externo | ✅ Sí |
| Corrección de bug simple (1 archivo) | ❌ No |
| Cambio de label/texto UI | ❌ No |

## Convención de Nombres en Obsidian

- `Plan_[Feature].md` → planes de implementación
- `Manual_[Rol].md` → manuales de usuario
- `Arquitectura_[Componente].md` → documentación técnica
- `Referencia_[Tema].md` → referencias rápidas
- `Errores_[Tema].md` → errores conocidos y soluciones
