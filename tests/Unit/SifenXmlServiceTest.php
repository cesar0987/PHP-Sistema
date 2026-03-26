<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\SaleService;
use App\Services\SifenXmlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del SifenXmlService — validación de la estructura XML SIFEN v150.
 *
 * Cubre Plan_Sifen_XML.md ítem 7: XML bien formado, namespace correcto,
 * CDC en atributo Id del <DE>, secciones obligatorias, y cálculo de IVA Paraguay.
 */
class SifenXmlServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SifenXmlService $service;

    protected Sale $sale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SifenXmlService::class);

        $company = Company::create([
            'name'    => 'Ferretería Test SA',
            'ruc'     => '80000001-5',
            'address' => 'Av. Principal 123',
            'phone'   => '021-123-456',
            'email'   => 'test@ferreteria.com',
        ]);

        $branch = Branch::create([
            'company_id'         => $company->id,
            'name'               => 'Sucursal Central',
            'timbrado_number'    => '12345678',
            'establishment_code' => '001',
            'dispatch_point'     => '001',
            'timbrado_start_date'=> now()->subYear()->toDateString(),
        ]);

        $warehouse = Warehouse::create([
            'branch_id' => $branch->id,
            'name'      => 'Depósito',
        ]);

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $this->actingAs($user);

        $category = Category::create(['name' => 'Materiales']);
        $product  = Product::create([
            'name'           => 'Cemento 50kg',
            'category_id'    => $category->id,
            'sale_price'     => 55000,
            'cost_price'     => 40000,
            'tax_percentage' => 10,
            'active'         => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'CEM-001',
        ]);

        app(InventoryService::class)->addStock($variant, $warehouse, 100);

        $this->sale = app(SaleService::class)->createSale([
            'branch_id'    => $branch->id,
            'warehouse_id' => $warehouse->id,
            'items'        => [
                ['product_variant_id' => $variant->id, 'quantity' => 2, 'price' => 55000],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 110000],
            ],
        ]);

        // Set invoice number for CDC generation (document_type defaults to 'ticket')
        $this->sale->update([
            'invoice_number' => '0000001',
        ]);
    }

    public function test_generate_returns_valid_xml(): void
    {
        $xml = $this->service->generate($this->sale);

        $doc = new \DOMDocument;
        $loaded = @$doc->loadXML($xml);

        $this->assertTrue($loaded, 'El XML generado no es válido (bien formado).');
    }

    public function test_xml_has_correct_namespace(): void
    {
        $xml = $this->service->generate($this->sale);

        $this->assertStringContainsString(
            'http://ekuatia.set.gov.py/sifen/xsd',
            $xml
        );
    }

    public function test_xml_root_element_is_rDE(): void
    {
        $xml = $this->service->generate($this->sale);

        $doc = new \DOMDocument;
        $doc->loadXML($xml);

        $this->assertEquals('rDE', $doc->documentElement->localName);
    }

    public function test_xml_has_DE_element_with_cdc_in_id(): void
    {
        $xml = $this->service->generate($this->sale);

        $doc = new \DOMDocument;
        $doc->loadXML($xml);

        $deElements = $doc->getElementsByTagName('DE');
        $this->assertGreaterThan(0, $deElements->length, '<DE> no encontrado en el XML.');

        $de    = $deElements->item(0);
        $idVal = $de->getAttribute('Id');

        // CDC debe tener exactamente 44 dígitos
        $this->assertMatchesRegularExpression('/^\d{44}$/', $idVal, "El CDC en Id no tiene 44 dígitos: {$idVal}");
    }

    public function test_xml_contains_required_sections(): void
    {
        $xml = $this->service->generate($this->sale);

        $doc = new \DOMDocument;
        $doc->loadXML($xml);

        $requiredTags = ['gOpeDE', 'gTimb', 'gDatGralOpe', 'gDtipDE', 'gTotSub'];

        foreach ($requiredTags as $tag) {
            $elements = $doc->getElementsByTagName($tag);
            $this->assertGreaterThan(
                0,
                $elements->length,
                "Sección requerida <{$tag}> no encontrada en el XML."
            );
        }
    }

    public function test_xml_contains_company_ruc(): void
    {
        $xml = $this->service->generate($this->sale);

        // RUC without DV digit
        $this->assertStringContainsString('<dRucEm>80000001</dRucEm>', $xml);
    }

    public function test_xml_contains_timbrado(): void
    {
        $xml = $this->service->generate($this->sale);

        $this->assertStringContainsString('<dNumTim>12345678</dNumTim>', $xml);
    }

    public function test_xml_gTotSub_reflects_iva_included_totals(): void
    {
        // Sale: 2 × 55000 = 110000 subtotal (IVA 10% incluido)
        // IVA = 110000 × 10/110 = 10000
        // total = 110000
        $xml = $this->service->generate($this->sale);

        $this->assertStringContainsString('<dTotOpe>110000</dTotOpe>', $xml);
        $this->assertStringContainsString('<dTotGralOpe>110000</dTotGralOpe>', $xml);
        $this->assertStringContainsString('<dIVA10>10000</dIVA10>', $xml);
    }

    public function test_xml_gCamItem_contains_product_name(): void
    {
        $xml = $this->service->generate($this->sale);

        $this->assertStringContainsString('CEMENTO 50KG', $xml);
    }

    public function test_xml_gCamItem_unit_price_correct(): void
    {
        // dPUniProSer = price per unit = 55000
        $xml = $this->service->generate($this->sale);

        $this->assertStringContainsString('<dPUniProSer>55000</dPUniProSer>', $xml);
    }

    public function test_xml_payment_condition_is_contado_for_cash_sale(): void
    {
        $xml = $this->service->generate($this->sale);

        $this->assertStringContainsString('<iCondOpe>1</iCondOpe>', $xml);
        $this->assertStringContainsString('<dDCondOpe>Contado</dDCondOpe>', $xml);
    }

    public function test_xml_consumer_final_uses_anonymous_receptor(): void
    {
        // Sale with no customer → consumidor final
        $xml = $this->service->generate($this->sale);

        // When no customer is assigned, iTiOpe=2 (sin documento)
        $this->assertStringContainsString('<iTiOpe>2</iTiOpe>', $xml);
    }

    public function test_xml_credit_sale_has_payment_condition_credito(): void
    {
        $this->sale->update([
            'condition'      => 'credito',
            'credit_due_date'=> now()->addDays(30),
        ]);

        $xml = $this->service->generate($this->sale->fresh());

        $this->assertStringContainsString('<iCondOpe>2</iCondOpe>', $xml);
        $this->assertStringContainsString('<dDCondOpe>Crédito</dDCondOpe>', $xml);
    }

    public function test_xml_contains_qr_url(): void
    {
        $xml = $this->service->generate($this->sale);

        // dCarQR should contain a URL with the QR parameters
        $this->assertStringContainsString('<dCarQR>', $xml);
        $this->assertMatchesRegularExpression('/<dCarQR>https?:\/\/.+<\/dCarQR>/', $xml);
    }

    public function test_xml_version_is_150(): void
    {
        $xml = $this->service->generate($this->sale);

        $this->assertStringContainsString('<dVerFor>150</dVerFor>', $xml);
    }
}
