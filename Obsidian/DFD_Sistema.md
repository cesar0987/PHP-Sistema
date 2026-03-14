# Diagramas de Flujo de Datos (DFD) — POS Ferretería

> **Fecha:** 14/03/2026  
> **Metodología:** Kendall & Kendall — Capítulos 7-9  
> **Referencia:** [Plan Kendall & Kendall — Fase 4](Plan_Kendall_Kendall.md)

---

## Nivel 0 — Diagrama de Contexto

Muestra el sistema como una caja negra con sus actores externos y los flujos de datos que lo atraviesan.

```mermaid
graph LR
    CAJERO(["👤 Cajero"])
    ADMIN(["👤 Administrador"])
    CLIENTE(["👤 Cliente"])
    PROVEEDOR(["🏭 Proveedor"])
    SISTEMA["🖥️ Sistema POS\nTerracota"]

    CAJERO -->|"Venta, pagos, apertura/cierre caja"| SISTEMA
    SISTEMA -->|"Ticket impreso, estado stock"| CAJERO

    ADMIN -->|"Productos, compras, ajustes, usuarios"| SISTEMA
    SISTEMA -->|"Reportes, dashboards, logs auditoría"| ADMIN

    CLIENTE -->|"Datos cliente, pagos crédito"| SISTEMA
    SISTEMA -->|"Comprobante, estado cuenta"| CLIENTE

    PROVEEDOR -->|"Factura, mercadería"| SISTEMA
    SISTEMA -->|"Orden de compra, recibo"| PROVEEDOR
```

---

## Nivel 1 — Procesos Principales

Descompone el sistema en sus 5 procesos funcionales principales con sus almacenes de datos.

```mermaid
graph TD
    CAJERO(["👤 Cajero"])
    ADMIN(["👤 Admin"])
    CLIENTE(["👤 Cliente"])
    PROVEEDOR(["🏭 Proveedor"])

    P1["P1\nGestión de Ventas"]
    P2["P2\nGestión de Compras"]
    P3["P3\nControl de Inventario"]
    P4["P4\nGestión de Caja"]
    P5["P5\nAdministración"]

    DS1[("💾 sales\nsale_items\npayments")]
    DS2[("💾 purchases\npurchase_items")]
    DS3[("💾 stocks\nstock_movements\nproduct_variants")]
    DS4[("💾 cash_registers\ncash_movements")]
    DS5[("💾 users\nbranches\ncategories\nproducts")]

    CAJERO -->|"Datos venta"| P1
    P1 -->|"Ticket/receipt"| CAJERO
    P1 -->|"Graba venta"| DS1
    P1 -->|"Descuenta stock"| DS3
    P1 -->|"Registra pago"| DS4
    CLIENTE -->|"Pago crédito"| P1

    ADMIN -->|"Orden compra"| P2
    PROVEEDOR -->|"Mercadería"| P2
    P2 -->|"Graba compra"| DS2
    P2 -->|"Aumenta stock"| DS3

    ADMIN -->|"Ajuste manual"| P3
    P3 -->|"Lee/escribe stock"| DS3
    P3 -->|"Notifica mínimo"| ADMIN

    CAJERO -->|"Apertura/cierre"| P4
    P4 -->|"Graba movimientos"| DS4

    ADMIN -->|"Config sistema"| P5
    P5 -->|"Gestiona datos maestros"| DS5
    DS5 -->|"Provee catálogos"| P1
    DS5 -->|"Provee catálogos"| P2
```

---

## Nivel 2 — Detalle P1: Flujo de Venta

Descompone el proceso P1 (Gestión de Ventas) en sus subprocesos detallados.

```mermaid
flowchart TD
    START(["🛒 Inicio venta"])
    P1_1["1.1 Buscar Producto\n(código barras, nombre, SKU)"]
    P1_2["1.2 Verificar Stock\n(InventoryService::checkMinimum)"]
    P1_3["1.3 Agregar al carrito\n(línea de venta)"]
    P1_4["1.4 Calcular Total\n(SaleService::calculateTotal)\nsubtotal + IVA - descuento"]
    P1_5["1.5 Registrar Pago\n(efectivo / tarjeta / QR / transferencia)"]
    P1_6["1.6 Crear Venta\n(SaleService::createSale)"]
    P1_7["1.7 Descontar Stock\n(InventoryService::removeStock)"]
    P1_8["1.8 Generar Comprobante\n(ReceiptService::generateReceipt)"]
    P1_9["1.9 Imprimir Ticket\n(PDF 80mm / A4)"]
    END_OK(["✅ Venta completada"])
    END_CANCEL(["❌ Cancelar"])

    STOCK_ERR["⚠️ Error: Stock insuficiente"]
    PAY_ERR["⚠️ Error: Pago insuficiente"]

    DS_PROD[("💾 product_variants\nproducts")]
    DS_STOCK[("💾 stocks")]
    DS_SALES[("💾 sales\nsale_items")]
    DS_PAY[("💾 payments")]
    DS_RCPT[("💾 receipts")]

    START --> P1_1
    P1_1 -->|"Consulta"| DS_PROD
    DS_PROD -->|"Datos producto"| P1_1
    P1_1 --> P1_2
    P1_2 -->|"Consulta"| DS_STOCK
    DS_STOCK -->|"Cantidad disponible"| P1_2
    P1_2 -->|"Stock OK"| P1_3
    P1_2 -->|"Sin stock"| STOCK_ERR
    STOCK_ERR --> END_CANCEL
    P1_3 --> P1_4
    P1_4 --> P1_5
    P1_5 -->|"Pago insuficiente"| PAY_ERR
    PAY_ERR --> P1_5
    P1_5 -->|"Pago OK"| P1_6
    P1_6 -->|"Graba"| DS_SALES
    P1_6 -->|"Graba pago"| DS_PAY
    P1_6 --> P1_7
    P1_7 -->|"Actualiza"| DS_STOCK
    P1_7 --> P1_8
    P1_8 -->|"Graba"| DS_RCPT
    P1_8 --> P1_9
    P1_9 --> END_OK
```

---

## Nivel 2 — Detalle P2: Flujo de Compra

```mermaid
flowchart TD
    START2(["📦 Nueva compra"])
    P2_1["2.1 Seleccionar Proveedor"]
    P2_2["2.2 Agregar ítems\n(producto, cantidad, costo)"]
    P2_3["2.3 Crear Compra\n(PurchaseService::createPurchase)\nstatus = pending"]
    P2_4{"¿Recibir\nproductos?"}
    P2_5["2.5 Recibir Mercadería\n(PurchaseService::receiveProducts)\nstatus = received"]
    P2_6["2.6 Aumentar Stock\n(InventoryService::addStock)"]
    P2_7["2.7 Generar Recibo\n(ReceiptService::generateReceipt)"]
    END2(["✅ Compra registrada"])

    DS_SUPP[("💾 suppliers")]
    DS_PUR[("💾 purchases\npurchase_items")]
    DS_STOCK[("💾 stocks")]
    DS_RCPT[("💾 receipts")]

    START2 --> P2_1
    P2_1 -->|"Consulta"| DS_SUPP
    P2_1 --> P2_2
    P2_2 --> P2_3
    P2_3 -->|"Graba"| DS_PUR
    P2_3 --> P2_4
    P2_4 -->|"Sí (receive_products=true)"| P2_5
    P2_4 -->|"No (pendiente)"| END2
    P2_5 --> P2_6
    P2_6 -->|"Actualiza"| DS_STOCK
    P2_5 --> P2_7
    P2_7 -->|"Graba"| DS_RCPT
    P2_7 --> END2
```

---

## Almacenes de Datos (Data Stores)

| ID | Nombre | Tablas | Descripción |
|---|---|---|---|
| DS1 | Ventas | `sales`, `sale_items`, `payments` | Transacciones de venta y cobros |
| DS2 | Compras | `purchases`, `purchase_items` | Órdenes y recepción de mercadería |
| DS3 | Inventario | `stocks`, `stock_movements`, `product_variants` | Stock actual y movimientos |
| DS4 | Caja | `cash_registers`, `cash_movements` | Operaciones de caja |
| DS5 | Maestros | `users`, `branches`, `categories`, `products`, `customers`, `suppliers` | Datos de configuración |
| DS6 | Comprobantes | `receipts`, `receipt_templates` | PDFs generados |
| DS7 | Ubicaciones | `warehouses`, `warehouse_aisles`, `shelves`, `shelf_rows`, `shelf_levels`, `product_locations` | Estructura física del almacén |
