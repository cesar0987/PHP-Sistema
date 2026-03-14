<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Servicio de generación de comprobantes.
 *
 * Gestiona la creación, generación en PDF, descarga y transmisión
 * en línea de comprobantes de venta (tickets, facturas y recibos).
 */
class ReceiptService
{
    /**
     * Genera un comprobante para una venta, crea el PDF y lo almacena en disco.
     *
     * @param  Sale  $sale  La venta para la cual se generará el comprobante.
     * @param  string  $type  El tipo de comprobante: 'ticket', 'invoice' o 'receipt' (por defecto 'ticket').
     * @return Receipt El comprobante creado con su archivo PDF almacenado.
     */
    public function generateReceipt(Sale $sale, string $type = 'ticket'): Receipt
    {
        $lastReceipt = Receipt::where('type', $type)
            ->orderByDesc('number')
            ->first();

        $nextNumber = $lastReceipt
            ? intval($lastReceipt->number) + 1
            : 1;

        $number = str_pad($nextNumber, 8, '0', STR_PAD_LEFT);

        $receipt = Receipt::create([
            'sale_id' => $sale->id,
            'type' => $type,
            'number' => $number,
            'generated_at' => now(),
        ]);

        $pdf = $this->generatePdf($sale, $receipt, $type);

        $filename = "receipt_{$type}_{$number}.pdf";
        $path = "receipts/{$filename}";

        $pdf->save(storage_path("app/{$path}"));

        $receipt->update(['file_path' => $path]);

        return $receipt;
    }

    /**
     * Genera un objeto PDF con los datos de la venta y el comprobante según el tipo indicado.
     *
     * @param  Sale  $sale  La venta con sus relaciones (customer, user, items, branch).
     * @param  Receipt  $receipt  El comprobante asociado.
     * @param  string  $type  El tipo de comprobante que determina la vista a usar: 'invoice', 'receipt' o 'ticket'.
     * @return \Barryvdh\DomPDF\PDF La instancia del PDF generado.
     */
    public function generatePdf(Sale $sale, Receipt $receipt, string $type)
    {
        $data = [
            'receipt' => $receipt,
            'sale' => $sale->load(['customer', 'user', 'items.productVariant.product', 'branch']),
            'company' => $sale->branch->company ?? null,
        ];

        $view = match ($type) {
            'invoice' => 'pdf.invoice',
            'receipt' => 'pdf.receipt',
            default => 'pdf.ticket',
        };

        return Pdf::loadView($view, $data);
    }

    /**
     * Genera y descarga el PDF de un comprobante como archivo adjunto.
     *
     * @param  Receipt  $receipt  El comprobante cuyo PDF se descargará.
     * @return Response Respuesta HTTP con el PDF como descarga.
     */
    public function downloadPdf(Receipt $receipt)
    {
        $sale = $receipt->sale->load(['customer', 'user', 'items.productVariant.product', 'branch']);

        return $this->generatePdf($sale, $receipt, $receipt->type)
            ->download("receipt_{$receipt->number}.pdf");
    }

    /**
     * Genera y transmite el PDF de un comprobante directamente al navegador.
     *
     * @param  Receipt  $receipt  El comprobante cuyo PDF se transmitirá.
     * @return Response Respuesta HTTP con el PDF para visualización en línea.
     */
    public function streamPdf(Receipt $receipt)
    {
        $sale = $receipt->sale->load(['customer', 'user', 'items.productVariant.product', 'branch']);

        return $this->generatePdf($sale, $receipt, $receipt->type)
            ->stream("receipt_{$receipt->number}.pdf");
    }
}
