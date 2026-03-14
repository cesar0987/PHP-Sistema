# Manual del Administrador y Supervisor

## Índice
1. [Introducción](#introducción)
2. [Gestión de Sucursales y Usuarios](#gestión-de-sucursales-y-usuarios)
   - Alta de Sucursales
   - Alta de Usuarios y Roles
   - Asignación de Permisos
3. [Administración de Catálogos](#administración-de-catálogos)
   - Categorías y Productos
   - Plantillas de Comprobantes
4. [Trazabilidad y Auditoría](#trazabilidad-y-auditoría)
   - Registro de Actividades (Activity Log)
5. [Reportes y Métricas](#reportes-y-métricas)
6. [Manejo de Operaciones Avanzadas](#manejo-de-operaciones-avanzadas)
   - Anulación de Ventas
   - Recepción de Compras de Proveedores
   - Ajustes de Inventario

---

## Introducción
Este manual está dirigido a los usuarios con rol **Admin** o **Supervisor** del Sistema POS Terracota. Al contar con mayores privilegios, debes dominar no solo la operación comercial, sino también la configuración inicial, actualización de catálogos y auditoría contable/operativa de los empleados (cajeros).

> **Aviso Importante:** El rol **Admin** es global; puede ver toda la información de todas las sucursales. El rol **Supervisor** está asociado a un `branch_id` específico y solo verá datos de su propia sucursal, aunque contará con permisos avanzados (anulaciones, cierres forzosos).

---

## Gestión de Sucursales y Usuarios

Como administrador global, debes estructurar el sistema antes de la operación.

### Alta de Sucursales
1. En el menú de navegación izquierdo, ve a **Branches / Sucursales**.
2. Selecciona **New Branch**.
3. Rellena los datos básicos: `name` (Ej: Sucursal Centro), `address` (Dirección física), `phone` y `email`.
4. Si esta sucursal es la principal, activa el switch `is_main`.
5. Pulsa **Create**.

### Alta de Usuarios y Roles
Solo los administradores o supervisores designados podrán dar acceso a nuevos cajeros.
1. Ingresa a **Users / Usuarios**.
2. Dale a **New User**.
3. Rellena Nombre, Email y Contraseña.
4. En el selector *Roles*, elige el rol correspondiente (`vendedor` para cajeros básicos, `almacenero` para encargados de depósito, o `supervisor` para administradores locales).
5. Selecciona obligatoriamente a qué **Sucursal** (`branch_id`) pertenece dicho usuario. Si este campo queda nulo, el usuario no verá datos transaccionales, al menos que su rol sea `admin`.
6. Haz clic en **Create**.

### Asignación de Permisos
Si requieres permisos muy específicos para un usuario, el plugin *Shield* te permite asignar roles. Ve al módulo **Shield / Roles**, y podrás crear nuevos perfiles además de los existentes, marcando casillas exactas (ver ventas, editar productos, etc.).

---

## Administración de Catálogos

### Categorías y Productos
Mantener la base de datos de productos actualizada es vital.
1. **Categorías**: Antes de cargar un producto, crea su Categoría (`Ferretería`, `Pinturas`, etc.) desde el menú **Categories**. Cada categoría pertenece a una **Compañía**, no a una sucursal, para que todas las sucursales vendan la misma base de ítems.
2. **Productos**: Entra a **Products**. Carga el `Nombre`, código único (`SKU`), código de barras (`barcode`), precio base y umbral mínimo de inventario (`low_stock_threshold`). Enlázalo con la categoría recién creada.

### Plantillas de Comprobantes
Puedes definir cómo se verán las facturas impresas.
1. Ingresa a **Receipt Templates**.
2. Rellena el HTML y CSS en los recuadros. Utiliza etiquetas Blade (`{{ $sale->total }}`) para el renderizado de datos.
3. Pon un logo en la configuración de la Compañía.

---

## Trazabilidad y Auditoría

### Registro de Actividades (Activity Log)
Para evitar robos silenciosos o identificar quién borró un registro:
1. Navega a la sección **Activities** (bajo el apartado de Auditoría/Admin).
2. Podrás ver un listado donde indica la fecha de la acción (`created_at`), el nombre del modelo afectado (Ej: `Sale`, `InventoryAdjustment`), qué pasó (Created, Updated, Deleted) y el **Causante (Causer)** (ej. "Juan Cajero").
3. Si un cajero cambia un precio u anula una venta, toda la traza JSON del "antes" y el "después" se visualizará aquí.

---

## Manejo de Operaciones Avanzadas

### Anulación de Ventas
Si un cliente devuelve la mercancía o la factura salió errada:
1. Un Administrador debe ir a **Sales / Ventas**.
2. Entrar a ver o editar la factura errónea.
3. Rellenar el campo **Cancellation reason** (Motivo de Anulación) y cambiar el estado o directamente darle a `Eliminar/Soft Delete` si el sistema se configuró para SoftDeletes, lo cual es útil si la anulación exige aprobación especial.

### Recepción de Compras de Proveedores
Para dar ingreso de stock formal:
1. Ve a **Compras / Purchases**.
2. Ingresa la factura del proveedor de compra.
3. Esto sumará el stock de inmediato si el estatus final de la compra es `received`.

### Ajustes de Inventario
En caso de pérdida, hurto, roturas o mermas:
1. Ve a **Inventory Adjustments**.
2. Crea un ajuste e indica una razón (Ej. Mercadería dañada, Pérdida).
3. Selecciona el Almacén de tu sucursal.
4. Esto bajará (-X) el nivel de stock global de ese ítem, en vez de usar el proceso de Venta. Todo ajuste queda registrado con el ID del trabajador en el Activity Log.
