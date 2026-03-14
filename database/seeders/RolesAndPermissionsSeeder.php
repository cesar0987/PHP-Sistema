<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Permisos por módulo ───────────────────────────────────────

        $permissions = [
            // Productos
            'ver_productos',
            'crear_productos',
            'editar_productos',
            'eliminar_productos',

            // Categorías
            'ver_categorias',
            'crear_categorias',
            'editar_categorias',
            'eliminar_categorias',

            // Ventas
            'ver_ventas',
            'crear_ventas',
            'editar_ventas',
            'eliminar_ventas',
            'anular_ventas',

            // Compras
            'ver_compras',
            'crear_compras',
            'editar_compras',
            'eliminar_compras',

            // Inventario / Stock
            'ver_stock',
            'ver_ajustes_inventario',
            'crear_ajustes_inventario',
            'editar_ajustes_inventario',
            'eliminar_ajustes_inventario',

            // Ubicaciones / Almacenes
            'ver_almacenes',
            'crear_almacenes',
            'editar_almacenes',
            'eliminar_almacenes',
            'ver_ubicaciones',
            'crear_ubicaciones',
            'editar_ubicaciones',
            'eliminar_ubicaciones',

            // Clientes
            'ver_clientes',
            'crear_clientes',
            'editar_clientes',
            'eliminar_clientes',

            // Proveedores
            'ver_proveedores',
            'crear_proveedores',
            'editar_proveedores',
            'eliminar_proveedores',

            // Caja registradora
            'ver_cajas',
            'crear_cajas',
            'editar_cajas',
            'eliminar_cajas',

            // Comprobantes / Facturación
            'ver_comprobantes',
            'crear_comprobantes',
            'editar_comprobantes',
            'eliminar_comprobantes',
            'imprimir_comprobantes',

            // Usuarios y sistema
            'ver_usuarios',
            'crear_usuarios',
            'editar_usuarios',
            'eliminar_usuarios',
            'gestionar_roles',

            // Gastos
            'ver_gastos',
            'crear_gastos',
            'editar_gastos',
            'eliminar_gastos',

            // Dashboard y reportes
            'ver_dashboard',
            'ver_reportes',
            'ver_auditoria',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ─── Roles ─────────────────────────────────────────────────────

        // 1. Admin — acceso total
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // 2. Vendedor — ventas, caja, consulta productos
        $vendedor = Role::firstOrCreate(['name' => 'vendedor']);
        $vendedor->syncPermissions([
            'ver_productos',
            'ver_categorias',
            'ver_ventas',
            'crear_ventas',
            'ver_clientes',
            'crear_clientes',
            'editar_clientes',
            'ver_stock',
            'ver_cajas',
            'crear_cajas',
            'editar_cajas',
            'ver_comprobantes',
            'crear_comprobantes',
            'imprimir_comprobantes',
            'ver_dashboard',
        ]);

        // 3. Almacenero — inventario, ubicaciones, recibir compras
        $almacenero = Role::firstOrCreate(['name' => 'almacenero']);
        $almacenero->syncPermissions([
            'ver_productos',
            'crear_productos',
            'editar_productos',
            'ver_categorias',
            'crear_categorias',
            'editar_categorias',
            'ver_stock',
            'ver_ajustes_inventario',
            'crear_ajustes_inventario',
            'editar_ajustes_inventario',
            'ver_almacenes',
            'crear_almacenes',
            'editar_almacenes',
            'ver_ubicaciones',
            'crear_ubicaciones',
            'editar_ubicaciones',
            'ver_compras',
            'crear_compras',
            'editar_compras',
            'ver_proveedores',
            'crear_proveedores',
            'editar_proveedores',
            'ver_dashboard',
        ]);

        // 4. Supervisor — todo de vendedor + almacenero + anular + reportes
        $supervisor = Role::firstOrCreate(['name' => 'supervisor']);
        $supervisor->syncPermissions([
            // Productos
            'ver_productos',
            'crear_productos',
            'editar_productos',
            'eliminar_productos',
            'ver_categorias',
            'crear_categorias',
            'editar_categorias',
            'eliminar_categorias',
            // Ventas
            'ver_ventas',
            'crear_ventas',
            'editar_ventas',
            'eliminar_ventas',
            'anular_ventas',
            // Compras
            'ver_compras',
            'crear_compras',
            'editar_compras',
            'eliminar_compras',
            // Inventario
            'ver_stock',
            'ver_ajustes_inventario',
            'crear_ajustes_inventario',
            'editar_ajustes_inventario',
            'eliminar_ajustes_inventario',
            // Ubicaciones
            'ver_almacenes',
            'crear_almacenes',
            'editar_almacenes',
            'eliminar_almacenes',
            'ver_ubicaciones',
            'crear_ubicaciones',
            'editar_ubicaciones',
            'eliminar_ubicaciones',
            // Clientes / Proveedores
            'ver_clientes',
            'crear_clientes',
            'editar_clientes',
            'eliminar_clientes',
            'ver_proveedores',
            'crear_proveedores',
            'editar_proveedores',
            'eliminar_proveedores',
            // Caja
            'ver_cajas',
            'crear_cajas',
            'editar_cajas',
            'eliminar_cajas',
            // Comprobantes
            'ver_comprobantes',
            'crear_comprobantes',
            'editar_comprobantes',
            'eliminar_comprobantes',
            'imprimir_comprobantes',
            // Dashboard
            'ver_dashboard',
            'ver_reportes',
            'ver_auditoria',
        ]);

        echo "✓ 4 roles creados: admin, vendedor, almacenero, supervisor\n";
        echo '✓ '.Permission::count()." permisos creados\n";
    }
}
