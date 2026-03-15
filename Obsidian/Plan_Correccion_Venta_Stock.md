# Plan de Integración: Ventas, Compras y Stock

Este documento detalla la lógica de integración entre los módulos de Ventas y Compras con el sistema de Inventario (Stock), bajo la arquitectura de Sucursales y Almacenes.

## 1. Arquitectura de Stock
El stock no está vinculado directamente a una **Sucursal**, sino a un **Almacén** (`Warehouse`). Cada Almacén pertenece a una Sucursal.

### Estructura de tablas relacionada:
- `branches`: Sucursales de la empresa.
- `warehouses`: Almacenes físicos (ej: Depósito Central, Salón de Ventas). Tienen un `branch_id`.
- `stocks`: Cantidad de una `product_variant` en un `warehouse_id`.

## 2. Flujo de Compras (Entrada)
Al registrar una compra:
1. Se selecciona el **Almacén** de destino (`warehouse_id`).
2. Cuando el estado de la compra cambia a **"Recibido"**:
   - El sistema invoca a `InventoryService->addPurchaseStock()`.
   - Se incrementa la cantidad en la tabla `stocks` para ese almacén y variante.

## 3. Flujo de Ventas (Salida)
Al registrar una venta:
1. Se selecciona la **Sucursal** (`branch_id`).
2. **Validación de Stock**: Antes de permitir agregar un item, el sistema debe verificar si hay existencias suficientes en sucursal.
   - **Corrección aplicada**: La validación ahora busca el stock sumando todos los almacenes vinculados a la sucursal seleccionada.
   - Consulta técnica: `Stock::whereHas('warehouse', fn($q) => $q->where('branch_id', $branchId))->sum('quantity')`.
3. Al completar la venta:
   - Se descuenta el stock del almacén predeterminado de la sucursal o según la lógica definida en `SaleService`.

## 4. Próximos Pasos de Mejora
- Implementar una selección de Almacén opcional por item en la venta si la sucursal tiene múltiples depósitos.
- Alertas de stock bajo integradas en el tablero de Administración.
