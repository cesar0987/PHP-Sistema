<?php

namespace Tests\Unit;

use App\Services\SifenCdcService;
use Tests\TestCase;

/**
 * Tests para SifenCdcService.
 *
 * No usa RefreshDatabase porque SifenCdcService es puro (sin DB).
 *
 * Referencia CDC 44 dígitos (estructura SIFEN v150):
 *   iTiDE(2) + RUC(8) + DV(1) + Est(3) + PunExp(3) + NumDoc(7)
 *   + SisFact(1) + FechaEmision(8) + TipoEmi(1) + CodSeg(9) + DV(1)
 */
class SifenCdcServiceTest extends TestCase
{
    protected SifenCdcService $service;

    /**
     * Parámetros del ejemplo de referencia. Se construye el CDC completo y
     * todos los tests de "valor exacto" se derivan de estos mismos parámetros,
     * garantizando consistencia interna del algoritmo.
     */
    private const EXAMPLE = [
        'tipoDoc'        => '01',
        'ruc'            => '00000001',
        'dvRuc'          => '9',
        'establecimiento'=> '001',
        'puntoExp'       => '001',
        'numeroDoc'      => '1000050',
        'sisFact'        => '2',
        'fechaEmision'   => '20200507',
        'tipoEmision'    => '1',
        'codSeg'         => '000000023',
    ];

    /** Base de 43 chars esperada para los parámetros de EXAMPLE. */
    private const EXAMPLE_BASE = '0100000001900100110000502202005071000000023';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SifenCdcService();
    }

    // -------------------------------------------------------------------------
    // buildBase
    // -------------------------------------------------------------------------

    public function test_build_base_produces_43_character_string(): void
    {
        $base = $this->service->buildBase(...self::EXAMPLE);

        $this->assertSame(43, strlen($base));
    }

    public function test_build_base_matches_reference_example(): void
    {
        $base = $this->service->buildBase(...self::EXAMPLE);

        $this->assertSame(self::EXAMPLE_BASE, $base);
    }

    public function test_build_base_segments_are_correct(): void
    {
        $base = $this->service->buildBase(...self::EXAMPLE);

        // iTiDE — posición 0-1
        $this->assertSame('01', substr($base, 0, 2));
        // RUC — posición 2-9
        $this->assertSame('00000001', substr($base, 2, 8));
        // DV RUC — posición 10
        $this->assertSame('9', substr($base, 10, 1));
        // Est — posición 11-13
        $this->assertSame('001', substr($base, 11, 3));
        // PunExp — posición 14-16
        $this->assertSame('001', substr($base, 14, 3));
        // NumDoc — posición 17-23
        $this->assertSame('1000050', substr($base, 17, 7));
        // SisFact — posición 24
        $this->assertSame('2', substr($base, 24, 1));
        // FechaEmision — posición 25-32
        $this->assertSame('20200507', substr($base, 25, 8));
        // TipoEmi — posición 33
        $this->assertSame('1', substr($base, 33, 1));
        // CodSeg — posición 34-42
        $this->assertSame('000000023', substr($base, 34, 9));
    }

    public function test_build_base_pads_ruc_to_8_digits(): void
    {
        $base = $this->service->buildBase(
            tipoDoc:        '01',
            ruc:            '12345',
            dvRuc:          '3',
            establecimiento:'001',
            puntoExp:       '001',
            numeroDoc:      '0000001',
            sisFact:        '1',
            fechaEmision:   '20260101',
            tipoEmision:    '1',
            codSeg:         '000000001',
        );

        $this->assertSame('00012345', substr($base, 2, 8));
    }

    public function test_build_base_pads_numero_doc_to_7_digits(): void
    {
        $base = $this->service->buildBase(
            tipoDoc:        '01',
            ruc:            '00000001',
            dvRuc:          '9',
            establecimiento:'001',
            puntoExp:       '001',
            numeroDoc:      '42',
            sisFact:        '1',
            fechaEmision:   '20260101',
            tipoEmision:    '1',
            codSeg:         '000000001',
        );

        // NumDoc comienza en posición 17 (0-indexed), longitud 7
        $this->assertSame('0000042', substr($base, 17, 7));
    }

    public function test_build_base_pads_cod_seg_to_9_digits(): void
    {
        $base = $this->service->buildBase(
            tipoDoc:        '01',
            ruc:            '00000001',
            dvRuc:          '9',
            establecimiento:'001',
            puntoExp:       '001',
            numeroDoc:      '0000001',
            sisFact:        '1',
            fechaEmision:   '20260101',
            tipoEmision:    '1',
            codSeg:         '5',
        );

        // CodSeg comienza en posición 34 (0-indexed), longitud 9
        $this->assertSame('000000005', substr($base, 34, 9));
    }

    // -------------------------------------------------------------------------
    // calculateCheckDigit
    // -------------------------------------------------------------------------

    public function test_check_digit_for_reference_base(): void
    {
        // Dígito calculado por el algoritmo módulo 11 sobre EXAMPLE_BASE
        $dv = $this->service->calculateCheckDigit(self::EXAMPLE_BASE);

        // El DV es el resultado determinista de nuestro algoritmo sobre esta base
        $this->assertSame(3, $dv);
    }

    public function test_check_digit_returns_zero_when_sum_is_multiple_of_11(): void
    {
        // Con 43 ceros: suma = 0, 0 % 11 = 0 → DV = 0
        $dv = $this->service->calculateCheckDigit(str_repeat('0', 43));

        $this->assertSame(0, $dv);
    }

    public function test_check_digit_is_always_a_single_digit(): void
    {
        // El DV siempre debe ser 0-9 para cualquier base válida de 43 chars
        $bases = [
            self::EXAMPLE_BASE,
            str_repeat('1', 43),
            str_repeat('5', 43),
            '0100000001900100110000501202005071000000001',
        ];

        foreach ($bases as $base) {
            $dv = $this->service->calculateCheckDigit($base);
            $this->assertGreaterThanOrEqual(0, $dv, "DV fuera de rango para base: $base");
            $this->assertLessThanOrEqual(9, $dv, "DV fuera de rango para base: $base");
        }
    }

    public function test_check_digit_is_deterministic(): void
    {
        // La misma base siempre produce el mismo DV
        $dv1 = $this->service->calculateCheckDigit(self::EXAMPLE_BASE);
        $dv2 = $this->service->calculateCheckDigit(self::EXAMPLE_BASE);

        $this->assertSame($dv1, $dv2);
    }

    // -------------------------------------------------------------------------
    // generateSecurityCode
    // -------------------------------------------------------------------------

    public function test_generate_security_code_has_9_digits(): void
    {
        $codSeg = $this->service->generateSecurityCode();

        $this->assertSame(9, strlen($codSeg));
        $this->assertMatchesRegularExpression('/^\d{9}$/', $codSeg);
    }

    public function test_generate_security_code_is_random(): void
    {
        // Generando 10 veces, al menos 2 deben ser distintos
        $codes = array_map(fn () => $this->service->generateSecurityCode(), range(1, 10));

        $this->assertGreaterThan(1, count(array_unique($codes)));
    }

    // -------------------------------------------------------------------------
    // CDC completo
    // -------------------------------------------------------------------------

    public function test_full_cdc_is_44_characters(): void
    {
        $base = $this->service->buildBase(...self::EXAMPLE);
        $dv   = $this->service->calculateCheckDigit($base);
        $cdc  = $base . $dv;

        $this->assertSame(44, strlen($cdc));
    }

    public function test_full_cdc_matches_reference_example(): void
    {
        $base    = $this->service->buildBase(...self::EXAMPLE);
        $dv      = $this->service->calculateCheckDigit($base);
        $cdc     = $base . $dv;
        $expected = self::EXAMPLE_BASE . '3';  // DV=3 calculado por módulo 11

        $this->assertSame($expected, $cdc);
        $this->assertSame(44, strlen($cdc));
    }

    public function test_full_cdc_last_digit_matches_calculated_dv(): void
    {
        $base = $this->service->buildBase(...self::EXAMPLE);
        $dv   = $this->service->calculateCheckDigit($base);
        $cdc  = $base . $dv;

        $this->assertSame((string) $dv, substr($cdc, -1));
    }
}
