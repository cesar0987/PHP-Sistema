<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $service;

    protected ProductVariant $variant;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService;

        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create(['name' => 'Test Co', 'ruc' => '123']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Test']);
        $this->warehouse = Warehouse::create([
            'branch_id' => $branch->id,
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
    }

    public function test_add_stock_creates_stock_record(): void
    {
        $this->service->addStock($this->variant, $this->warehouse, 50);

        $stock = Stock::where('product_variant_id', $this->variant->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(50, $stock->quantity);
    }

    public function test_add_stock_increments_existing_stock(): void
    {
        $this->service->addStock($this->variant, $this->warehouse, 30);
        $this->service->addStock($this->variant, $this->warehouse, 20);

        $stock = Stock::where('product_variant_id', $this->variant->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(50, $stock->quantity);
    }

    public function test_remove_stock_decrements_quantity(): void
    {
        $this->service->addStock($this->variant, $this->warehouse, 50);
        $this->service->removeStock($this->variant, $this->warehouse, 20);

        $stock = Stock::where('product_variant_id', $this->variant->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(30, $stock->quantity);
    }

    public function test_remove_stock_throws_on_insufficient_stock(): void
    {
        $this->service->addStock($this->variant, $this->warehouse, 10);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->service->removeStock($this->variant, $this->warehouse, 20);
    }

    public function test_adjust_stock_sets_exact_quantity(): void
    {
        $this->service->addStock($this->variant, $this->warehouse, 50);

        $adjustment = $this->service->adjustStock(
            $this->variant,
            $this->warehouse,
            35,
            'Conteo físico',
            1
        );

        $stock = Stock::where('product_variant_id', $this->variant->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(35, $stock->quantity);
        $this->assertNotNull($adjustment);
        $this->assertEquals('approved', $adjustment->status);
    }

    public function test_transfer_stock_moves_between_warehouses(): void
    {
        $branch = $this->warehouse->branch;
        $warehouse2 = Warehouse::create([
            'branch_id' => $branch->id,
            'name' => 'Almacén 2',
            'active' => true,
        ]);

        $this->service->addStock($this->variant, $this->warehouse, 50);

        $result = $this->service->transferStock(
            $this->variant,
            $this->warehouse,
            $warehouse2,
            20,
            1
        );

        $this->assertEquals(30, $result['from']->quantity);
        $this->assertEquals(20, $result['to']->quantity);
    }

    public function test_transfer_stock_throws_on_insufficient_stock(): void
    {
        $branch = $this->warehouse->branch;
        $warehouse2 = Warehouse::create([
            'branch_id' => $branch->id,
            'name' => 'Almacén 2',
            'active' => true,
        ]);

        $this->service->addStock($this->variant, $this->warehouse, 10);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->service->transferStock(
            $this->variant,
            $this->warehouse,
            $warehouse2,
            20,
            1
        );
    }

    public function test_check_minimum_detects_low_stock(): void
    {
        $this->service->addStock($this->variant, $this->warehouse, 3);

        $this->assertTrue($this->service->checkMinimum($this->variant, $this->warehouse));
    }

    public function test_check_minimum_passes_when_above(): void
    {
        $this->service->addStock($this->variant, $this->warehouse, 50);

        $this->assertFalse($this->service->checkMinimum($this->variant, $this->warehouse));
    }

    public function test_get_total_stock_sums_all_warehouses(): void
    {
        $branch = $this->warehouse->branch;
        $warehouse2 = Warehouse::create([
            'branch_id' => $branch->id,
            'name' => 'Almacén 2',
            'active' => true,
        ]);

        $this->service->addStock($this->variant, $this->warehouse, 30);
        $this->service->addStock($this->variant, $warehouse2, 20);

        $total = $this->service->getTotalStock($this->variant);
        $this->assertEquals(50, $total);
    }
}
