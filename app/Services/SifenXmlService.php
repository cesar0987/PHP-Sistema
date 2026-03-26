<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use DOMDocument;
use DOMElement;

/**
 * Servicio de generación de XML SIFEN v150.
 *
 * Construye el Documento Electrónico (rDE) completo en formato XML
 * según el Manual Técnico SIFEN v150 de la SET Paraguay.
 *
 * Flujo:
 *   1. Genera el código de seguridad (dCodSeg) y el CDC.
 *   2. Construye el árbol DOM del XML.
 *   3. Genera la URL del QR.
 *   4. (Pendiente) Aplica firma digital RSA-SHA256.
 */
class SifenXmlService
{
    /** Mapa de tipo de documento a [código, descripción]. */
    private const TIPOS_DOCUMENTO = [
        'factura'       => ['1', 'Factura electrónica'],
        'autofactura'   => ['4', 'Autofactura electrónica'],
        'nota_credito'  => ['5', 'Nota de crédito electrónica'],
        'nota_debito'   => ['6', 'Nota de débito electrónica'],
        'nota_remision' => ['7', 'Nota de remisión electrónica'],
    ];

    /** Mapa de método de pago a [código SIFEN, descripción]. */
    private const METODOS_PAGO = [
        'efectivo'        => ['1', 'Efectivo'],
        'cheque'          => ['2', 'Cheque'],
        'tarjeta_credito' => ['3', 'Tarjeta de crédito'],
        'tarjeta_debito'  => ['4', 'Tarjeta de débito'],
        'transferencia'   => ['5', 'Transferencia'],
        'giro'            => ['6', 'Giro'],
        'tarjeta'         => ['3', 'Tarjeta de crédito'],
    ];

    public function __construct(
        protected SifenCdcService $cdcService,
        protected SifenQrService $qrService,
    ) {}

    /**
     * Genera el XML completo del Documento Electrónico para una Venta.
     *
     * @param  Sale  $sale  La venta con relaciones branch.company, customer e items.productVariant.product cargadas.
     * @return string  XML formateado listo para firma digital.
     */
    public function generate(Sale $sale): string
    {
        $sale->loadMissing(['branch.company', 'customer', 'items.productVariant.product']);

        $codSeg = $this->cdcService->generateSecurityCode();
        $cdc    = $this->cdcService->generateFromSale($sale, $codSeg);
        $dvId   = (int) substr($cdc, -1);

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        // <rDE>
        $rDE = $doc->createElementNS('http://ekuatia.set.gov.py/sifen/xsd', 'rDE');
        $rDE->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'https://ekuatia.set.gov.py/sifen/xsd siRecepDE_v150.xsd'
        );
        $doc->appendChild($rDE);

        $this->text($doc, $rDE, 'dVerFor', config('sifen.version', '150'));

        // <DE>
        $DE = $doc->createElement('DE');
        $DE->setAttribute('Id', $cdc);
        $rDE->appendChild($DE);

        $this->text($doc, $DE, 'dDVId', (string) $dvId);
        $this->text($doc, $DE, 'dFecFirma', now()->format('Y-m-d\TH:i:s'));
        $this->text($doc, $DE, 'dSisFact', (string) config('sifen.issuer.system_facturation', '1'));

        $this->buildGOpeDE($doc, $DE, $codSeg);
        $this->buildGTimb($doc, $DE, $sale);
        $this->buildGDatGralOpe($doc, $DE, $sale);
        $this->buildGDtipDE($doc, $DE, $sale);
        $this->buildGTotSub($doc, $DE, $sale);

        // <gCamFuFD> — QR (sin DigestValue hasta que se firme)
        $qrUrl     = $this->qrService->generate($sale, $cdc);
        $gCamFuFD  = $doc->createElement('gCamFuFD');
        $rDE->appendChild($gCamFuFD);
        $this->text($doc, $gCamFuFD, 'dCarQR', $qrUrl);

        return $doc->saveXML();
    }

    // -------------------------------------------------------------------------
    // Secciones del XML
    // -------------------------------------------------------------------------

    private function buildGOpeDE(DOMDocument $doc, DOMElement $parent, string $codSeg): void
    {
        $gOpeDE = $doc->createElement('gOpeDE');
        $parent->appendChild($gOpeDE);

        $this->text($doc, $gOpeDE, 'iTipEmi',    (string) config('sifen.issuer.tipo_emision', '1'));
        $this->text($doc, $gOpeDE, 'dDesTipEmi', 'Normal');
        $this->text($doc, $gOpeDE, 'dCodSeg',    $codSeg);
        $this->text($doc, $gOpeDE, 'dInfoEmi',   (string) config('sifen.issuer.info_emi', ''));
        $this->text($doc, $gOpeDE, 'dInfoFisc',  (string) config('sifen.issuer.info_fisc', ''));
    }

    private function buildGTimb(DOMDocument $doc, DOMElement $parent, Sale $sale): void
    {
        [$tiDE, $desTiDE] = self::TIPOS_DOCUMENTO[$sale->document_type ?? 'factura'] ?? ['1', 'Factura electrónica'];

        $gTimb = $doc->createElement('gTimb');
        $parent->appendChild($gTimb);

        $branch = $sale->branch;

        $this->text($doc, $gTimb, 'iTiDE',    $tiDE);
        $this->text($doc, $gTimb, 'dDesTiDE', $desTiDE);
        $this->text($doc, $gTimb, 'dNumTim',  $branch->timbrado_number ?? ($sale->timbrado ?? ''));
        $this->text($doc, $gTimb, 'dEst',     str_pad($branch->establishment_code ?? '001', 3, '0', STR_PAD_LEFT));
        $this->text($doc, $gTimb, 'dPunExp',  str_pad($branch->dispatch_point ?? '001', 3, '0', STR_PAD_LEFT));
        $this->text($doc, $gTimb, 'dNumDoc',  str_pad($sale->invoice_number ?? '0000001', 7, '0', STR_PAD_LEFT));
        if ($branch->timbrado_start_date) {
            $this->text($doc, $gTimb, 'dFeIniT', $branch->timbrado_start_date->format('Y-m-d'));
        }
    }

    private function buildGDatGralOpe(DOMDocument $doc, DOMElement $parent, Sale $sale): void
    {
        $gDatGralOpe = $doc->createElement('gDatGralOpe');
        $parent->appendChild($gDatGralOpe);

        $this->text($doc, $gDatGralOpe, 'dFeEmiDE', $sale->sale_date->format('Y-m-d\TH:i:s'));

        $this->buildGOpeCom($doc, $gDatGralOpe);
        $this->buildGEmis($doc, $gDatGralOpe, $sale);
        $this->buildGDatRec($doc, $gDatGralOpe, $sale);
    }

    private function buildGOpeCom(DOMDocument $doc, DOMElement $parent): void
    {
        $gOpeCom = $doc->createElement('gOpeCom');
        $parent->appendChild($gOpeCom);

        $this->text($doc, $gOpeCom, 'iTipTra',    '1');
        $this->text($doc, $gOpeCom, 'dDesTipTra', 'Venta de mercadería');
        $this->text($doc, $gOpeCom, 'iTImp',      '1');
        $this->text($doc, $gOpeCom, 'dDesTImp',   'IVA');
        $this->text($doc, $gOpeCom, 'cMoneOpe',   'PYG');
        $this->text($doc, $gOpeCom, 'dDesMoneOpe','Guarani');
    }

    private function buildGEmis(DOMDocument $doc, DOMElement $parent, Sale $sale): void
    {
        $company = $sale->branch->company;

        $rucParts = explode('-', $company->ruc ?? '');
        $ruc      = $rucParts[0];
        $dvRuc    = $company->ruc_dv ?? ($rucParts[1] ?? '0');

        $gEmis = $doc->createElement('gEmis');
        $parent->appendChild($gEmis);

        $this->text($doc, $gEmis, 'dRucEm',    $ruc);
        $this->text($doc, $gEmis, 'dDVEmi',    $dvRuc);
        $this->text($doc, $gEmis, 'iTipCont',  (string) ($company->tipo_contribuyente ?? 2));
        $this->text($doc, $gEmis, 'cTipReg',   (string) ($company->tipo_regimen ?? 3));
        $this->text($doc, $gEmis, 'dNomEmi',   $company->name ?? '');
        $this->text($doc, $gEmis, 'dDirEmi',   $company->address ?? '');
        $this->text($doc, $gEmis, 'dNumCas',   $company->num_casa ?? '0');
        $this->text($doc, $gEmis, 'cDepEmi',   (string) ($company->departamento_code ?? ''));
        $this->text($doc, $gEmis, 'dDesDepEmi',$company->departamento_desc ?? '');
        $this->text($doc, $gEmis, 'cCiuEmi',   (string) ($company->ciudad_code ?? ''));
        $this->text($doc, $gEmis, 'dDesCiuEmi',$company->ciudad_desc ?? '');
        $this->text($doc, $gEmis, 'dTelEmi',   $company->phone ?? '');
        $this->text($doc, $gEmis, 'dEmailE',   $company->email ?? '');

        if ($company->actividad_eco_code) {
            $gActEco = $doc->createElement('gActEco');
            $gEmis->appendChild($gActEco);
            $this->text($doc, $gActEco, 'cActEco',    $company->actividad_eco_code);
            $this->text($doc, $gActEco, 'dDesActEco', $company->actividad_eco_desc ?? '');
        }
    }

    private function buildGDatRec(DOMDocument $doc, DOMElement $parent, Sale $sale): void
    {
        $customer = $sale->customer;
        $gDatRec  = $doc->createElement('gDatRec');
        $parent->appendChild($gDatRec);

        if ($customer?->document) {
            // Receptor con RUC/CI
            $docParts = explode('-', $customer->document);
            $rucRec   = $docParts[0];
            $dvRec    = $docParts[1] ?? null;

            $this->text($doc, $gDatRec, 'iNatRec',    '1');
            $this->text($doc, $gDatRec, 'iTiOpe',     '1');
            $this->text($doc, $gDatRec, 'cPaisRec',   'PRY');
            $this->text($doc, $gDatRec, 'dDesPaisRe', 'Paraguay');
            $this->text($doc, $gDatRec, 'iTiContRec', '1');
            $this->text($doc, $gDatRec, 'dRucRec',    $rucRec);
            if ($dvRec !== null) {
                $this->text($doc, $gDatRec, 'dDVRec', $dvRec);
            }
            $this->text($doc, $gDatRec, 'dNomRec', $customer->name);
            if ($customer->address) {
                $this->text($doc, $gDatRec, 'dDirRec',    $customer->address);
                $this->text($doc, $gDatRec, 'dNumCasRec', '0');
            }
            if ($customer->phone) {
                $this->text($doc, $gDatRec, 'dTelRec', $customer->phone);
            }
            $this->text($doc, $gDatRec, 'dCodCliente', (string) $customer->id);
        } else {
            // Consumidor final sin documento
            $this->text($doc, $gDatRec, 'iNatRec',    '1');
            $this->text($doc, $gDatRec, 'iTiOpe',     '2');
            $this->text($doc, $gDatRec, 'cPaisRec',   'PRY');
            $this->text($doc, $gDatRec, 'dDesPaisRe', 'Paraguay');
            $this->text($doc, $gDatRec, 'dNomRec',    $customer?->name ?? 'SIN NOMBRE');
        }
    }

    private function buildGDtipDE(DOMDocument $doc, DOMElement $parent, Sale $sale): void
    {
        $gDtipDE = $doc->createElement('gDtipDE');
        $parent->appendChild($gDtipDE);

        // <gCamFE> solo aplica para factura electrónica (iTiDE=1)
        $gCamFE = $doc->createElement('gCamFE');
        $gDtipDE->appendChild($gCamFE);
        $this->text($doc, $gCamFE, 'iIndPres',    '1');
        $this->text($doc, $gCamFE, 'dDesIndPres', 'Operación presencial');

        $this->buildGCamCond($doc, $gDtipDE, $sale);

        foreach ($sale->items as $item) {
            $this->buildGCamItem($doc, $gDtipDE, $item);
        }
    }

    private function buildGCamCond(DOMDocument $doc, DOMElement $parent, Sale $sale): void
    {
        $gCamCond = $doc->createElement('gCamCond');
        $parent->appendChild($gCamCond);

        $isCredit  = in_array($sale->payment_method, ['credito', 'crédito'], true)
            || $sale->condition === 'credito';
        $iCondOpe  = $isCredit ? '2' : '1';
        $dDCondOpe = $isCredit ? 'Crédito' : 'Contado';

        $this->text($doc, $gCamCond, 'iCondOpe',  $iCondOpe);
        $this->text($doc, $gCamCond, 'dDCondOpe', $dDCondOpe);

        if ($isCredit) {
            $gPagCred = $doc->createElement('gPagCred');
            $gCamCond->appendChild($gPagCred);
            $this->text($doc, $gPagCred, 'iCondCred',  '1');
            $this->text($doc, $gPagCred, 'dDCondCred', 'Plazo');
            if ($sale->credit_due_date) {
                $days = (int) now()->startOfDay()->diffInDays($sale->credit_due_date->startOfDay());
                $this->text($doc, $gPagCred, 'dPlazoCre', (string) max(1, $days));
            }
        } else {
            // Contado: detallar método de pago
            $method    = $sale->payment_method ?? 'efectivo';
            [$tiPago, $desTiPago] = self::METODOS_PAGO[$method] ?? ['1', 'Efectivo'];

            $gPagContado = $doc->createElement('gPagContado');
            $gCamCond->appendChild($gPagContado);
            $this->text($doc, $gPagContado, 'iTiPago',    $tiPago);
            $this->text($doc, $gPagContado, 'dDesTiPago', $desTiPago);
            $this->text($doc, $gPagContado, 'dMonTiPago', (string) (int) round((float) $sale->total));
        }
    }

    private function buildGCamItem(DOMDocument $doc, DOMElement $parent, SaleItem $item): void
    {
        $product  = $item->productVariant->product;
        $taxRate  = (float) ($item->tax_percentage ?? $product->tax_percentage ?? 10);
        $gross    = (float) $item->quantity * (float) $item->price;
        $discount = (float) $item->discount;
        $net      = $gross - $discount;

        // Base gravada e IVA (precio incluye IVA en Paraguay)
        $taxBase   = $taxRate > 0 ? round($net / (1 + $taxRate / 100)) : $net;
        $ivaAmount = $taxRate > 0 ? round($net - $taxBase) : 0;
        $iAfecIVA  = $taxRate > 0 ? '1' : '3';
        $desAfecIVA = $taxRate > 0 ? 'Gravado IVA' : 'Exento';
        $porcDesc   = $gross > 0 ? round($discount / $gross * 100, 2) : 0;

        $gCamItem = $doc->createElement('gCamItem');
        $parent->appendChild($gCamItem);

        $this->text($doc, $gCamItem, 'dCodInt',    $product->sku ?? $product->barcode ?? '');
        $this->text($doc, $gCamItem, 'dDesProSer', mb_strtoupper($product->name));
        $this->text($doc, $gCamItem, 'cUniMed',    '77');
        $this->text($doc, $gCamItem, 'dDesUniMed', 'UNI');
        $this->text($doc, $gCamItem, 'dCantProSer',(string) $item->quantity);

        // <gValorItem>
        $gValorItem = $doc->createElement('gValorItem');
        $gCamItem->appendChild($gValorItem);
        $this->text($doc, $gValorItem, 'dPUniProSer',      (string) (int) round((float) $item->price));
        $this->text($doc, $gValorItem, 'dTotBruOpeItem',   (string) (int) round($gross));

        $gValorRestaItem = $doc->createElement('gValorRestaItem');
        $gValorItem->appendChild($gValorRestaItem);
        $this->text($doc, $gValorRestaItem, 'dDescItem',    (string) (int) round($discount));
        $this->text($doc, $gValorRestaItem, 'dPorcDesIt',   (string) $porcDesc);
        $this->text($doc, $gValorRestaItem, 'dDescGloItem', '0');
        $this->text($doc, $gValorRestaItem, 'dTotOpeItem',  (string) (int) round($net));

        // <gCamIVA>
        $gCamIVA = $doc->createElement('gCamIVA');
        $gCamItem->appendChild($gCamIVA);
        $this->text($doc, $gCamIVA, 'iAfecIVA',   $iAfecIVA);
        $this->text($doc, $gCamIVA, 'dDesAfecIVA',$desAfecIVA);
        $this->text($doc, $gCamIVA, 'dPropIVA',   '100');
        $this->text($doc, $gCamIVA, 'dTasaIVA',   (string) (int) $taxRate);
        $this->text($doc, $gCamIVA, 'dBasGravIVA', (string) (int) $taxBase);
        $this->text($doc, $gCamIVA, 'dLiqIVAItem', (string) (int) $ivaAmount);
    }

    private function buildGTotSub(DOMDocument $doc, DOMElement $parent, Sale $sale): void
    {
        $subExe   = (float) ($sale->subtotal_exenta ?? 0);
        $sub5     = (float) ($sale->subtotal_5 ?? 0);
        $sub10    = (float) ($sale->subtotal_10 ?? 0);
        $tax5     = (float) ($sale->tax_5 ?? 0);
        $tax10    = (float) ($sale->tax_10 ?? 0);
        $base5    = $sub5 - $tax5;
        $base10   = $sub10 - $tax10;

        $gTotSub = $doc->createElement('gTotSub');
        $parent->appendChild($gTotSub);

        $this->text($doc, $gTotSub, 'dSubExe',        (string) (int) round($subExe));
        $this->text($doc, $gTotSub, 'dSubExo',        '0');
        $this->text($doc, $gTotSub, 'dSub5',          (string) (int) round($sub5));
        $this->text($doc, $gTotSub, 'dSub10',         (string) (int) round($sub10));
        $this->text($doc, $gTotSub, 'dTotOpe',        (string) (int) round((float) $sale->subtotal));
        $this->text($doc, $gTotSub, 'dTotDesc',       (string) (int) round((float) $sale->discount));
        $this->text($doc, $gTotSub, 'dTotDescGlotem', '0');
        $this->text($doc, $gTotSub, 'dTotAntItem',    '0');
        $this->text($doc, $gTotSub, 'dTotAnt',        '0');
        $this->text($doc, $gTotSub, 'dPorcDescTotal', '0');
        $this->text($doc, $gTotSub, 'dDescTotal',     number_format((float) $sale->discount, 1, '.', ''));
        $this->text($doc, $gTotSub, 'dAnticipo',      '0');
        $this->text($doc, $gTotSub, 'dRedon',         '0.0');
        $this->text($doc, $gTotSub, 'dTotGralOpe',    (string) (int) round((float) $sale->total));
        $this->text($doc, $gTotSub, 'dIVA5',          (string) (int) round($tax5));
        $this->text($doc, $gTotSub, 'dIVA10',         (string) (int) round($tax10));
        $this->text($doc, $gTotSub, 'dTotIVA',        (string) (int) round((float) $sale->tax));
        $this->text($doc, $gTotSub, 'dBaseGrav5',     (string) (int) round($base5));
        $this->text($doc, $gTotSub, 'dBaseGrav10',    (string) (int) round($base10));
        $this->text($doc, $gTotSub, 'dTBasGraIVA',   (string) (int) round($base5 + $base10));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Crea un elemento de texto y lo adjunta al padre.
     */
    private function text(DOMDocument $doc, DOMElement $parent, string $tag, string $value): DOMElement
    {
        $el = $doc->createElement($tag);
        $el->appendChild($doc->createTextNode($value));
        $parent->appendChild($el);

        return $el;
    }
}
