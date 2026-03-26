# Manual del Cajero / Vendedor

> **Rol:** `vendedor`
> **Acceso a:** Ventas, Caja, Clientes, Productos (solo lectura)
> **Última actualización:** 26/03/2026

---

## Índice
1. [Acceso al Sistema](#1-acceso-al-sistema)
2. [Gestión de Caja](#2-gestión-de-caja)
3. [Módulo de Ventas](#3-módulo-de-ventas)
   - [Tabs y cómo navegar](#31-tabs-del-listado-de-ventas)
   - [Nueva venta al contado](#32-nueva-venta-al-contado)
   - [Nueva venta a crédito](#33-nueva-venta-a-crédito)
   - [Nota de Pedido / Presupuesto](#34-nota-de-pedido--presupuesto)
   - [Escanear producto con lector](#35-escanear-producto-con-lector-de-código-de-barras)
   - [Imprimir comprobante](#36-imprimir-comprobante)
   - [Anular una venta](#37-anular-una-venta)
4. [Módulo de Clientes](#4-módulo-de-clientes)
5. [Consulta de Stock](#5-consulta-de-stock)
6. [Errores frecuentes](#6-errores-frecuentes)

---

## 1. Acceso al Sistema

1. Abrí el navegador e ingresá a la URL del sistema (Ej: `http://localhost:8000/admin`).
2. Ingresá tu **correo electrónico** y **contraseña**.
3. Hacé clic en **Iniciar Sesión**.

> **Nota:** El sistema muestra automáticamente solo los datos de tu sucursal asignada. No vas a ver ventas ni stock de otras sucursales.

**¿Olvidaste la contraseña?** Contactá al Administrador para que la resetee desde el panel de Usuarios.

---

## 2. Gestión de Caja

**⚠️ Importante:** No podés crear ventas si no tenés una caja abierta. El botón "Nueva Venta" aparece deshabilitado hasta que abras tu caja del día.

### 2.1 Abrir la caja

1. En el menú izquierdo, hacé clic en **Ventas → Cajas**.
2. Hacé clic en **Nueva Caja**.
3. Completá:
   - **Nombre**: ej. "Caja Principal Turno Mañana"
   - **Sucursal**: ya viene precargada con la tuya
   - **Monto de Apertura**: el efectivo con el que arrancás el turno (ej. 50.000 Gs)
4. Hacé clic en **Crear**.

La caja queda en estado **Abierta** y todas las ventas en efectivo se suman automáticamente.

### 2.2 Cerrar la caja

Al finalizar el turno:

1. Andá a **Ventas → Cajas** y buscá tu caja abierta.
2. Hacé clic en **Editar**.
3. Ingresá el **Monto de Cierre** (conteo físico del efectivo).
4. El sistema calcula automáticamente la diferencia con las ventas registradas.
5. Si hay diferencia importante (> 10%), aparece una advertencia. Anotá el motivo en **Notas**.
6. Hacé clic en **Guardar**.

---

## 3. Módulo de Ventas

### 3.1 Tabs del listado de Ventas

Cuando entrás a **Ventas**, el listado tiene cuatro pestañas (tabs):

| Tab | Qué muestra |
|-----|-------------|
| **Todas** | Todas las ventas sin filtro |
| **Notas de Pedido / Presupuestos** | Ventas en estado *Pendiente* (presupuestos no cobrados aún). Tiene un número en naranja indicando cuántas hay. |
| **Completadas** | Ventas ya cobradas |
| **Créditos Activos** | Ventas a crédito con saldo pendiente de cobro |

Usá la pestaña **Notas de Pedido / Presupuestos** para encontrar rápidamente los presupuestos que emitiste y convertirlos en venta real.

### 3.2 Nueva venta al contado

1. Hacé clic en **Nueva Venta** (si está deshabilitado, abrí tu caja primero).
2. Completá el encabezado:
   - **Cliente**: buscá por nombre o documento. Si es consumidor final, dejalo en blanco.
   - **Estado**: `Completado`
   - **Método de Pago**: `Contado`
   - **Tipo de Documento**: `Ticket` (para comprobante interno) o `Factura` (cuando el cliente pide factura legal SIFEN).
3. En la sección **Productos**:
   - Buscá el producto por nombre o SKU en el selector.
   - Modificá la **Cantidad** si llevá más de uno.
   - El **Precio unit.** y **Subtotal** se calculan solos.
   - Podés agregar descuento individual por ítem en el campo **Desc.**
4. En **Totales** vas a ver:
   - Subtotal desglosado por tasa de IVA (Exenta / 5% / 10%)
   - Campo **Descuento Gral.**: descuento sobre el total
   - Panel grande azul con el **TOTAL A COBRAR** en Guaraníes
5. Hacé clic en **Crear**.

> **Precios y IVA:** Los precios del sistema incluyen IVA (sistema paraguayo). Lo que se muestra en pantalla es el precio final al cliente. Las cifras de IVA son para el desglose contable.

### 3.3 Nueva venta a crédito

1. Seguí los mismos pasos que una venta al contado.
2. En **Método de Pago**, elegí **Crédito**.
3. Aparece automáticamente el campo **Vencimiento del crédito** con fecha sugerida en 30 días. Podés cambiarla según el acuerdo con el cliente.
4. Hacé clic en **Crear**.

La venta queda registrada. El saldo aparece en la cuenta del cliente y en el **Calendario de Créditos** (accesible para supervisores y cobradores).

> **Atención:** Las ventas a crédito **no entran a caja**. El monto queda como saldo pendiente del cliente hasta que el cobrador registre el pago.

### 3.4 Nota de Pedido / Presupuesto

Un presupuesto es una venta en estado **Pendiente**. No descuenta stock ni afecta caja.

**Para crear un presupuesto:**

1. Seguí el proceso normal de venta.
2. En el campo **Estado**, elegí **Nota de Pedido**.
3. Hacé clic en **Crear**.

**Para imprimir el presupuesto:**

Desde el listado de ventas, en la tab **Notas de Pedido**, buscá la venta y usá el botón **Presupuesto** (ícono de documento con moneda).

**Para convertirlo en venta real:**

Desde el listado, hacé clic en **Aprobar a Venta**. Se abre un modal para registrar el pago recibido (efectivo, tarjeta, transferencia, QR). Una vez confirmado, el stock se descuenta y la venta queda completada.

O si el cliente paga todo en efectivo al instante, usá **Cobro Rápido (Efectivo)** — aprueba y cobra en un solo clic.

### 3.5 Escanear producto con lector de código de barras

En el formulario de venta, la sección **Productos** tiene el botón **Escanear producto** (ícono QR).

1. Hacé clic en **Escanear producto**.
2. Apuntá el lector al código de barras del producto (o escribí el SKU manualmente).
3. El sistema busca el producto y lo agrega a la lista. Si ya estaba en la lista, incrementa la cantidad en 1.

### 3.6 Imprimir comprobante

Solo disponible para ventas **Completadas**.

1. En el listado de ventas, buscá la venta.
2. Hacé clic en **Imprimir Ticket/Factura** (ícono de impresora azul).
3. Se descarga un PDF:
   - **Ticket**: formato 80mm para impresora térmica, con desglose de IVA y código QR.
   - **Factura**: formato A4, con datos del timbrado, CDC y datos del cliente.
4. Imprimí desde el PDF.

### 3.7 Anular una venta

Solo para ventas **Completadas**. El botón aparece como una **X roja** (`Anular`).

1. Hacé clic en **Anular**.
2. Seleccioná el **Motivo** (Error en precio, Producto equivocado, Devolución del cliente, etc.).
3. Podés agregar un detalle en **Detalle adicional**.
4. Hacé clic en **Sí, anular venta**.

El stock se devuelve automáticamente al almacén.

> **Nota:** Si no tenés permisos para anular, contactá a tu Supervisor.

---

## 4. Módulo de Clientes

1. Andá a **Clientes** en el menú.
2. Buscá por nombre, RUC o CI con la barra de búsqueda.
3. Hacé clic en el cliente para ver:
   - **Saldo actual** (deuda vigente)
   - **Límite de crédito**
   - Historial de ventas y pagos

**Crear un cliente nuevo:**

Podés crearlo directamente desde el formulario de venta (el campo Cliente tiene un botón `+`), o desde el menú **Clientes → Nuevo Cliente**.

Campos básicos: Nombre, RUC/CI, Teléfono, Email, Dirección.

---

## 5. Consulta de Stock

1. Andá a **Stock** o **Productos** en el menú.
2. Los productos con stock bajo aparecen con un indicador de color:
   - 🟢 Verde: stock normal
   - 🟡 Amarillo: por debajo del mínimo
   - 🔴 Rojo: sin stock
3. Podés filtrar por categoría, nombre o código.

---

## 6. Errores frecuentes

| Problema | Causa | Solución |
|----------|-------|----------|
| El botón "Nueva Venta" está deshabilitado | No tenés caja abierta | Abrir caja desde **Cajas** |
| "Stock insuficiente en sucursal" al ingresar cantidad | El stock disponible es menor a lo solicitado | Reducir cantidad o consultar al almacenero |
| La venta se crea pero no descuenta stock | La venta quedó en estado "Pendiente" | Verificar que el Estado sea "Completado" |
| No aparece el botón "Imprimir Ticket/Factura" | La venta no está completada | Solo disponible en ventas completadas |
| Pantalla en blanco o error 500 | Error del servidor | Contactar al Administrador |

> **Soporte:** En caso de errores que no podés resolver, contactá a tu **Supervisor** o al **Administrador del Sistema**.
