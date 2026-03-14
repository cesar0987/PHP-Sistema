# Manual de Roles y Permisos — POS Terracota

> **Última actualización:** 14/03/2026

---

## Roles del Sistema

| Rol | Descripción | Color en UI |
|---|---|---|
| **admin** | Acceso total al sistema. Puede ver todas las sucursales. | 🔴 Rojo |
| **supervisor** | Control total operativo sin acceso admin. Incluye anulación de ventas, reportes y auditoría. | 🟡 Amarillo |
| **vendedor** | Opera ventas, caja, consulta productos y clientes. | 🟢 Verde |
| **almacenero** | Gestiona inventario, almacenes, ubicaciones, compras y proveedores. | 🔵 Azul |
| **cobrador** | Gestiona cobros de créditos, consulta clientes y vencimientos. | ⚪ Gris |

---

## Permisos por Rol

### Admin
✅ **Todos los permisos** del sistema (43 permisos).

### Supervisor
| Módulo | Permisos |
|---|---|
| Productos | ver, crear, editar, eliminar |
| Categorías | ver, crear, editar, eliminar |
| Ventas | ver, crear, editar, eliminar, **anular** |
| Compras | ver, crear, editar, eliminar |
| Inventario | ver stock, ver/crear/editar/eliminar ajustes |
| Almacenes | ver, crear, editar, eliminar + ubicaciones |
| Clientes | ver, crear, editar, eliminar |
| Proveedores | ver, crear, editar, eliminar |
| Caja | ver, crear, editar, eliminar |
| Comprobantes | ver, crear, editar, eliminar, imprimir |
| Sistema | dashboard, reportes, **auditoría** |

### Vendedor
| Módulo | Permisos |
|---|---|
| Productos | ver |
| Categorías | ver |
| Ventas | ver, crear |
| Clientes | ver, crear, editar |
| Stock | ver |
| Caja | ver, crear, editar |
| Comprobantes | ver, crear, imprimir |
| Sistema | dashboard |

### Almacenero
| Módulo | Permisos |
|---|---|
| Productos | ver, crear, editar |
| Categorías | ver, crear, editar |
| Stock | ver |
| Ajustes Inv. | ver, crear, editar |
| Almacenes | ver, crear, editar + ubicaciones |
| Compras | ver, crear, editar |
| Proveedores | ver, crear, editar |
| Sistema | dashboard |

### Cobrador
| Módulo | Permisos |
|---|---|
| Clientes | ver, editar |
| Ventas | ver |
| Caja | ver |
| Comprobantes | ver, imprimir |
| Sistema | dashboard |

---

## Cómo Crear un Rol Personalizado

1. **Editar el Seeder** en `database/seeders/RolesAndPermissionsSeeder.php`:
   ```php
   $nuevoRol = Role::firstOrCreate(['name' => 'mi_rol']);
   $nuevoRol->syncPermissions([
       'ver_productos',
       'ver_ventas',
       // ... agregar los permisos necesarios
   ]);
   ```

2. **Ejecutar el Seeder**:
   ```bash
   php artisan db:seed --class=RolesAndPermissionsSeeder
   ```

3. **Agregar label en UserResource** (opcional):
   En `UserResource.php`, agregar el mapeo del nombre en el Select de roles:
   ```php
   'mi_rol' => 'Mi Rol — Descripción breve',
   ```

4. **Limpiar caché de permisos**:
   ```bash
   php artisan permission:cache-reset
   ```

---

## Permisos Disponibles (43 total)

| Módulo | Permisos |
|---|---|
| Productos | `ver_productos`, `crear_productos`, `editar_productos`, `eliminar_productos` |
| Categorías | `ver_categorias`, `crear_categorias`, `editar_categorias`, `eliminar_categorias` |
| Ventas | `ver_ventas`, `crear_ventas`, `editar_ventas`, `eliminar_ventas`, `anular_ventas` |
| Compras | `ver_compras`, `crear_compras`, `editar_compras`, `eliminar_compras` |
| Inventario | `ver_stock`, `ver_ajustes_inventario`, `crear_ajustes_inventario`, `editar_ajustes_inventario`, `eliminar_ajustes_inventario` |
| Almacenes | `ver_almacenes`, `crear_almacenes`, `editar_almacenes`, `eliminar_almacenes`, `ver_ubicaciones`, `crear_ubicaciones`, `editar_ubicaciones`, `eliminar_ubicaciones` |
| Clientes | `ver_clientes`, `crear_clientes`, `editar_clientes`, `eliminar_clientes` |
| Proveedores | `ver_proveedores`, `crear_proveedores`, `editar_proveedores`, `eliminar_proveedores` |
| Caja | `ver_cajas`, `crear_cajas`, `editar_cajas`, `eliminar_cajas` |
| Comprobantes | `ver_comprobantes`, `crear_comprobantes`, `editar_comprobantes`, `eliminar_comprobantes`, `imprimir_comprobantes` |
| Usuarios | `ver_usuarios`, `crear_usuarios`, `editar_usuarios`, `eliminar_usuarios`, `gestionar_roles` |
| Gastos | `ver_gastos`, `crear_gastos`, `editar_gastos`, `eliminar_gastos` |
| Sistema | `ver_dashboard`, `ver_reportes`, `ver_auditoria` |
