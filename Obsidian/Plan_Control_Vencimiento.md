# Plan: Control de Vencimiento y Lotes (Optimizado para Ferretería)

Este plan aborda la necesidad de supervisar el vencimiento de los productos (como pinturas, cementos, adhesivos) y clarificar el flujo de reabastecimiento (restock) e inventario inicial utilizando Lotes de Stock (Batch Management) con descuentos FIFO.

## 1. Análisis del Problema en Ferretería

En una ferretería típica, aproximadamente el **80.90% de los productos no vencen** (clavos, herramientas, tuberías PVC). Exigir datos de vencimiento a cada producto o compra es contraproducente.
Por ello, implementamos una validación condicional `has_expiry` en el producto.

**El Problema sin lotes**: Si solo guardamos `expiry_date` en la compra, al momento de revisar vencimientos, no sabemos si esos productos *ya se vendieron* o siguen en el estante. 
**La Solución con Lotes**: Mantener tablas de Lotes de Stock (`stock_batches`) donde descenderemos saldo específicamente de los lotes más viejos (FIFO) a medida que vendemos. Esto asegura alertas de vencimiento 100% reales.

## 2. Hoja de Ruta (Roadmap)

1. [x] Crear el plan maestro y flujo.
2. [x] Crear Migración para la tabla `stock_batches` y agregar columnas `has_expiry` en `products` y `expiry_date` en ingresos.
3. [x] Crear modelo `StockBatch` y agregarlo en las relaciones.
4. [x] Refactorizar la lógica del servicio `InventoryService` (Lógica FIFO de entrada/salida/ajuste/transferencia).
5. [x] Actualizar UI de Filament (Toggle `has_expiry` y campos condicionales de fecha).
6. [x] Crear interfaz de gestión de "Lotes de Stock" (`StockBatchResource`).

## 3. Lógica Técnica Aplicada (FIFO)

La descarga de stock se realiza de forma jerárquica en `InventoryService::removeStock()`:
1. Se buscan lotes con `quantity > 0` para la variante y almacén específico.
2. Se ordenan por `expiry_date ASC` (fechas más próximas primero).
3. Los lotes sin fecha (`null`) se procesan al final para priorizar los productos perecederos conocidos.
4. Se utiliza `lockForUpdate()` para prevenir colisiones de stock durante transacciones concurrentes.

---
**Documento generado por Antigravity AI**
*Fecha: 2026-03-28*
