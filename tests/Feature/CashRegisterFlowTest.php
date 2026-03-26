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

/**
 * Tests del flujo de Caja Registradora.
 *
 * Cubre Kendall F3.2 — Tests de feature (flujos completos):
 * - Cierre de caja con totales calculados.
 */
class CashRegisterFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected CashRegister $cashRegister;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $company            = Company::create(['name' => 'Mi Empresa', 'ruc' => '80000001']);
        $this->branch       = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal 1']);
        $this->user         = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'name'      => 'Depósito',
            'branch_id' => $this->branch->id,
        ]);

        $this->cashRegister = CashRegister::create([
            'name'           => 'Caja Principal',
            'branch_id'      => $this->branch->id,
            'user_id'        => $this->user->id,
            'status'         => 'open',
            'opening_amount' => 50000,
            'opened_at'      => now()->subHours(2),
        ]);

        $category      = Category::create(['name' => 'Materiales']);
        $product       = Product::create([
            'name'           => 'Cemento 50kg',
            'category_id'    => $category->id,
            'sale_price'     => 80000,
            'cost_price'     => 60000,
            'tax_percentage' => 10,
            'active'         => true,
        ]);
        $this->variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CEM-001']);

        app(InventoryService::class)->addStock($this->variant, $this->warehouse, 100);
    }

    public function test_open_cash_register_has_open_status(): void
    {
        $this->assertEquals('open', $this->cashRegister->status);
        $this->assertNotNull($this->cashRegister->opened_at);
        $this->assertNull($this->cashRegister->closed_at);
    }

    public function test_close_cash_register_records_closing_data(): void
    {
        $this->cashRegister->update([
            'status'         => 'closed',
            'closing_amount' => 180000,
            'closed_at'      => now(),
        ]);

        $this->cashRegister->refresh();

        $this->assertEquals('closed', $this->cashRegister->status);
        $this->assertEquals(180000, $this->cashRegister->closing_amount);
        $this->assertNotNull($this->cashRegister->closed_at);
    }

    public function test_sales_are_linked_to_cash_register(): void
    {
        $saleService = app(SaleService::class);

        $saleService->createSale([
            'branch_id'        => $this->branch->id,
            'warehouse_id'     => $this->warehouse->id,
            'cash_register_id' => $this->cashRegister->id,
            'status'           => 'completed',
            'items'            => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 1, 'price' => 80000],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 88000],
            ],
        ]);

        $this->assertCount(1, $this->cashRegister->fresh()->sales);
    }

    public function test_cash_register_total_from_completed_sales(): void
    {
        $saleService = app(SaleService::class);

        // Dos ventas completadas en esta caja
        $saleService->createSale([
            'branch_id'        => $this->branch->id,
            'warehouse_id'     => $this->warehouse->id,
            'cash_register_id' => $this->cashRegister->id,
            'status'           => 'completed',
            'items'            => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 1, 'price' => 80000],
            ],
        ]);

        $saleService->createSale([
            'branch_id'        => $this->branch->id,
            'warehouse_id'     => $this->warehouse->id,
            'cash_register_id' => $this->cashRegister->id,
            'status'           => 'completed',
            'items'            => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 2, 'price' => 80000],
            ],
        ]);

        $totalVentas = $this->cashRegister->sales()
            ->where('status', 'completed')
            ->sum('total');

        // Venta 1: 80000 + 10% IVA = 88000
        // Venta 2: 160000 + 10% IVA = 176000
        // Total = 264000
        $this->assertEquals(264000, $totalVentas);
    }
}
