# Diccionario de Datos — POS Ferretería

> **Fecha:** 14/03/2026  
> **Metodología:** Kendall & Kendall — Capítulo 7  
> **Referencia:** [Diagrama ER](Diagrama_ER.md) | [Plan Kendall & Kendall — Fase 4](Plan_Kendall_Kendall.md)

---

## 1. Tabla: `companies`

**Descripción:** Empresa raíz del sistema. Agrupa sucursales.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `name` | varchar | NOT NULL | Nombre legal de la empresa |
| `ruc` | varchar | NOT NULL | RUC de la empresa |
| `created_at` | datetime | auto | Fecha de creación |
| `updated_at` | datetime | auto | Fecha de última modificación |

---

## 2. Tabla: `branches`

**Descripción:** Sucursales de la empresa. Cada sucursal tiene su propia caja, almacenes y personal.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `company_id` | bigint | FK → companies | Empresa a la que pertenece |
| `name` | varchar | NOT NULL | Nombre de la sucursal |
| `created_at` | datetime | auto | Fecha de creación |
| `updated_at` | datetime | auto | Fecha de última modificación |

---

## 3. Tabla: `users`

**Descripción:** Usuarios del sistema (cajeros, supervisores, administradores).

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `name` | varchar | NOT NULL | Nombre completo |
| `email` | varchar | NOT NULL, UNIQUE | Correo de acceso |
| `password` | varchar | NOT NULL | Hash bcrypt |
| `email_verified_at` | datetime | nullable | Fecha de verificación |
| `remember_token` | varchar | nullable | Token sesión prolongada |
| `created_at` | datetime | auto | Fecha de creación |
| `updated_at` | datetime | auto | Fecha de modificación |

**Reglas de negocio:**
- Los roles se asignan vía `spatie/laravel-permission`: `admin`, `supervisor`, `cajero`
- Solo usuarios con rol `admin` pueden crear/eliminar otros usuarios

---

## 4. Tabla: `categories`

**Descripción:** Categorías de productos (ej: Herramientas, Electricidad, Plomería).

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `name` | varchar | NOT NULL | Nombre de la categoría |
| `active` | boolean | NOT NULL, default: true | Si está activa para usar |
| `deleted_at` | datetime | nullable | SoftDelete |
| `created_at` | datetime | auto | Fecha de creación |
| `updated_at` | datetime | auto | Fecha de modificación |

---

## 5. Tabla: `products`

**Descripción:** Producto del catálogo. Cada producto puede tener múltiples variantes.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `category_id` | bigint | FK → categories | Categoría del producto |
| `name` | varchar | NOT NULL | Nombre del producto |
| `description` | text | nullable | Descripción detallada |
| `cost_price` | decimal(12,2) | NOT NULL | Precio de costo |
| `sale_price` | decimal(12,2) | NOT NULL | Precio de venta |
| `min_stock` | integer | NOT NULL, default: 0 | Stock mínimo para alerta |
| `tax_percentage` | decimal(5,2) | NOT NULL, default: 10 | Porcentaje de IVA |
| `active` | boolean | NOT NULL, default: true | Si está activo en catálogo |
| `deleted_at` | datetime | nullable | SoftDelete |

**Reglas de negocio:**
- `sale_price` ≥ `cost_price` (advertencia si se viola)
- `tax_percentage`: valores comunes 0%, 5%, 10%
- Solo `admin` puede eliminar productos

---

## 6. Tabla: `product_variants`

**Descripción:** Variantes de un producto (talla, color, presentación). Mínima 1 variante "Default".

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `product_id` | bigint | FK → products | Producto al que pertenece |
| `name` | varchar | NOT NULL | Nombre de la variante (ej: "Rojo L") |
| `sku` | varchar | UNIQUE, nullable | Código de referencia interno |
| `barcode` | varchar | UNIQUE, nullable | Código de barras EAN/UPC |
| `deleted_at` | datetime | nullable | SoftDelete |

---

## 7. Tabla: `warehouses`

**Descripción:** Almacenes físicos de una sucursal.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `branch_id` | bigint | FK → branches | Sucursal propietaria |
| `name` | varchar | NOT NULL | Nombre del almacén |
| `description` | text | nullable | Descripción |
| `is_default` | boolean | NOT NULL, default: false | Almacén predeterminado de la sucursal |
| `active` | boolean | NOT NULL, default: true | Si está activo |

**Reglas:** Solo puede haber 1 almacén con `is_default = true` por sucursal.

---

## 8. Tabla: `stocks`

**Descripción:** Stock actual de una variante en un almacén. Se actualiza con cada venta, compra o ajuste.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `product_variant_id` | bigint | FK → product_variants | Variante del producto |
| `warehouse_id` | bigint | FK → warehouses | Almacén donde está |
| `quantity` | integer | NOT NULL, default: 0 | Cantidad actual en stock |

**Reglas:** `quantity` ≥ 0 siempre. `InventoryService::removeStock` lanza excepción si se intenta dejar en negativo.

---

## 9. Tabla: `stock_movements`

**Descripción:** Historial de todos los movimientos de stock (entradas, salidas, ajustes, transferencias).

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `product_variant_id` | bigint | FK → product_variants | Variante afectada |
| `warehouse_id` | bigint | FK → warehouses | Almacén afectado |
| `user_id` | bigint | FK → users, NOT NULL | Responsable del movimiento |
| `type` | varchar | NOT NULL | Tipo (ver tabla de dominio) |
| `quantity` | integer | NOT NULL | Cantidad movida (positivo) |
| `notes` | text | nullable | Observaciones |
| `reference_type` | varchar | nullable | Modelo de referencia (ej: `App\Models\Sale`) |
| `reference_id` | bigint | nullable | ID del modelo de referencia |

**Dominio `type`:**

| Valor | Descripción |
|---|---|
| `purchase` | Ingreso por compra |
| `sale` | Egreso por venta |
| `adjustment` | Ajuste manual de inventario |
| `transfer_in` | Entrada por transferencia entre almacenes |
| `transfer_out` | Salida por transferencia entre almacenes |
| `return` | Devolución de venta cancelada |

---

## 10. Tabla: `sales`

**Descripción:** Transacciones de venta realizadas en el POS.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `customer_id` | bigint | FK → customers, nullable | Cliente (null = venta anónima) |
| `user_id` | bigint | FK → users | Cajero que realizó la venta |
| `branch_id` | bigint | FK → branches | Sucursal donde se realizó |
| `cash_register_id` | bigint | FK → cash_registers, nullable | Caja usada |
| `subtotal` | decimal(12,2) | NOT NULL | Total antes de IVA y descuentos |
| `discount` | decimal(12,2) | NOT NULL, default: 0 | Descuento global |
| `tax` | decimal(12,2) | NOT NULL, default: 0 | IVA total |
| `total` | decimal(12,2) | NOT NULL | Total final a cobrar |
| `status` | enum | NOT NULL, default: completed | Estado de la venta |
| `payment_method` | enum | NOT NULL, default: contado | Método de pago general |
| `sale_date` | timestamp | NOT NULL | Fecha y hora de la venta |
| `notes` | text | nullable | Observaciones |
| `deleted_at` | datetime | nullable | SoftDelete |

**Dominio `status`:**

| Valor | Descripción |
|---|---|
| `pending` | Nota de pedido sin despachar |
| `completed` | Venta completada y cobrada |
| `cancelled` | Venta anulada |
| `returned` | Devolución procesada |

**Dominio `payment_method`:**

| Valor | Descripción |
|---|---|
| `contado` | Pago al contado (efectivo/tarjeta/QR) |
| `credito` | Venta a crédito (cuenta corriente) |

---

## 11. Tabla: `payments`

**Descripción:** Pagos individuales de una venta. Una venta puede tener múltiples pagos (ej: parte en efectivo, parte en tarjeta).

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `sale_id` | bigint | FK → sales | Venta asociada |
| `method` | enum | NOT NULL | Medio de pago |
| `amount` | decimal(12,2) | NOT NULL | Monto recibido |
| `reference` | varchar | nullable | Referencia (número de tarjeta, etc.) |
| `payment_date` | datetime | NOT NULL | Fecha y hora del pago |

**Dominio `method`:** `cash` · `card` · `transfer` · `qr`

> [!CAUTION]
> Los valores del enum `payments.method` son **en inglés**: `cash`, `card`, `transfer`, `qr`. No usar `efectivo`, `tarjeta`, etc.

---

## 12. Tabla: `purchases`

**Descripción:** Órdenes de compra a proveedores.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `supplier_id` | bigint | FK → suppliers | Proveedor |
| `user_id` | bigint | FK → users | Usuario que creó la compra |
| `branch_id` | bigint | FK → branches | Sucursal destino |
| `warehouse_id` | bigint | FK → warehouses | Almacén destino |
| `total` | decimal(12,2) | NOT NULL | Importe total de la compra |
| `status` | enum | NOT NULL | Estado de la compra |
| `notes` | text | nullable | Observaciones |
| `deleted_at` | datetime | nullable | SoftDelete |

**Dominio `status`:** `pending` · `received` · `cancelled`

---

## 13. Tabla: `customers`

**Descripción:** Clientes del negocio. Pueden realizar compras a crédito.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `name` | varchar | NOT NULL | Nombre o razón social |
| `document` | varchar | nullable | CI / RUC |
| `phone` | varchar | nullable | Teléfono |
| `email` | varchar | nullable | Correo electrónico |
| `address` | varchar | nullable | Dirección |
| `active` | boolean | NOT NULL, default: true | Estado activo |
| `is_credit_enabled` | boolean | NOT NULL, default: false | ¿Puede comprar a crédito? |
| `credit_limit` | decimal(12,2) | nullable | Límite de crédito |
| `current_balance` | decimal(12,2) | NOT NULL, default: 0 | Saldo de deuda actual |
| `deleted_at` | datetime | nullable | SoftDelete |

---

## 14. Tabla: `suppliers`

**Descripción:** Proveedores de mercadería.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `name` | varchar | NOT NULL | Nombre / Razón social |
| `ruc` | varchar | nullable | RUC |
| `phone` | varchar | nullable | Teléfono |
| `email` | varchar | nullable | Correo |
| `address` | varchar | nullable | Dirección |
| `deleted_at` | datetime | nullable | SoftDelete |

---

## 15. Tabla: `cash_registers`

**Descripción:** Cajas operativas. Cada apertura genera un registro de caja.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `branch_id` | bigint | FK → branches | Sucursal |
| `user_id` | bigint | FK → users | Cajero responsable |
| `name` | varchar | NOT NULL | Nombre de la caja |
| `opening_amount` | decimal(12,2) | NOT NULL | Efectivo al abrir |
| `closing_amount` | decimal(12,2) | nullable | Efectivo al cerrar |
| `status` | varchar | NOT NULL | Estado (open / closed) |
| `opened_at` | datetime | NOT NULL | Fecha/hora de apertura |
| `closed_at` | datetime | nullable | Fecha/hora de cierre |
| `notes` | text | nullable | Observaciones |
| `deleted_at` | datetime | nullable | SoftDelete |

---

## 16. Tabla: `inventory_adjustments`

**Descripción:** Ajustes manuales de stock por conteo físico, merma o error.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `product_variant_id` | bigint | FK → product_variants | Variante ajustada |
| `warehouse_id` | bigint | FK → warehouses | Almacén |
| `user_id` | bigint | FK → users | Responsable |
| `old_quantity` | integer | NOT NULL | Stock antes del ajuste |
| `new_quantity` | integer | NOT NULL | Stock después del ajuste |
| `reason` | text | NOT NULL | Motivo del ajuste |
| `status` | enum | NOT NULL, default: approved | Estado |

**Dominio `status`:** `pending` · `approved` · `rejected`

---

## 17. Tabla: `receipts`

**Descripción:** Comprobantes generados (tickets y facturas). Cada comprobante tiene un PDF en disco.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK, auto | Identificador único |
| `sale_id` | bigint | FK → sales, nullable | Venta asociada |
| `purchase_id` | bigint | FK → purchases, nullable | Compra asociada |
| `type` | varchar | NOT NULL | Tipo de comprobante |
| `number` | varchar | NOT NULL | Número correlativo (00000001) |
| `file_path` | varchar | nullable | Ruta al PDF en storage |
| `generated_at` | datetime | NOT NULL | Fecha de generación |

**Dominio `type`:** `sale_ticket` · `sale_invoice` · `sale_receipt` · `purchase_ticket` · `purchase_invoice`
