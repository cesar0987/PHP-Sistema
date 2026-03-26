<?php

namespace App\Services;

use App\Models\Sale;

/**
 * Servicio de generación del CDC (Código de Control) SIFEN v150.
 *
 * El CDC es un identificador único de 44 dígitos que identifica cada
 * Documento Electrónico emitido ante la SET (Paraguay).
 */
class SifenCdcService
{
    /** Mapa de tipo de documento a código numérico SIFEN. */
    private const TIPO_DOCUMENTO = [
        'factura'       => '01',
        'autofactura'   => '04',
        'nota_credito'  => '05',
        'nota_debito'   => '06',
        'nota_remision' => '07',
    ];

    /**
     * Genera un código de seguridad aleatorio de 9 dígitos (dCodSeg).
     */
    public function generateSecurityCode(): string
    {
        return str_pad((string) random_int(1, 999_999_999), 9, '0', STR_PAD_LEFT);
    }

    /**
     * Genera el CDC completo de 44 dígitos a partir de una Venta.
     *
     * @param  Sale    $sale    La venta con relaciones branch.company cargadas.
     * @param  string  $codSeg  Código de seguridad de 9 dígitos generado previamente.
     * @return string  CDC de 44 caracteres.
     */
    public function generateFromSale(Sale $sale, string $codSeg): string
    {
        $company = $sale->branch->company;

        $tipoDoc = self::TIPO_DOCUMENTO[$sale->document_type ?? 'factura'] ?? '01';

        // RUC sin dígito verificador
        $rucParts = explode('-', $company->ruc ?? '');
        $ruc = $rucParts[0];
        $dvRuc = $company->ruc_dv ?? ($rucParts[1] ?? '0');

        $cdcBase = $this->buildBase(
            tipoDoc:       $tipoDoc,
            ruc:           $ruc,
            dvRuc:         $dvRuc,
            establecimiento: str_pad($sale->branch->establishment_code ?? '001', 3, '0', STR_PAD_LEFT),
            puntoExp:      str_pad($sale->branch->dispatch_point ?? '001', 3, '0', STR_PAD_LEFT),
            numeroDoc:     str_pad($sale->invoice_number ?? '0000001', 7, '0', STR_PAD_LEFT),
            sisFact:       (string) config('sifen.issuer.system_facturation', '1'),
            fechaEmision:  $sale->sale_date->format('Ymd'),
            tipoEmision:   (string) config('sifen.issuer.tipo_emision', '1'),
            codSeg:        $codSeg,
        );

        $dv = $this->calculateCheckDigit($cdcBase);

        return $cdcBase . $dv;
    }

    /**
     * Construye la cadena base de 43 caracteres del CDC (sin dígito verificador).
     */
    public function buildBase(
        string $tipoDoc,
        string $ruc,
        string $dvRuc,
        string $establecimiento,
        string $puntoExp,
        string $numeroDoc,
        string $sisFact,
        string $fechaEmision,
        string $tipoEmision,
        string $codSeg,
    ): string {
        return str_pad($tipoDoc, 2, '0', STR_PAD_LEFT)
            . str_pad($ruc, 8, '0', STR_PAD_LEFT)
            . $dvRuc
            . str_pad($establecimiento, 3, '0', STR_PAD_LEFT)
            . str_pad($puntoExp, 3, '0', STR_PAD_LEFT)
            . str_pad($numeroDoc, 7, '0', STR_PAD_LEFT)
            . $sisFact
            . $fechaEmision
            . $tipoEmision
            . str_pad($codSeg, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Calcula el dígito verificador del CDC usando módulo 11.
     *
     * Algoritmo (SIFEN v150, Anexo):
     * - Se recorren los 43 dígitos de derecha a izquierda.
     * - Se asignan pesos cíclicos 2..9 de derecha a izquierda.
     * - suma = Σ(dígito * peso)
     * - resto = suma % 11
     * - Si resto = 0 → 0; si resto = 1 → 1; sino → 11 - resto.
     *
     * @param  string  $cdcBase  Los primeros 43 caracteres del CDC.
     * @return int     Dígito verificador (0–9).
     */
    public function calculateCheckDigit(string $cdcBase): int
    {
        $sum = 0;
        $weight = 2;

        for ($i = strlen($cdcBase) - 1; $i >= 0; $i--) {
            $sum += (int) $cdcBase[$i] * $weight;
            $weight = ($weight === 9) ? 2 : $weight + 1;
        }

        $remainder = $sum % 11;

        return match (true) {
            $remainder === 0 => 0,
            $remainder === 1 => 1,
            default          => 11 - $remainder,
        };
    }
}
