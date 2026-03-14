# Planificación: Mejoras en Módulo de Inventario

## Nombre de la Característica
Corrección de "Ajustes de Inventario", Documentación de "Stock" e Implementación de lógicas faltantes de Inventario.

## Descripción
El usuario ha reportado dos inconvenientes en la gestión de inventario del sistema POS:
1. Al crear un **Ajuste de Inventario** (`InventoryAdjustmentResource`), solo se guarda el registro principal (cabecera) y no permite seleccionar qué productos se van a ajustar ni las cantidades.
2. La vista de **Stock** (`StockResource`) suele estar vacía al inicio y no es muy evidente cómo funciona o se alimenta de datos.
3. Se solicita elaborar una documentación que clarifique **cómo funciona exactamente** el módulo de inventario para el usuario final.

## Enfoque Técnico

**Para Ajustes de Inventario:**
1. Modificaremos el `InventoryAdjustmentResource` de Filament para incluir un campo `Repeater` (o un RelationManager), vinculado a la relación `items` (hacia `InventoryAdjustmentItem`).
2. En este repeater se podrá seleccionar el `product_variant_id` y especificar la `quantity` (que será el ajuste, pudiendo ser positivo para entrada o negativo para salida/merma).
3. Haremos que al crear/aprobar el Ajuste, el `InventoryService` dispare automáticamente la función de ajustar el inventario real y cree los correspondientes movimientos de stock (`StockMovement`).

**Para la Vista de Stock:**
1. El `StockResource` actualmente solo muestra registros en la tabla `stocks`. Si un producto nunca tuvo una entrada o salida, no existe en esa tabla.
2. Modificaremos el `StockResource` para que sea **Estrictamente de Solo Lectura**. El stock *jamás* debe editarse manualmente haciendo doble clic. Siempre debe usarse un Ajuste de Inventario, una Venta o una Compra.

**Para la Documentación:**
- Crearé un documento en `Obsidian/Documentacion_Inventario.md` con guías claras para que los operadores del negocio entiendan que el stock solo se afecta a través de:
  - Compras (suman)
  - Ventas (restan)
  - Ajustes de Inventario (suman/restan mermas, diferencias, etc.)

## Lista de Tareas (Tasks)
- [ ] Refactorizar `InventoryAdjustmentResource.php` agregando el Repeater para `items`.
- [ ] Hacer que el Repeater actualice dinámicamente el listado de productos disponibles.
- [ ] Intervenir en el evento de guardado para que las cantidades se reflejen en el `Stock` real si el estado cambia a `approved`.
- [ ] Refactorizar `StockResource.php` desactivando la edición manual (solo lectura para visualización y filtro por almacén).
- [ ] Escribir la documentación `Documentacion_Inventario.md`.
