<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SaleService $service;

    protected ProductVariant $variant;

    protected Warehouse $warehouse;

    protected Branch $branch;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SaleService::class);

        $company = Company::create(['name' => 'Test Co', 'ruc' => '123']);
        $this->branch = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Test']);
        $this->warehouse = Warehouse::create([
            'branch_id' => $this->branch->id,
            'name' => 'Almacén Test',
            'is_default' => true,
            'active' => true,
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

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed stock (after actingAs so auth()->id() is available)
        $inventoryService = app(InventoryService::class);
        $inventoryService->addStock($this->variant, $this->warehouse, 100);
    }

    public function test_calculate_total_computes_correctly(): void
    {
        $items = [
            [
                'product_variant_id' => $this->variant->id,
                'quantity' => 3,
                'price' => 10000,
            ],
        ];

        $result = $this->service->calculateTotal($items, 500);

        // Paraguay: prices include IVA.
        // 3 * 10000 = 30000 subtotal (IVA incluido)
        // IVA 10% embedded = 30000 * 10/110 = 2727.27
        // total = 30000 - 500 (discount) = 29500
        $this->assertEquals(30000, $result['subtotal']);
        $this->assertEqualsWithDelta(2727.27, $result['tax'], 0.01);
        $this->assertEquals(500, $result['discount']);
        $this->assertEquals(29500, $result['total']);
    }

    public function test_create_sale_creates_sale_and_deducts_stock(): void
    {
        $sale = $this->service->createSale([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 5,
                    'price' => 10000,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 55000],
            ],
        ]);

        $this->assertNotNull($sale);
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals(50000, $sale->subtotal);

        // Stock should be 100 - 5 = 95
        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(95, $stock->quantity);
    }

    public function test_cancel_sale_returns_stock(): void
    {
        $sale = $this->service->createSale([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 10,
                    'price' => 10000,
                ],
            ],
        ]);

        // Stock is 100 - 10 = 90
        $this->service->cancelSale($sale);

        // Stock should be back to 100
        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(100, $stock->quantity);
        $this->assertEquals('cancelled', $sale->fresh()->status);
    }

    public function test_create_sale_fails_with_insufficient_stock(): void
    {
        $this->expectException(\Exception::class);

        $this->service->createSale([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 999,
                    'price' => 10000,
                ],
            ],
        ]);
    }
}
