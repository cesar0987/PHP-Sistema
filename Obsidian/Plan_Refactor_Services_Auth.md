---
title: "Plan Refactor - Desacoplar Control de Acceso (Auth) de la Capa de Servicios"
status: "Completado"
date: "2026-03-26"
---

# Refactor: Eliminación de `auth()->id()` en los Services

Este documento detalla el plan ejecutado para resolver la **Violación C1 de Arquitectura Limpia**, que consistía en el uso directo de la función helper del framework `auth()->id()` dentro de los casos de uso (`Services`).

## 1. El Problema (Violación de la Regla de Dependencia)

En Clean Architecture, la capa de Aplicación (Casos de Uso o Services) **no debe depender** de los detalles de infraestructura o del framework. El mecanismo de sesión HTTP (`auth()`) pertenece a la capa de **Adaptadores** (Controladores, Middlewares, Resources).

**Impacto negativo del acoplamiento:**
- **Testabilidad:** Obligaba a simular una sesión activa (`actingAs()`) incluso para tests no relacionados con HTTP.
- **Reusabilidad:** Impedía usar los servicios desde comandos de CLI o Jobs en segundo plano donde no existe una sesión web.
- **Opacidad:** Ocultaba la dependencia del usuario responsable de las operaciones, haciéndolas parecer mágicas.

## 2. Los Cambios Implementados

### A. Modificación de los Servicios
Eliminamos el fallback a `auth()->id()` en los servicios principales y obligamos a que el parámetro `user_id` se pase de forma explícita.

1. **`SaleService.php`**: 
   - `createSale()`: Se quitó el fallback y ahora lanza una `\InvalidArgumentException` si falta `user_id`.
   - Se actualizó internamente para que las llamadas subsiguientes pasen explícitamente este ID.
2. **`PurchaseService.php`**: 
   - `createPurchase()`: Igual que en Venta, se retiró el fallback a la sesión.
3. **`InventoryService.php`**: 
   - `resolveUserId()`: Se eliminó la llamada a `auth()->id()`. Ahora, si los métodos como `addStock` o `removeStock` no reciben un usuario en los `$data`, lanzan una `\RuntimeException`.

### B. Inyección desde las Capas Externas (Resources)
Una vez que los servicios exigieron explícitamente el usuario, la responsabilidad de proveerlo se empujó (correctamente) a la capa más externa, que es consciente de la sesión web:

- En los controladores y mutadores de la UI de Filament (`SaleResource`, `PurchaseResource`), el ID del usuario loggeado se recoge a través del request o del mismo form (que ya traía un `default(auth()->id())`) y se le inyecta al DTO/Array que se le pasa al Servicio.
- Así, **el Servicio simplemente procesa datos planos**, ignorando de dónde salieron.

## 3. Resultado Arquitectónico
La arquitectura ascendió a un nivel más maduro, donde el Dominio ya no "tira" de los datos del entorno externo, sino que los recibe como parámetros inyectados de manera limpia y testeable.

## 4. Próximos Pasos (relacionados)
Dado que los fallbacks mágicos fueron retirados, los tests unitarios vinculados a estas entidades tuvieron que ajustarse. En proyectos grandes, es fundamental asegurar que todos los comandos CLI, cron jobs y callbacks envíen un `user_id` simulado o de sistema si necesitan realizar de forma desatendida procesos que requieran trazabilidad de inventario.
