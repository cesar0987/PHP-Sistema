<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CreditService;
use App\Services\InventoryService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CreditService $service;

    protected Customer $customer;

    protected Branch $branch;

    protected Warehouse $warehouse;

    protected ProductVariant $variant;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CreditService::class);

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

        $this->customer = Customer::create([
            'name' => 'Cliente Test',
            'document' => '1234567',
            'is_credit_enabled' => true,
            'credit_limit' => 500000,
            'current_balance' => 0,
        ]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed stock
        app(InventoryService::class)->addStock($this->variant, $this->warehouse, 100);
    }

    public function test_record_sale_payment_creates_payment(): void
    {
        $sale = app(SaleService::class)->createSale([
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 2,
                    'price' => 10000,
                ],
            ],
        ]);

        // SaleService no pasa payment_method, se fija manualmente
        $sale->update(['payment_method' => 'credito']);

        // Registrar un pago parcial
        $this->service->recordSalePayment($sale, 10000);

        $this->customer->refresh();
        $this->assertCount(1, $this->customer->payments);
        $this->assertEquals(10000, $this->customer->payments->first()->amount);
    }

    public function test_update_customer_balance_calculates_correctly(): void
    {
        // Crear venta — Paraguay IVA incluido: total = 2×10000 = 20000
        $sale = app(SaleService::class)->createSale([
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity' => 2,
                    'price' => 10000,
                ],
            ],
        ]);

        // Marcar como venta a crédito
        $sale->update(['payment_method' => 'credito']);

        // Registrar pago parcial de 5000
        $this->service->recordSalePayment($sale, 5000);

        // Actualizar balance del cliente
        $this->service->updateCustomerBalance($this->customer);

        $this->customer->refresh();

        // Paraguay: IVA incluido en precio. Total = 2×10000 = 20000. Pago = 5000. Deuda = 15000.
        $this->assertEquals(15000, $this->customer->current_balance);
    }
}
