---
description: Strict Git commit conventions — Conventional Commits spec
alwaysApply: true
scope: repository
---

# Git & Commit Conventions

## Formato Obligatorio

```
<type>(<scope>): <subject>

<body>
```

**Ejemplos correctos:**
```
feat(sifen): implement CDC generation with modulo-11 check digit
fix(inventory): prevent negative stock on concurrent sales
refactor(sale): eliminate duplicate item loop in createSale
docs(obsidian): add SIFEN XML field mapping to plan
test(sifen): add unit tests for CDC algorithm
```

**Ejemplos incorrectos (NO hacer):**
```
Fix: corrección de bug en ventas          ← capital, español, sin scope
fix: arreglé el stock                     ← español, sin scope, pasado
Implement SIFEN XML generation            ← sin type, sin scope
feat: varias mejoras                      ← demasiado vago
```

## Reglas del Subject (primera línea)
- **Máximo 50 caracteres**
- **Idioma: inglés**
- **Modo imperativo** (add, fix, update, remove — no added, fixed, updated)
- **Sin punto final**
- **Minúscula** después del scope

## Body (cuerpo del commit — opcional pero recomendado)
- Separado del subject con una línea en blanco
- Explica el **por qué**, no el qué (el qué se ve en el diff)
- Máximo 72 caracteres por línea
- Puede referenciar el plan de Obsidian: `Based on Plan_Sifen_XML.md`

## Tipos Permitidos

| Tipo | Cuándo usar |
|------|-------------|
| `feat` | Nueva funcionalidad visible al usuario o al sistema |
| `fix` | Corrección de un bug |
| `docs` | Solo cambios de documentación (Obsidian, CLAUDE.md, README) |
| `refactor` | Cambio de código sin cambiar comportamiento |
| `perf` | Mejora de rendimiento |
| `test` | Agregar o corregir tests |
| `chore` | Tareas de mantenimiento (deps, config, migrations) |

## Scopes del Proyecto

Usar el módulo afectado como scope:

| Scope | Área |
|-------|------|
| `sale` | Ventas y SaleService |
| `inventory` | Stock e InventoryService |
| `purchase` | Compras y PurchaseService |
| `credit` | Créditos y CreditService |
| `location` | Ubicaciones en almacén |
| `sifen` | SIFEN / facturación electrónica |
| `receipt` | PDFs y ReceiptService |
| `auth` | Autenticación y permisos |
| `product` | Productos y variantes |
| `customer` | Clientes |
| `cash` | Caja registradora |
| `branch` | Sucursales |
| `company` | Empresa / configuración |
| `ui` | Cambios de UI en Filament (forms, tables) |
| `test` | Tests |
| `obsidian` | Documentación en Obsidian/ |
| `config` | Archivos de configuración |
| `migration` | Migraciones de base de datos |

## Flujo de Trabajo Git

1. Crear el plan en `Obsidian/` si corresponde.
2. Implementar en rama feature o directamente en `main` para cambios pequeños.
3. Correr `composer test` antes de hacer commit.
4. Commit con mensaje en formato Conventional Commits.
5. Nunca `git push --force` a `main`.
