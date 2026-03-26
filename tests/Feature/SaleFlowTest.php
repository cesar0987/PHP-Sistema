<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
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

class SaleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected CashRegister $cashRegister;
    protected ProductVariant $variant;
    protected SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();

        $company       = Company::create(['name' => 'Mi Empresa', 'ruc' => '80000001']);
        $this->branch  = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Principal']);
        $this->user    = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'name'      => 'Depósito Central',
            'branch_id' => $this->branch->id,
        ]);

        $this->cashRegister = CashRegister::create([
            'name'           => 'Caja 1',
            'branch_id'      => $this->branch->id,
            'user_id'        => $this->user->id,
            'status'         => 'open',
            'opening_amount' => 100000,
            'opened_at'      => now(),
        ]);

        $category      = Category::create(['name' => 'Herramientas']);
        $product       = Product::create([
            'name'           => 'Martillo',
            'category_id'    => $category->id,
            'sale_price'     => 50000,
            'cost_price'     => 30000,
            'tax_percentage' => 10,
            'active'         => true,
        ]);
        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'MART-001',
        ]);

        // Stock inicial = 10 unidades
        app(InventoryService::class)->addStock($this->variant, $this->warehouse, 10);

        $this->saleService = app(SaleService::class);
    }

    public function test_completed_sale_deducts_stock(): void
    {
        $this->saleService->createSale([
            'branch_id'       => $this->branch->id,
            'warehouse_id'    => $this->warehouse->id,
            'cash_register_id'=> $this->cashRegister->id,
            'status'          => 'completed',
            'items'           => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 2, 'price' => 50000],
            ],
        ]);

        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(8, $stock->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $this->warehouse->id,
            'type'               => 'sale',
            'quantity'           => -2,
        ]);
    }

    public function test_pending_sale_does_not_deduct_stock(): void
    {
        $sale = $this->saleService->createSale([
            'branch_id'    => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'pending',
            'items'        => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 2, 'price' => 50000],
            ],
        ]);

        $this->assertEquals('pending', $sale->status);

        // Stock no debe haberse modificado
        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(10, $stock->quantity);
    }

    public function test_approve_pending_sale_deducts_stock(): void
    {
        $sale = $this->saleService->createSale([
            'branch_id'    => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'pending',
            'items'        => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 2, 'price' => 50000],
            ],
        ]);

        $this->saleService->approveSale($sale, [
            ['method' => 'cash', 'amount' => 110000],
        ]);

        $this->assertEquals('completed', $sale->fresh()->status);

        // Stock descontado solo al aprobar
        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(8, $stock->quantity);
    }

    public function test_cancel_sale_returns_stock(): void
    {
        $sale = $this->saleService->createSale([
            'branch_id'    => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'completed',
            'items'        => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 3, 'price' => 50000],
            ],
        ]);

        // Stock: 10 - 3 = 7
        $this->saleService->cancelSale($sale);

        // Stock devuelto: 7 + 3 = 10
        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(10, $stock->quantity);
        $this->assertEquals('cancelled', $sale->fresh()->status);
    }

    public function test_create_sale_fails_with_insufficient_stock(): void
    {
        $this->expectException(\Exception::class);

        $this->saleService->createSale([
            'branch_id'    => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'completed',
            'items'        => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 999, 'price' => 50000],
            ],
        ]);
    }
}
