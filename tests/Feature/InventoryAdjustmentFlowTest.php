<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAdjustmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Warehouse $warehouse;
    protected ProductVariant $variant;
    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $company        = Company::create(['name' => 'Empresa Test', 'ruc' => '80000001']);
        $branch         = Branch::create(['company_id' => $company->id, 'name' => 'Central']);
        $this->user     = User::factory()->create(['branch_id' => $branch->id]);
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'name'      => 'Depósito',
            'branch_id' => $branch->id,
        ]);

        $category       = Category::create(['name' => 'Materiales']);
        $product        = Product::create([
            'name'           => 'Cemento',
            'category_id'    => $category->id,
            'sale_price'     => 80000,
            'cost_price'     => 60000,
            'tax_percentage' => 10,
            'active'         => true,
        ]);
        $this->variant  = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'CEM-001',
        ]);

        $this->inventoryService = app(InventoryService::class);
        $this->inventoryService->addStock($this->variant, $this->warehouse, 20);
    }

    public function test_pending_adjustment_does_not_change_stock(): void
    {
        $adjustment = InventoryAdjustment::create([
            'user_id'      => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'reason'       => 'Conteo físico',
            'status'       => 'pending',
        ]);

        InventoryAdjustmentItem::create([
            'adjustment_id'      => $adjustment->id,
            'product_variant_id' => $this->variant->id,
            'quantity_before'    => 20,
            'quantity_after'     => 15,
        ]);

        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(20, $stock->quantity, 'Stock no debe cambiar con ajuste pendiente.');

        $this->assertDatabaseMissing('stock_movements', [
            'product_variant_id' => $this->variant->id,
            'type'               => 'adjustment',
        ]);
    }

    public function test_approved_adjustment_updates_stock_via_process(): void
    {
        $adjustment = InventoryAdjustment::create([
            'user_id'      => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'reason'       => 'Conteo físico',
            'status'       => 'pending',
        ]);

        InventoryAdjustmentItem::create([
            'adjustment_id'      => $adjustment->id,
            'product_variant_id' => $this->variant->id,
            'quantity_before'    => 20,
            'quantity_after'     => 15,
        ]);

        $this->inventoryService->processAdjustment($adjustment);

        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(15, $stock->quantity, 'Stock debe reflejar la cantidad ajustada.');

        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $this->warehouse->id,
            'type'               => 'adjustment',
            'quantity'           => -5,
        ]);
    }

    public function test_adjust_stock_service_method_creates_adjustment_and_updates_stock(): void
    {
        $adjustment = $this->inventoryService->adjustStock(
            $this->variant,
            $this->warehouse,
            18,
            'Corrección por merma',
            $this->user->id
        );

        $this->assertInstanceOf(InventoryAdjustment::class, $adjustment);
        $this->assertEquals('approved', $adjustment->status);

        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(18, $stock->quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $this->variant->id,
            'warehouse_id'       => $this->warehouse->id,
            'type'               => 'adjustment',
            'quantity'           => -2,
        ]);
    }

    public function test_process_adjustment_is_idempotent(): void
    {
        $adjustment = InventoryAdjustment::create([
            'user_id'      => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'reason'       => 'Test idempotencia',
            'status'       => 'pending',
        ]);

        InventoryAdjustmentItem::create([
            'adjustment_id'      => $adjustment->id,
            'product_variant_id' => $this->variant->id,
            'quantity_before'    => 20,
            'quantity_after'     => 10,
        ]);

        // Primer procesamiento
        $this->inventoryService->processAdjustment($adjustment);

        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(10, $stock->quantity);

        $movementsAfterFirst = StockMovement::where('product_variant_id', $this->variant->id)
            ->where('type', 'adjustment')
            ->count();

        // Segundo procesamiento (simula doble-click o bug)
        $this->inventoryService->processAdjustment($adjustment);

        $movementsAfterSecond = StockMovement::where('product_variant_id', $this->variant->id)
            ->where('type', 'adjustment')
            ->count();

        // No se debe crear un movimiento duplicado (diferencia 0 en segundo proceso)
        $this->assertEquals(
            $movementsAfterFirst,
            $movementsAfterSecond,
            'El segundo processAdjustment no debe crear movimientos si la diferencia es 0.'
        );
    }

    public function test_rejected_adjustment_does_not_change_stock(): void
    {
        $adjustment = InventoryAdjustment::create([
            'user_id'      => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'reason'       => 'Test rechazo',
            'status'       => 'rejected',
        ]);

        InventoryAdjustmentItem::create([
            'adjustment_id'      => $adjustment->id,
            'product_variant_id' => $this->variant->id,
            'quantity_before'    => 20,
            'quantity_after'     => 5,
        ]);

        // No se llama processAdjustment porque fue rechazado
        $stock = $this->variant->stocks()->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(20, $stock->quantity, 'Ajuste rechazado no debe modificar stock.');
    }
}
