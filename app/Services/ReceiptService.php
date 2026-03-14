<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Purchase;
use App\Models\Receipt;
use App\Models\ReceiptTemplate;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;

/**
 * Servicio de generación de comprobantes.
 *
 * Gestiona la creación, generación en PDF, descarga y transmisión
 * en línea de comprobantes de venta (tickets, facturas y recibos).
 */
class ReceiptService
{
    /**
     * Genera un comprobante para una venta o compra, crea el PDF y lo almacena en disco.
     *
     * @param  Model  $record  La venta o compra para la cual se generará el comprobante.
     * @param  string  $type  El tipo de comprobante: 'sale_ticket', 'purchase_ticket', etc.
     * @return Receipt El comprobante creado con su archivo PDF almacenado.
     */
    public function generateReceipt(Model $record, string $type = 'sale_ticket'): Receipt
    {
        $lastReceipt = Receipt::where('type', $type)
            ->orderByDesc('id')
            ->first();

        // Extraer numero en caso de que sea string o tener prefijo (asumiendo numerico paddeado)
        $nextNumber = 1;
        if ($lastReceipt && is_numeric($lastReceipt->number)) {
            $nextNumber = intval($lastReceipt->number) + 1;
        }

        $number = str_pad($nextNumber, 8, '0', STR_PAD_LEFT);

        $receipt = Receipt::create([
            'sale_id' => $record instanceof Sale ? $record->id : null,
            'purchase_id' => $record instanceof Purchase ? $record->id : null,
            'type' => $type,
            'number' => $number,
            'generated_at' => now(),
        ]);

        $pdf = $this->generatePdf($record, $receipt, $type);

        $filename = "receipt_{$type}_{$number}.pdf";
        $path = "receipts/{$filename}";

        $fullPath = storage_path("app/{$path}");
        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdf->save($fullPath);

        $receipt->update(['file_path' => $path]);

        return $receipt;
    }

    /**
     * Genera un objeto PDF con los datos del registro y el comprobante según el tipo indicado.
     *
     * @param  Model  $record  La entidad Sale o Purchase con sus relaciones.
     * @param  Receipt  $receipt  El comprobante asociado.
     * @param  string  $type  El tipo de comprobante.
     * @return \Barryvdh\DomPDF\PDF La instancia del PDF generado.
     */
    public function generatePdf(Model $record, Receipt $receipt, string $type)
    {
        $data = ['receipt' => $receipt];

        if ($record instanceof Sale) {
            $record->loadMissing(['customer', 'user', 'items.productVariant.product', 'branch']);
            $data['sale'] = $record;
            $data['company'] = $record->branch->company ?? null;
        } elseif ($record instanceof Purchase) {
            $record->loadMissing(['supplier', 'user', 'items.productVariant.product', 'branch', 'warehouse']);
            $data['purchase'] = $record;
            $data['company'] = $record->branch->company ?? Company::first();
        }

        // Buscar si existe una plantilla dinámica para este tipo
        $template = ReceiptTemplate::where('type', $type)->where('is_active', true)->first();

        if ($template && ! empty($template->content_html)) {
            // Renderizar Blade en memoria
            $html = Blade::render($template->content_html, $data);

            return Pdf::loadHTML($html);
        }

        // Fallbacks
        $view = match ($type) {
            'sale_invoice' => 'pdf.invoice',
            'sale_receipt' => 'pdf.receipt',
            'purchase_ticket' => 'pdf.purchase_ticket',
            'purchase_invoice' => 'pdf.purchase_invoice',
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
