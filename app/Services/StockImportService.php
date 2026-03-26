<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockImportService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Importa actualizaciones de stock desde un archivo CSV.
     *
     * El CSV debe identificar cada producto por SKU o barcode y especificar
     * la cantidad nueva (absoluta) o el ajuste (+/-).
     *
     * @param  string  $filePath  Ruta al archivo CSV
     * @param  Warehouse  $warehouse  Almacén donde se aplican los cambios
     * @param  string  $mode  'absoluto' → establece la cantidad exacta | 'ajuste' → suma o resta
     * @param  string  $motivo  Motivo del ajuste que queda en el log de auditoría
     * @return array{updated: int, skipped: int, errors: array<string>}
     */
    public function import(string $filePath, Warehouse $warehouse, string $mode, string $motivo): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return ['updated' => 0, 'skipped' => 0, 'errors' => ['No se pudo abrir el archivo.']];
        }

        $rawHeaders = fgetcsv($handle);
        if ($rawHeaders === false) {
            fclose($handle);
            return ['updated' => 0, 'skipped' => 0, 'errors' => ['El archivo está vacío.']];
        }
        $headers = array_map(fn ($h) => mb_strtolower(trim($h)), $rawHeaders);

        $updated = 0;
        $skipped = 0;
        $errors  = [];
        $rowNum  = 1;

        while (($raw = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($raw) !== count($headers)) {
                $errors[] = "Fila {$rowNum}: columnas incorrectas.";
                continue;
            }

            $row = array_combine($headers, $raw);

            try {
                $result = DB::transaction(
                    fn () => $this->processRow($row, $warehouse, $mode, $motivo)
                );
                $result ? $updated++ : $skipped++;
            } catch (\Exception $e) {
                $ref = trim($row['sku'] ?? $row['barcode'] ?? "fila {$rowNum}");
                $errors[] = "Fila {$rowNum} ({$ref}): " . $e->getMessage();
            }
        }

        fclose($handle);

        return compact('updated', 'skipped', 'errors');
    }

    /**
     * Procesa una fila del CSV.
     *
     * @return bool true si se actualizó, false si se omitió (sin cambios)
     */
    protected function processRow(array $row, Warehouse $warehouse, string $mode, string $motivo): bool
    {
        $sku     = trim($row['sku'] ?? '');
        $barcode = trim($row['barcode'] ?? '');
        $cantidadRaw = trim($row['cantidad'] ?? '');

        if ($sku === '' && $barcode === '') {
            throw new \InvalidArgumentException('Debe especificar "sku" o "barcode".');
        }

        if ($cantidadRaw === '' || ! is_numeric($cantidadRaw)) {
            throw new \InvalidArgumentException('La "cantidad" debe ser un número.');
        }

        $cantidad = (int) $cantidadRaw;

        // Buscar variante primero por SKU, luego por barcode
        $variant = null;

        if ($sku !== '') {
            $variant = ProductVariant::where('sku', $sku)->first()
                ?? ProductVariant::whereHas('product', fn ($q) => $q->where('sku', $sku))->first();
        }

        if (! $variant && $barcode !== '') {
            $variant = ProductVariant::where('barcode', $barcode)->first()
                ?? ProductVariant::whereHas('product', fn ($q) => $q->where('barcode', $barcode))->first();
        }

        if (! $variant) {
            $ref = $sku ?: $barcode;
            throw new \RuntimeException("No se encontró ningún producto con SKU/barcode \"{$ref}\".");
        }

        $motivoFinal = ! empty(trim($row['motivo'] ?? ''))
            ? trim($row['motivo'])
            : $motivo;

        if ($mode === 'ajuste') {
            // Modo ajuste: sumar o restar la cantidad al stock actual
            if ($cantidad === 0) {
                return false; // sin cambio
            }

            if ($cantidad > 0) {
                $this->inventoryService->addStock($variant, $warehouse, $cantidad, [
                    'type'  => 'adjustment',
                    'notes' => $motivoFinal,
                ]);
            } else {
                $this->inventoryService->removeStock($variant, $warehouse, abs($cantidad), [
                    'type'  => 'adjustment',
                    'notes' => $motivoFinal,
                ]);
            }
        } else {
            // Modo absoluto: fijar el stock a la cantidad indicada (usa adjustStock)
            if ($cantidad < 0) {
                throw new \InvalidArgumentException('En modo absoluto la cantidad no puede ser negativa.');
            }

            $stockActual = $variant->stocks()->where('warehouse_id', $warehouse->id)->value('quantity') ?? 0;

            if ($stockActual === $cantidad) {
                return false; // sin cambio, omitir
            }

            $this->inventoryService->adjustStock($variant, $warehouse, $cantidad, $motivoFinal);
        }

        return true;
    }

    /**
     * Genera el contenido CSV de la plantilla de actualización de stock.
     */
    public static function buildTemplate(): string
    {
        $headers = ['sku', 'barcode', 'nombre', 'cantidad', 'motivo'];

        $examples = [
            ['MART-001', '',              'Martillo 500g',         '25', 'Conteo físico marzo 2026'],
            ['',         '7891234500002', 'Pintura Blanca 4L',      '8', ''],
            ['TORN-020', '',              'Tornillo 3x20 (100u)',  '150', 'Recepción mercadería'],
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);

        foreach ($examples as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
