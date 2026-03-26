# Manual del Administrador y Supervisor

> **Roles:** `admin` / `supervisor`
> **Acceso a:** Todos los módulos. El Admin ve todas las sucursales; el Supervisor ve solo la suya.
> **Última actualización:** 26/03/2026

---

## Índice
1. [Configuración inicial](#1-configuración-inicial)
2. [Gestión de Usuarios y Roles](#2-gestión-de-usuarios-y-roles)
3. [Catálogo de Productos](#3-catálogo-de-productos)
4. [Módulo de Compras](#4-módulo-de-compras)
5. [Inventario y Ajustes](#5-inventario-y-ajustes)
6. [Sistema de Créditos a Clientes](#6-sistema-de-créditos-a-clientes)
7. [Gestión Avanzada de Ventas](#7-gestión-avanzada-de-ventas)
8. [Plantillas de Comprobantes](#8-plantillas-de-comprobantes)
9. [Facturación Electrónica (SIFEN)](#9-facturación-electrónica-sifen)
10. [Trazabilidad y Auditoría](#10-trazabilidad-y-auditoría)
11. [Reportes](#11-reportes)

---

## 1. Configuración inicial

Antes de operar, configurar la empresa y sucursales.

### 1.1 Datos de la empresa

Andá a **Configuración → Empresa**. Completá:
- Nombre, RUC (formato `80000001-5`), dirección, teléfono, email
- Para facturación SIFEN: RUC DV, tipo de contribuyente, actividad económica, ciudad/departamento

### 1.2 Crear sucursales

1. Menú **Configuración → Sucursales → Nueva Sucursal**.
2. Completá nombre, dirección, teléfono.
3. Para SIFEN: `establishment_code` (3 dígitos), `dispatch_point` (3 dígitos), número de timbrado y fecha de inicio.

### 1.3 Crear almacenes

Cada sucursal necesita al menos un almacén.
1. Menú **Almacenes → Nuevo Almacén**.
2. Asigná a la sucursal correspondiente. Marcá el principal como `is_default = true`.

---

## 2. Gestión de Usuarios y Roles

### 2.1 Crear usuarios

1. Menú **Usuarios → Nuevo Usuario**.
2. Completá: Nombre, Email, Contraseña.
3. Asigná el **Rol** correspondiente (ver tabla abajo).
4. **⚠️ Obligatorio:** Asigná la **Sucursal** (`branch_id`). Sin esto, el usuario no verá datos.

### 2.2 Roles del sistema

| Rol | Descripción |
|-----|-------------|
| `admin` | Acceso total, todas las sucursales |
| `supervisor` | Acceso avanzado, solo su sucursal |
| `vendedor` | Crear ventas, consultar stock |
| `almacenero` | Gestionar compras, ajustes de inventario |
| `cobrador` | Ver y registrar cobros de créditos |

Para ver el detalle de cada permiso, consultá [[Manual_Roles_Permisos]].

### 2.3 Resetear contraseña

Desde **Usuarios**, editá el usuario y completá el campo **Contraseña** con la nueva.

---

## 3. Catálogo de Productos

### 3.1 Crear categorías

Antes de cargar productos, creá las categorías:
1. **Categorías → Nueva Categoría**.
2. Podés crear categorías padre (ej. "Herramientas") y subcategorías (ej. "Martillos").

### 3.2 Crear productos

1. **Productos → Nuevo Producto**.
2. Campos obligatorios:
   - Nombre
   - Categoría
   - Precio de venta (IVA incluido, según sistema paraguayo)
   - Precio de costo
   - `tax_percentage`: 0% (exento), 5% o 10%
   - `min_stock`: stock mínimo antes de alerta
3. Por cada producto se crea automáticamente una variante base. Podés agregar más variantes (color, talle, etc.).

### 3.3 Carga inicial de stock

Consultá [[Manual_Carga_Stock]] para el proceso detallado. Resumen:
1. Crear el producto.
2. Ir a **Ajustes de Inventario → Nuevo Ajuste**.
3. Tipo: `entrada`, Almacén: el de tu sucursal, Motivo: `carga_inicial`.

---

## 4. Módulo de Compras

### 4.1 Crear una orden de compra

1. **Compras → Nueva Compra**.
2. Seleccioná el proveedor, fecha y almacén destino.
3. Agregá los ítems (producto, cantidad, precio de costo).
4. Estado inicial: `Pendiente` — no afecta stock todavía.

### 4.2 Recibir mercadería

Cuando la mercadería llega físicamente:
1. En el listado de Compras, buscá la orden y hacé clic en **Recibir Productos**.
2. Confirmá las cantidades recibidas.
3. El stock se suma automáticamente al almacén.

También podés crear la compra en estado `Recibido` directamente si la mercadería ya ingresó.

### 4.3 Cancelar una compra

Usá el botón **Cancelar** desde el listado. El stock NO se modifica si la compra no estaba recibida.

---

## 5. Inventario y Ajustes

### 5.1 Ajustes de inventario

Para pérdidas, roturas, mermas o correcciones:
1. **Inventario → Ajustes → Nuevo Ajuste**.
2. Seleccioná Almacén, Producto/Variante.
3. Tipo: `entrada` (suma) o `salida` (resta).
4. Ingresá la cantidad y el motivo (texto libre).
5. Todo ajuste queda registrado con el usuario que lo hizo.

### 5.2 Conteos físicos

Para auditar el inventario real vs. el sistema:
1. **Inventario → Conteos Físicos → Nuevo Conteo**.
2. Agregá los productos y la cantidad física contada.
3. El sistema calcula la diferencia automáticamente.
4. Al completar el conteo, podés aplicar los ajustes automáticamente.

### 5.3 Transferencias entre almacenes

Si tu sucursal tiene más de un almacén:
1. **Inventario → Transferencias → Nueva Transferencia**.
2. Indicá origen, destino, producto y cantidad.

---

## 6. Sistema de Créditos a Clientes

### 6.1 Habilitar crédito a un cliente

1. **Clientes → editar el cliente**.
2. Activá el toggle **Crédito habilitado** (`is_credit_enabled`).
3. Fijá el **Límite de crédito** máximo en Gs.

### 6.2 Calendario de Vencimientos (vista del cobrador/supervisor)

Menú **Ventas → Calendario de Créditos**.

Muestra las ventas a crédito individuales con:
- Total de la venta
- Monto ya cobrado
- **Saldo pendiente** (en rojo si tiene deuda)
- Fecha de vencimiento (con semáforo: 🔴 vencida / 🟡 próxima / 🟢 vigente)

Los stats de la parte superior muestran:
- Cantidad de ventas vencidas
- Ventas por vencer en 7 días
- Saldo total pendiente en Gs

### 6.3 Registrar un cobro

Desde el **Calendario de Créditos**, en la fila de la venta correspondiente:

1. Hacé clic en **Registrar Cobro**.
2. Se abre un modal con el resumen de la deuda.
3. Completá:
   - **Monto a cobrar** (puede ser parcial)
   - **Método de pago** (Efectivo, Transferencia, Tarjeta, etc.)
   - **Fecha del cobro**
   - Si queda saldo: activá el toggle **¿Queda saldo pendiente?** y fijá la **Nueva fecha de vencimiento**
4. Hacé clic en **Registrar cobro**.

> **Regla de la fecha de vencimiento:** La fecha se calcula desde la fecha del **cobro**, no desde la fecha de la factura original. Esto permite acordar cuotas en base a cuándo el cliente va pagando.

Cuando el saldo llega a cero, la venta sale automáticamente del calendario.

---

## 7. Gestión Avanzada de Ventas

### 7.1 Aprobar una Nota de Pedido

Las ventas en estado **Pendiente** (presupuestos) no afectan caja ni stock.

Para convertirlas en venta real:
1. En el listado de Ventas, ir a la tab **Notas de Pedido / Presupuestos**.
2. Hacé clic en **Aprobar a Venta**.
3. Registrá los pagos recibidos (puede ser múltiples métodos).
4. Si es pago total en efectivo: usar **Cobro Rápido (Efectivo)**.

### 7.2 Anular una venta completada

1. Listado de Ventas → venta completada → **Anular** (botón rojo).
2. Seleccioná el motivo en el desplegable.
3. Podés agregar notas adicionales.
4. Confirmá.

El stock **vuelve al almacén** automáticamente. La venta queda en estado `Cancelado` y visible en el historial (nunca se borra definitivamente).

### 7.3 Precios B2B (sin IVA)

En el formulario de venta, el toggle **Precios B2B (Sin IVA)** recalcula todos los precios dividiéndolos por 1.1 para empresas que compran con exención de IVA.

---

## 8. Plantillas de Comprobantes

El sistema tiene dos tipos de PDF:
- **Ticket (80mm)**: para impresora térmica
- **Factura (A4)**: para factura legal con timbrado y datos del receptor

Para personalizar las plantillas:
1. **Configuración → Plantillas de Comprobantes**.
2. Editá el HTML/CSS directamente. Los datos se inyectan con variables Blade (`{{ $sale->total }}`).

> Si no hay plantillas cargadas, ejecutar: `php artisan db:seed --class=ReceiptTemplateSeeder`

---

## 9. Facturación Electrónica (SIFEN)

El sistema genera los componentes técnicos de la factura electrónica SET Paraguay:

| Componente | Estado |
|------------|--------|
| CDC 44 dígitos | ✅ Generado automáticamente |
| URL QR con hash CSC | ✅ Incluida en el PDF |
| XML `<rDE>` SIFEN v150 | ✅ Generado por SifenXmlService |
| Firma digital RSA-SHA256 | ⏳ Requiere certificado .p12 de la SET |

Para emitir facturas electrónicas válidas ante la SET, configurar en `.env`:
```
SIFEN_ENV=test
SIFEN_CSC_ID=0001
SIFEN_CSC_VAL=ABCD0000000000000000000000000000
```

Consultá [[manual_sifen_v150]] para la documentación técnica oficial de la SET.

---

## 10. Trazabilidad y Auditoría

### 10.1 Log de actividad

Menú **Auditoría → Actividades**.

Registra automáticamente:
- Quién creó, modificó o eliminó cada registro
- Fecha y hora exacta
- Valores antes y después (JSON diff)
- Módulos auditados: Ventas, Compras, Productos, Ajustes, Usuarios

Filtrá por módulo (`venta`, `compra`, `inventario`, etc.) o por usuario para investigar movimientos específicos.

### 10.2 Log de autenticación

En la misma sección, filtrá por tipo `authentication` para ver:
- Inicios de sesión exitosos
- Intentos fallidos (con IP)
- Cierres de sesión

---

## 11. Reportes

El Dashboard principal (`/admin`) muestra:
- Ventas del día / semana / mes
- Productos más vendidos
- Stock bajo por almacén
- Ventas por vendedor

Para reportes más detallados, usá los filtros de las tablas. Los filtros disponibles en Ventas incluyen:
- **Rango de fechas** (con indicadores activos)
- Estado de la venta
- Condición de pago (Contado / Crédito)
- Tipo de documento (Ticket / Factura)
- Cliente / Vendedor

> **Exportar a Excel:** Función en desarrollo. Por ahora, usá los filtros y copiá la tabla, o accedé directamente a la base de datos SQLite.
