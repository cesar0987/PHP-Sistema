<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected CashRegister $cashRegister;
    protected Product $product;
    protected ProductVariant $variant;
    protected Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $company = \App\Models\Company::create(['name' => 'Mi Empresa']);
        $this->branch = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Principal', 'address' => 'Centro', 'phone' => '123']);
        
        $this->user = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);

        $warehouse = \App\Models\Warehouse::create([
            'name' => 'Depósito Central',
            'branch_id' => $this->branch->id,
        ]);

        $this->cashRegister = CashRegister::create([
            'name' => 'Caja 1',
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'status' => 'open',
            'opening_amount' => 100000,
            'opened_at' => now(),
        ]);

        $category = Category::create(['name' => 'Herramientas', 'slug' => 'herramientas']);
        
        $this->product = Product::create([
            'name' => 'Martillo',
            'category_id' => $category->id,
            'sale_price' => 50000,
            'cost_price' => 30000,
            'active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'price' => 50000,
            'sku' => 'MART-001',
        ]);

        $this->stock = Stock::create([
            'product_variant_id' => $this->variant->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);
    }

    public function test_complete_sale_flow()
    {
        $this->actingAs($this->user);

        // 1. Simular la creación de la venta (Nota de Pedido)
        $saleData = [
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'cash_register_id' => $this->cashRegister->id,
            'status' => 'pending',
            'payment_method' => 'contado',
            'document_type' => 'ticket',
            'invoice_number' => 'TCK-12345',
            'sale_date' => now(),
            'notes' => 'Test flow',
            'warehouse_id' => $this->stock->warehouse_id, // Necesario para descontar stock
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 2,
                    'price' => 50000,
                    'discount' => 0,
                    'subtotal' => 100000,
                ]
            ]
        ];

        // 2. Ejecutar la lógica principal del servicio para crear venta
        $saleService = app(SaleService::class);
        $sale = $saleService->createSale($saleData);

        $this->assertEquals(100000, $sale->total);
        $this->assertEquals('pending', $sale->status);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'total' => 100000]);

        // 3. Aprobar y cobrar la venta
        $payments = [
            [
                'method' => 'cash',
                'amount' => 100000,
                'reference' => 'Cash handed',
            ]
        ];

        $saleService->approveSale($sale, $payments);

        // 4. Validar resultados en base de datos
        // Venta debe estar completada
        $this->assertEquals('completed', $sale->fresh()->status);
        
        // Stock debe haber disminuido en 2
        $this->assertEquals(8, $this->stock->fresh()->quantity);

        // Debe existir movimiento de stock tipo 'sale'
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $this->stock->id,
            'type' => 'sale',
            'quantity' => -2,
        ]);

        // Debe existir un registro de pago a nivel de cliente si es a crédito, pero para caja debe estar cobrado
        // No hay tabla cashier_payments en este diseño, el pago lo manejamos indirectamente o con la sumatoria
    }
}
