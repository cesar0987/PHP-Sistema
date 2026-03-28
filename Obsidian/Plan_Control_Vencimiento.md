# Plan: Control de Vencimiento y Lotes (Optimizado para Ferretería)

Este plan aborda la necesidad de supervisar el vencimiento de los productos (como pinturas, cementos, adhesivos) y clarificar el flujo de reabastecimiento (restock) e inventario inicial utilizando Lotes de Stock (Batch Management) con descuentos FIFO.

## 1. Análisis del Problema en Ferretería

En una ferretería típica, aproximadamente el **80.90% de los productos no vencen** (clavos, herramientas, tuberías PVC). Exigir datos de vencimiento a cada producto o compra es contraproducente.
Por ello, implementamos una validación condicional `has_expiry` en el producto.

**El Problema sin lotes**: Si solo guardamos `expiry_date` en la compra, al momento de revisar vencimientos, no sabemos si esos productos *ya se vendieron* o siguen en el estante. 
**La Solución con Lotes**: Mantener tablas de Lotes de Stock (`stock_batches`) donde descenderemos saldo específicamente de los lotes más viejos (FIFO) a medida que vendemos. Esto asegura alertas de vencimiento 100% reales.

## 2. Hoja de Ruta (Roadmap)

1. [x] Crear el plan maestro y flujo.
2. [ ] Crear Migración para la tabla `stock_batches` y agregar columnas `has_expiry` en `products` y `expiry_date` en ingresos (`purchase_items`, `inventory_adjustment_items`).
3. [ ] Crear modelo `StockBatch` y agregarlo en las relaciones.
4. [ ] Refactorizar la lógica del servicio `InventoryService`:
    - `addStock()` / `addPurchaseStock()`: Agrupar stock en lotes con `expiry_date`.
    - `removeStock()`: Aplicar descuento jerárquico FIFO a los lotes.
5. [ ] Actualizar UI de Filament:
    - Productos: Toggle de `has_expiry`.
    - Compras y Ajustes: Mostrar selector de fecha condicionado a `$get`.
6. [ ] Crear interfaz/widget "Próximos Vencimientos" visualizando sobre `stock_batches`.

---
**Documento generado por Antigravity AI**
*Fecha: 2026-03-28*
