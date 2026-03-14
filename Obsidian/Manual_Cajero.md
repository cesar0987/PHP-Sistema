# Manual del Cajero (Vendedor)

## Índice
1. [Introducción](#introducción)
2. [Acceso al Sistema](#acceso-al-sistema)
3. [Gestión de Caja](#gestión-de-caja)
   - Apertura de Caja
   - Cierre de Caja
4. [Módulo de Ventas](#módulo-de-ventas)
   - Búsqueda de Productos
   - Nueva Venta (Al Contado)
   - Nueva Venta (A Crédito)
   - Impresión de Comprobantes
5. [Módulo de Clientes](#módulo-de-clientes)
6. [Consultas Adicionales](#consultas-adicionales)
   - Consulta de Stock

---

## Introducción
Este manual describe paso a paso las operaciones diarias que un empleado con el rol **Vendedor** o **Cajero** debe realizar en el Sistema POS Terracota. Tu función principal incluye la atención al cliente, el registro de ventas, el manejo de la caja registradora asignada y la consulta rápida de inventario.

---

## Acceso al Sistema
1. Ingresa a la URL del sistema desde tu navegador.
2. Introduce tu **Correo electrónico** y **Contraseña**.
3. Haz clic en **Iniciar Sesión**.
> *Nota: Al ingresar, el sistema pre-filtrará toda la información (ventas, cajas, inventario) para mostrar únicamente los datos correspondientes a tu sucursal asignada.*

---

## Gestión de Caja

Antes de comenzar a facturar o registrar ventas, debes asegurar la apertura de tu caja.

### Apertura de Caja
1. En el menú lateral izquierdo, ve a **Cajas / Cash Registers**.
2. Dale clic a **New Cash register** (Nueva Caja).
3. Selecciona la sucursal actual y tu usuario (normalmente pre-cargado).
4. Ingresa el **Opening Balance** (Monto de Apertura) con el efectivo base con el que inicias el turno.
5. Haz clic en **Create**.
> *A partir de este momento, todas las ventas en efectivo se sumarán automáticamente al saldo actual de esta caja.*

### Cierre de Caja
Al finalizar tu turno:
1. Ve al listado de **Cajas**, selecciona tu caja abierta y haz clic en **Edit**.
2. Ingresa la fecha/hora actual en **Closed At**.
3. Ingresa el conteo físico final en **Closing Balance** (Monto de Cierre).
4. Si hay una diferencia entre el efectivo esperado y el físico, escríbelo en **Notes** (Observaciones).
5. Haz clic en **Save changes**.

---

## Módulo de Ventas

El proceso central del sistema. Aquí puedes cobrar productos a los clientes.

### Nueva Venta (Al Contado)
1. En el menú lateral, ve a **Ventas / Sales**.
2. Haz clic en **New Sale**.
3. Busca al **Cliente** (puedes buscar por documento o nombre). Si no existe, puedes crearlo directamente desde el símbolo `+`.
4. El tipo de comprobante por defecto será **Factura** o **Ticket** (según corresponda). Selecciona **Condición Contado**.
5. En la sección **Productos**, haz clic en **Add product**:
   - Busca el producto por nombre o código de barras.
   - El precio unitario se cargará por defecto.
   - Modifica la cantidad si el cliente lleva más de uno.
6. Revisa el **Total** al final de la pantalla y el monto cobrado.
7. Haz clic en **Create**.

### Nueva Venta (A Crédito)
Si un cliente tiene línea de crédito aprobada:
1. En el momento de la venta, al seleccionar al cliente, verifica que tenga crédito disponible.
2. En las opciones de pago de la venta, selecciona **Método de Pago: Crédito**.
3. Finaliza la venta. El saldo de la venta se sumará a la cuenta corriente del cliente y su monto disponible se ajustará en tiempo real. 

### Impresión de Comprobantes
1. Una vez creada la venta, en el listado de ventas (`/admin/sales`), busca la venta deseada.
2. Haz clic en el botón de la impresora o la opción "Imprimir Reporte".
3. Se generará un PDF en Formato Ticket de 80mm que podrás imprimir directamente en la impresora térmica.

---

## Módulo de Clientes
Puedes gestionar rápida y ágilmente la cartera de clientes de tu sucursal.
1. Ve a **Clientes / Customers**.
2. Usa el buscador en la parte superior derecha para buscar por **RUC/CI** o **Nombre**.
3. Para ver el estado del cliente (Saldo Actual, Límite de Crédito), haz clic sobre su nombre y entra a la pestaña **Ver/View**. Allí verás si tiene `is_credit_enabled` y cuánto saldo posee.
4. Puedes agregar un nuevo cliente con el botón **New Customer**.

---

## Consultas Adicionales

### Consulta de Stock
1. Ve a **Productos / Products** o a **Inventario / Stocks**.
2. Puedes utilizar los filtros (icono de embudo) para buscar productos específicos.
3. Al visualizar un producto, verás su cantidad total disponible en tu sucursal actual.
4. *Opcional:* Si necesitas ver la ubicación exacta del producto (Pasillo, Estante, Nivel), ingresa al detalle del producto en la lista.

> **Soporte Técnico:** En caso de errores en facturación, pantalla en blanco o imposibilidad de cerrar caja, contacta con tu **Supervisor** o con el **Administrador del Sistema**.
