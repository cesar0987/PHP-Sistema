<?php

namespace App\Services;

use App\Models\Sale;

/**
 * Servicio de generación de la URL del Código QR SIFEN v150.
 *
 * Construye la URL de consulta QR según el Manual Técnico de la SET,
 * incluyendo el hash de seguridad (cHashQR) calculado con el CSC.
 */
class SifenQrService
{
    /**
     * Genera la URL completa del código QR para un Documento Electrónico.
     *
     * Estructura de la URL:
     *   {base_url}nVersion=150&Id={cdc}&dFeEmiDE={hex(fecha)}&dRucRec={ruc}&
     *   dTotGralOpe={total}&dTotIVA={iva}&cItems={n}&DigestValue={hex(digest)}&
     *   IdCSC={id}&cHashQR={sha256(params+csc_val)}
     *
     * @param  Sale    $sale         La venta con totales calculados.
     * @param  string  $cdc          CDC de 44 dígitos del documento.
     * @param  string  $digestValue  Valor DigestValue en base64 de la firma XML (vacío si aún no firmado).
     * @return string  URL del QR lista para insertar en el XML.
     */
    public function generate(Sale $sale, string $cdc, string $digestValue = ''): string
    {
        $baseUrl = config('sifen.qr.base_url');
        $cscId   = config('sifen.qr.csc_id');
        $cscVal  = config('sifen.qr.csc_val');

        // dFeEmiDE se codifica en hexadecimal (bytes del string ISO 8601)
        $fechaStr    = $sale->sale_date->format('Y-m-d\TH:i:s');
        $fechaHex    = bin2hex($fechaStr);

        // DigestValue base64 → hex (vacío si no hay firma aún)
        $digestHex = $digestValue !== ''
            ? bin2hex(base64_decode($digestValue))
            : '';

        $rucRec = $this->resolveRucRec($sale);

        $params = [
            'nVersion'      => config('sifen.version', '150'),
            'Id'            => $cdc,
            'dFeEmiDE'      => $fechaHex,
            'dRucRec'       => $rucRec,
            'dTotGralOpe'   => (int) round((float) $sale->total),
            'dTotIVA'       => (int) round((float) $sale->tax),
            'cItems'        => $sale->items()->count(),
            'DigestValue'   => $digestHex,
            'IdCSC'         => $cscId,
        ];

        // Construir query string sin cHashQR
        $queryString = http_build_query($params);

        // cHashQR = SHA256(queryString + cscVal)
        $cHashQR = hash('sha256', $queryString . $cscVal);

        return $baseUrl . $queryString . '&cHashQR=' . $cHashQR;
    }

    /**
     * Determina el RUC del receptor para el QR.
     * Si el cliente tiene documento (RUC o CI), se usa; sino "0".
     */
    private function resolveRucRec(Sale $sale): string
    {
        if (! $sale->customer_id || ! $sale->customer?->document) {
            return '0';
        }

        // Retornar solo la parte numérica (sin DV)
        return explode('-', $sale->customer->document)[0];
    }
}
