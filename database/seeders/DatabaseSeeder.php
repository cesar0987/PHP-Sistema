<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles and permissions FIRST
        $this->call(RolesAndPermissionsSeeder::class);

        $company = Company::create([
            'name' => 'Ferretería Central',
            'ruc' => '80000000-5',
            'address' => 'Av. Principal 123',
            'phone' => '021 123 456',
            'email' => 'info@ferreteriacentral.com',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Principal',
            'address' => 'Av. Principal 123',
            'phone' => '021 123 456',
        ]);

        $warehouse = Warehouse::create([
            'branch_id' => $branch->id,
            'name' => 'Almacén Principal',
            'description' => 'Depósito principal',
            'is_default' => true,
        ]);

        $categories = [
            ['name' => 'Herramientas Manuales', 'children' => [
                'Destornilladores', 'Martillos', 'Llaves', 'Pinzas',
            ]],
            ['name' => 'Electricidad', 'children' => [
                'Cables', 'Interruptores', 'Tomacorrientes', 'Focos',
            ]],
            ['name' => 'Pinturas', 'children' => [
                'Pinturas', 'Brochas', 'Rodillos', 'Diluyentes',
            ]],
            ['name' => 'Construcción', 'children' => [
                'Cemento', 'Arena', 'Ladrillos', 'Hierro',
            ]],
            ['name' => 'Plomería', 'children' => [
                'Caños', 'Válvulas', 'Grifería', 'Pegamentos',
            ]],
        ];

        foreach ($categories as $cat) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                ]
            );

            if (isset($cat['children'])) {
                foreach ($cat['children'] as $child) {
                    Category::firstOrCreate(
                        ['slug' => Str::slug($child)],
                        [
                            'parent_id' => $parent->id,
                            'name' => $child,
                        ]
                    );
                }
            }
        }

        $products = [
            ['name' => 'Destornillador Phillips', 'brand' => 'Stanley', 'cost' => 15000, 'price' => 25000, 'category' => 'Destornilladores'],
            ['name' => 'Martillo de Carpintero', 'brand' => 'Truper', 'cost' => 25000, 'price' => 45000, 'category' => 'Martillos'],
            ['name' => 'Llave Francesa 10"', 'brand' => 'Pretul', 'cost' => 35000, 'price' => 55000, 'category' => 'Llaves'],
            ['name' => 'Pinza de Corte', 'brand' => 'Stanley', 'cost' => 28000, 'price' => 42000, 'category' => 'Pinzas'],
            ['name' => 'Cable TW 2.5mm', 'brand' => 'Nexans', 'cost' => 12000, 'price' => 18000, 'category' => 'Cables'],
            ['name' => 'Foco LED 20W', 'brand' => 'Philips', 'cost' => 15000, 'price' => 25000, 'category' => 'Focos'],
            ['name' => 'Pintura Latex 4L', 'brand' => 'Alba', 'cost' => 65000, 'price' => 95000, 'category' => 'Pinturas'],
            ['name' => 'Cemento Holcim 50kg', 'brand' => 'Holcim', 'cost' => 42000, 'price' => 55000, 'category' => 'Cemento'],
            ['name' => 'Caño PVC 3"', 'brand' => 'Tigre', 'cost' => 18000, 'price' => 28000, 'category' => 'Caños'],
            ['name' => 'Grifo de Cocina', 'brand' => 'Fv', 'cost' => 45000, 'price' => 75000, 'category' => 'Grifería'],
        ];

        foreach ($products as $prod) {
            $category = Category::where('name', $prod['category'])->first();

            $product = Product::create([
                'category_id' => $category?->id,
                'name' => $prod['name'],
                'brand' => $prod['brand'],
                'cost_price' => $prod['cost'],
                'sale_price' => $prod['price'],
                'tax_percentage' => 10,
                'min_stock' => 10,
            ]);

            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => 'SKU-'.str_pad($product->id, 4, '0', STR_PAD_LEFT),
                'barcode' => '780'.str_pad($product->id, 10, '0', STR_PAD_LEFT),
                'price' => $prod['price'],
            ]);
        }

        $suppliers = [
            ['name' => 'Distribuidora Herrera', 'ruc' => '80012345-6'],
            ['name' => 'Importadora Paraguay', 'ruc' => '80023456-7'],
            ['name' => 'Casa de Herramientas', 'ruc' => '80034567-8'],
        ];

        foreach ($suppliers as $sup) {
            Supplier::create([
                'name' => $sup['name'],
                'ruc' => $sup['ruc'],
                'phone' => '021 555 000'.rand(1, 9),
                'email' => strtolower(str_replace(' ', '', $sup['name'])).'@gmail.com',
            ]);
        }

        $customers = [
            ['name' => 'Consumidor Final', 'document' => '0000000-0'],
            ['name' => 'Juan Pérez', 'document' => '1234567-8'],
            ['name' => 'María López', 'document' => '2345678-9'],
            ['name' => 'Carlos González', 'document' => '3456789-0'],
        ];

        foreach ($customers as $cust) {
            Customer::create([
                'name' => $cust['name'],
                'document' => $cust['document'],
                'phone' => '0981 '.rand(100000, 999999),
            ]);
        }

        $user = User::updateOrCreate(
            ['email' => 'admin@ferreteria.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );

        // Assign admin role to the admin user
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $user->assignRole($adminRole);
        }

        $this->call(ReceiptTemplateSeeder::class);

        echo "✓ Datos de prueba creados exitosamente!\n";
        echo "  - Empresa: Ferretería Central\n";
        echo "  - Sucursal: Sucursal Principal\n";
        echo "  - Almacén: Almacén Principal\n";
        echo '  - Categorías: '.Category::count()."\n";
        echo '  - Productos: '.Product::count()."\n";
        echo '  - Proveedores: '.Supplier::count()."\n";
        echo '  - Clientes: '.Customer::count()."\n";
        echo '  - Roles creados: '.Role::count()."\n";
        echo '  - Permisos creados: '.Permission::count()."\n";
        echo "  - Usuario admin: admin@ferreteria.com / password (con rol admin)\n";
    }
}
