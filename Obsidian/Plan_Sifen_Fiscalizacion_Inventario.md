# Mejoras a Corto Plazo: Fiscalización SIFEN y Tomas Físicas de Inventario

## Description
Este plan abarca la implementación de un sistema de "Tomas Físicas" (Fiscalización de inventario) para agilizar los conteos cíclicos y de cierre en los almacenes. Paralelamente, se adaptará el modelo de `Purchases` (Compras a proveedores) para cumplir con el estándar tributario paraguayo de SIFEN.

## Technical approach
1. **Fiscalización de Inventario**:
   - Crear un modelo de cabecera `InventoryCount` y su detalle `InventoryCountItem`.
   - Modificar las vistas nativas de Filament `InventoryCountResource` para manejar la inicialización del conteo (cargando todas las variantes de producto del `Warehouse` destino en `system_quantity`).
   - El operador digitará `counted_quantity` y el sistema calculará al vuelo `difference`.
   - Al cerrar la toma, un script automatizado inyectará los registros en el módulo existente de `InventoryAdjustment` para saldar las diferencias.

2. **SIFEN en Compras**:
   - Campos anexos en migración y modelo base de `Purchase`:
     - `invoice_number` (string) - Validado en formato 001-001-XXXXXXX.
     - `timbrado` (string/int) - 8 dígitos
     - `cdc` (string) - Código de Control SIFEN 44 dígitos
     - `condition` (enum) - Contado o Crédito
   - Los campos formarán parte obligatoria del alta en `PurchaseResource`.

## Tasks checklist
- [x] Levantar las tablas y modelos de fiscalización de inventario (`InventoryCount` e `InventoryCountItem`).
- [x] Configurar form y table en `InventoryCountResource`.
- [x] Construir la lógica transaccional de cruce (`counted` vs `system`) y generación del ajuste de stock a través de `InventoryService`.
- [x] Generar la migración que añade campos SIFEN a `purchases`.
- [x] Configurar regex, validaciones y formato de visualización del comprobante en Filament para `PurchaseResource`.
