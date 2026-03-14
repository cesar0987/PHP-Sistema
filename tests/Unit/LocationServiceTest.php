<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\LocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LocationService;
    }

    // ── Conversión numérica (puro, sin BD) ──────────────────────

    public function test_number_to_letters_single(): void
    {
        $this->assertEquals('A', $this->service->numberToLetters(1));
        $this->assertEquals('B', $this->service->numberToLetters(2));
        $this->assertEquals('Z', $this->service->numberToLetters(26));
    }

    public function test_number_to_letters_multi(): void
    {
        $this->assertEquals('AA', $this->service->numberToLetters(27));
        $this->assertEquals('AB', $this->service->numberToLetters(28));
        $this->assertEquals('AZ', $this->service->numberToLetters(52));
        $this->assertEquals('ZZ', $this->service->numberToLetters(702));
        $this->assertEquals('AAA', $this->service->numberToLetters(703));
    }

    public function test_letters_to_number(): void
    {
        $this->assertEquals(1, $this->service->lettersToNumber('A'));
        $this->assertEquals(26, $this->service->lettersToNumber('Z'));
        $this->assertEquals(27, $this->service->lettersToNumber('AA'));
        $this->assertEquals(702, $this->service->lettersToNumber('ZZ'));
        $this->assertEquals(703, $this->service->lettersToNumber('AAA'));
    }

    public function test_roundtrip_conversion(): void
    {
        // numberToLetters(lettersToNumber(X)) === X
        foreach (['A', 'M', 'Z', 'AA', 'AZ', 'BA', 'ZZ', 'AAA'] as $letters) {
            $this->assertEquals(
                $letters,
                $this->service->numberToLetters($this->service->lettersToNumber($letters)),
                "Roundtrip failed for {$letters}"
            );
        }
    }

    // ── Tests con BD ────────────────────────────────────────────

    public function test_create_location_generates_aisles_and_shelves(): void
    {
        $company = Company::create(['name' => 'Test Co', 'ruc' => '123']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Test']);
        $warehouse = Warehouse::create([
            'branch_id' => $branch->id,
            'name' => 'Almacén Test',
            'is_default' => true,
            'active' => true,
        ]);

        $aisle = $this->service->createLocation($warehouse, [
            'create_shelves' => true,
            'num_shelves' => 2,
        ]);

        $this->assertEquals('A', $aisle->code);
        $this->assertCount(2, $aisle->shelves);

        // Cada estante tiene 3 filas × 4 niveles
        $firstShelf = $aisle->shelves->first()->load('rows.levels');
        $this->assertCount(3, $firstShelf->rows);
        $this->assertCount(4, $firstShelf->rows->first()->levels);
    }

    public function test_assign_location_creates_product_location(): void
    {
        $company = Company::create(['name' => 'Test Co', 'ruc' => '123']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Test']);
        $warehouse = Warehouse::create([
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
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'TEST-001',
        ]);

        // Crear pasillo con estantes
        $aisle = $this->service->createLocation($warehouse, [
            'create_shelves' => true,
            'num_shelves' => 1,
        ]);

        $level = $aisle->shelves->first()->rows->first()->levels->first();

        $location = $this->service->assignLocation($variant, $level, 10);

        $this->assertNotNull($location);
        $this->assertEquals(10, $location->quantity);
        $this->assertEquals($variant->id, $location->product_variant_id);
        $this->assertEquals($level->id, $location->shelf_level_id);

        // Asignar de nuevo incrementa cantidad
        $this->service->assignLocation($variant, $level, 5);
        $this->assertEquals(15, $location->fresh()->quantity);
    }
}
