<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected Supplier $supplier;
    protected Product $product;
    protected ProductVariant $variant;
    protected Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $company = \App\Models\Company::create(['name' => 'Mi Empresa']);
        $this->branch = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal 1']);
        
        $this->user = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Depósito Central',
            'branch_id' => $this->branch->id,
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Ferretería Proveedor SA',
            'ruc' => '80012345-6',
        ]);

        $category = \App\Models\Category::create(['name' => 'Tornillos', 'slug' => 'tornillos']);
        
        $this->product = Product::create([
            'name' => 'Tornillo 10mm',
            'category_id' => $category->id,
            'cost_price' => 30000,
            'sale_price' => 50000,
            'active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'price' => 50000,
            'sku' => 'TORN-01',
        ]);

        $this->stock = Stock::create([
            'product_variant_id' => $this->variant->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ]);
    }

    public function test_complete_purchase_flow()
    {
        $this->actingAs($this->user);

        $purchaseData = [
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'purchase_date' => now(),
            'status' => 'pending',
            'condition' => 'contado',
            'invoice_number' => '001-001-1234567',
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 50,
                    'cost' => 28000, // Costo nuevo
                    'subtotal' => 1400000,
                ]
            ]
        ];

        $purchaseService = app(PurchaseService::class);
        $purchase = $purchaseService->createPurchase($purchaseData);

        $this->assertEquals(1400000, $purchase->total);
        $this->assertEquals('pending', $purchase->status);

        // Recibir la compra
        $purchaseService->receiveProducts($purchase);

        // Validar que el stock aumentó 50
        $this->assertEquals(60, $this->stock->fresh()->quantity);
        $this->assertEquals('received', $purchase->fresh()->status);

        // Validar el costo promedio o el history de compra
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $this->stock->id,
            'type' => 'purchase',
            'quantity' => 50,
        ]);
    }
}
