<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
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
    protected ProductVariant $variant;
    protected PurchaseService $purchaseService;

    protected function setUp(): void
    {
        parent::setUp();

        $company       = Company::create(['name' => 'Mi Empresa', 'ruc' => '80000001']);
        $this->branch  = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal 1']);
        $this->user    = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'name'      => 'Depósito Central',
            'branch_id' => $this->branch->id,
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Ferretería Proveedor SA',
            'ruc'  => '80012345-6',
        ]);

        $category      = Category::create(['name' => 'Tornillos']);
        $product       = Product::create([
            'name'           => 'Tornillo 10mm',
            'category_id'    => $category->id,
            'cost_price'     => 30000,
            'sale_price'     => 50000,
            'tax_percentage' => 10,
            'active'         => true,
        ]);
        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'TORN-01',
        ]);

        // Stock inicial = 10 unidades
        app(InventoryService::class)->addStock($this->variant, $this->warehouse, 10);

        $this->purchaseService = app(PurchaseService::class);
    }

    public function test_pending_purchase_does_not_add_stock(): void
    {
        $purchase = $this->purchaseService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'branch_id'     => $this->branch->id,
            'warehouse_id'  => $this->warehouse->id,
            'purchase_date' => now(),
            'status'        => 'pending',
            'items'         => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 50, 'cost' => 28000],
            ],
        ]);

        $this->assertEquals('pending', $purchase->status);

        // Stock no debe haber cambiado
        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(10, $stock->quantity);
    }

    public function test_receive_purchase_adds_stock(): void
    {
        $purchase = $this->purchaseService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'branch_id'     => $this->branch->id,
            'warehouse_id'  => $this->warehouse->id,
            'purchase_date' => now(),
            'status'        => 'pending',
            'items'         => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 50, 'cost' => 28000],
            ],
        ]);

        $this->purchaseService->receiveProducts($purchase);

        // Stock: 10 + 50 = 60
        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(60, $stock->quantity);
        $this->assertEquals('received', $purchase->fresh()->status);

        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $this->warehouse->id,
            'type'               => 'purchase',
            'quantity'           => 50,
        ]);
    }

    public function test_purchase_with_receive_products_adds_stock_immediately(): void
    {
        $this->purchaseService->createPurchase([
            'supplier_id'     => $this->supplier->id,
            'branch_id'       => $this->branch->id,
            'warehouse_id'    => $this->warehouse->id,
            'purchase_date'   => now(),
            'status'          => 'received',
            'receive_products'=> true,
            'items'           => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 20, 'cost' => 28000],
            ],
        ]);

        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(30, $stock->quantity);
    }

    public function test_cancel_purchase_changes_status(): void
    {
        $purchase = $this->purchaseService->createPurchase([
            'supplier_id'  => $this->supplier->id,
            'branch_id'    => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'purchase_date'=> now(),
            'status'       => 'pending',
            'items'        => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 10, 'cost' => 28000],
            ],
        ]);

        $this->purchaseService->cancelPurchase($purchase);

        $this->assertEquals('cancelled', $purchase->fresh()->status);

        // Stock no debe haber cambiado (no fue recibida)
        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(10, $stock->quantity);
    }
}
