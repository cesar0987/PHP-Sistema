<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseService $service;

    protected ProductVariant $variant;

    protected Warehouse $warehouse;

    protected Branch $branch;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PurchaseService::class);

        $company = Company::create(['name' => 'Test Co', 'ruc' => '123']);
        $this->branch = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Test']);
        $this->warehouse = Warehouse::create([
            'branch_id' => $this->branch->id,
            'name' => 'Almacén Test',
            'is_default' => true,
            'active' => true,
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Proveedor Test',
            'ruc' => '111222333',
        ]);

        $category = Category::create(['name' => 'Test Cat', 'active' => true]);
        $product = Product::create([
            'name' => 'Producto Test',
            'category_id' => $category->id,
            'cost_price' => 5000,
            'sale_price' => 10000,
            'min_stock' => 5,
            'tax_percentage' => 10,
            'active' => true,
        ]);
        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'TEST-001',
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_create_purchase_without_receiving(): void
    {
        $purchase = $this->service->createPurchase([
            'user_id' => 1,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 20,
                    'cost' => 5000,
                ],
            ],
        ]);

        $this->assertNotNull($purchase);
        $this->assertEquals('pending', $purchase->status);
        $this->assertEquals(100000, $purchase->total); // 20 * 5000

        // Stock should NOT increase
        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertNull($stock);
    }

    public function test_create_purchase_with_receiving_adds_stock(): void
    {
        $purchase = $this->service->createPurchase([
            'user_id' => 1,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'receive_products' => true,
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 20,
                    'cost' => 5000,
                ],
            ],
        ]);

        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertNotNull($stock);
        $this->assertEquals(20, $stock->quantity);
    }

    public function test_receive_products_updates_status_and_stock(): void
    {
        $purchase = $this->service->createPurchase([
            'user_id' => 1,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 30,
                    'cost' => 5000,
                ],
            ],
        ]);

        $this->assertEquals('pending', $purchase->status);

        $this->service->receiveProducts($purchase);

        $this->assertEquals('received', $purchase->fresh()->status);

        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(30, $stock->quantity);
    }

    public function test_cancel_purchase_changes_status(): void
    {
        $purchase = $this->service->createPurchase([
            'user_id' => 1,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 10,
                    'cost' => 5000,
                ],
            ],
        ]);

        $this->service->cancelPurchase($purchase);
        $this->assertEquals('cancelled', $purchase->fresh()->status);
    }
}
