# Manual de Operaciones Global del Sistema POS

Este manual explica a nivel técnico y operativo cómo funciona todo el ecosistema del sistema POS, enfocado actualmente en las operaciones de una Ferretería, garantizando seguridad, trazabilidad y cumplimiento fiscal (SIFEN).

---

## 1. Módulo de Cajas (Flujo de Caja)
El sistema opera bajo un modelo de estricto control de flujo de ingresos. 
- **Apertura Obligatoria:** Ningún vendedor puede registrar una venta si no tiene una Caja Abierta asignada a su turno.
- **Asignación Automática:** Toda venta realizada se vinculará transparente y permanentemente a la caja que el usuario tenga abierta en ese momento.
- **Cierre Ciego:** Al culminar el turno, el cajero debe ir a la vista de su Caja y seleccionar **"Cerrar Caja"**. Se le pedirá que declare el **efectivo físico** que tiene en posesión. El sistema cruza esta información con las ventas registradas y el monto de apertura, dejando el registro para auditoría gerencial sin revelarle cálculos previos al cajero.
- **Auditoría:** En la vista detallada de la caja, el supervisor puede ver un dashboard con el desglose del monto de apertura, ventas esperadas, y el historial de cada ticket/factura emitidos en la sesión.

---

## 2. Módulo de Ventas y Facturación
El Punto de Venta (POS) está diseñado para operar con rapidez, sin perder control fiscal:
- **Tipos de Documento:** Al realizar la venta, se debe elegir entre `Ticket` (comprobante interno) y `Factura` (comprobante con validez legal/fiscal SIFEN).
- **Control SIFEN:** Si se escoge `Factura`, el sistema validará que se introduzcan datos fiscales obligatorios como el Timbrado, el patrón estricto del Número de Factura (`001-001-XXXXXXX`), y en caso de ser electrónica, su código CDC.
- **Protección Antifraude:** Los vendedores comunes **tienen prohibido** editar o borrar una `Factura` una vez emitida. Esta es una tarea exclusiva de los usuarios con rol de **Administrador**.
- **Impresión:** Al culminar la venta, se genera automáticamente un PDF con diseño moderno (Ticket de 80mm).

---

## 3. Módulo de Inventario (Regla de Oro)
**El sistema está diseñado bajo Arquitectura Limpia.** Esto significa que **el stock JAMÁS se edita a mano** (por ejemplo, cambiando directamente un simple número). Toda modificación de inventario debe dejar un rastro contable.

### ¿Cómo ingresa/sale la mercadería del sistema?
1. **Compras a Proveedores:** Al registrar una Compra, el stock aumenta automáticamente. Si el producto tiene habilitado el **"Control de Vencimiento"**, se debe especificar la fecha de expiración, lo que creará un nuevo **Lote de Stock**.
2. **Ventas:** El stock se descuenta al instante. Para productos con vencimiento, el sistema aplica una **Lógica FIFO (First-In, First-Out)**, descontando automáticamente del lote que vence más pronto para evitar mermas.
3. **Ajustes de Inventario:** Permiten corregir el stock manualmentepara ingresos iniciales o roturas. En productos perecederos, el ajuste requiere identificar el lote (fecha de vencimiento) afectado.
4. **Transferencias:** Al mover mercadería entre sucursales, el sistema traslada también la información de los lotes y sus fechas de vencimiento de origen a destino.

### Control de Lotes (Stock Batches)
Para una trazabilidad total, el recurso **"Lotes de Stock"** permite auditar cuánta mercadería queda de cada partida específica, su fecha de vencimiento y en qué almacén se encuentra físicamente.

### Fiscalización: Tomas Físicas (Conteo de Inventario)
Para el control periódico del almacén, contamos con los **Inventarios Físicos**:
- Entras al módulo de **Tomas Físicas (Inventory Counts)** y creas un lote para una sucursal y empleado encargado.
- En la cuadrícula interactiva, el empleado registra la **"Cantidad Contada"** físicamente en la estantería.
- El sistema muestra una columna con la **"Diferencia"** calculada de forma reactiva (ideal para identificar faltantes/sobrantes ocultos).
- Al **"Completar"** y cerrar el conteo, el sistema consolida automáticamente esos reportes en *Ajustes de Inventario* aprobados, cuadrando la diferencia de la base de datos con la realidad.

---

## 4. Diseño y Flexibilidad: Plantillas de Impresión
- Los comprobantes no están estáticos ni "quemados" en el código.
- Los administradores tienen acceso a la sección de **"Plantillas de Recibo"**.
- Desde un panel web, sin conocimientos previos de programación backend, pueden editar el **HTML y CSS** de la plantilla del Ticket o Factura, inyectando variables dinámicas y viendo en tiempo real cómo cambia su logo corporativo, los agradecimientos, tipografías y ordenamiento visual de sus sucursales.

---

## Resumen Lógico
Toda acción deja un rastro:
- **`cash_registers`** registra y audita el dinero manejado.
- **`sales` y `purchases`** inyectan obligaciones financieras e impactan al stock.
- **`stock_movements`** detalla un "Libro Diario" exhaustivo de por qué se movió la mercadería en los estantes. 
- **`receipt_templates`** libera a los dueños a moldear la imagen estética final entregada al consumidor.
