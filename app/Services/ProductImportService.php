<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductImportService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Importa productos desde un archivo CSV.
     *
     * @param  string  $filePath  Ruta al archivo CSV
     * @param  Warehouse  $warehouse  Almacén donde se cargará el stock inicial
     * @return array{created: int, updated: int, errors: array<string>}
     */
    public function import(string $filePath, Warehouse $warehouse): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['No se pudo abrir el archivo.']];
        }

        // Leer encabezados y normalizar
        $rawHeaders = fgetcsv($handle);
        if ($rawHeaders === false) {
            fclose($handle);
            return ['created' => 0, 'updated' => 0, 'errors' => ['El archivo está vacío.']];
        }
        $headers = array_map(fn($h) => mb_strtolower(trim($h)), $rawHeaders);

        $created = 0;
        $updated = 0;
        $errors  = [];
        $rowNum  = 1;

        while (($raw = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($raw) !== count($headers)) {
                $errors[] = "Fila {$rowNum}: columnas incorrectas (" . count($raw) . " encontradas, " . count($headers) . " esperadas).";
                continue;
            }

            $row = array_combine($headers, $raw);

            try {
                $result = DB::transaction(fn () => $this->processRow($row, $warehouse));
                $result === 'created' ? $created++ : $updated++;
            } catch (\Exception $e) {
                $errors[] = "Fila {$rowNum} ({$row['nombre']}): " . $e->getMessage();
            }
        }

        fclose($handle);

        return compact('created', 'updated', 'errors');
    }

    /**
     * Procesa una fila del CSV y crea o actualiza el producto.
     *
     * @return string 'created' | 'updated'
     */
    protected function processRow(array $row, Warehouse $warehouse): string
    {
        $nombre = trim($row['nombre'] ?? '');

        if ($nombre === '') {
            throw new \InvalidArgumentException('El campo "nombre" es obligatorio.');
        }

        $precioVenta = (float) str_replace(['.', ','], ['', '.'], $row['precio_venta'] ?? '0');

        if ($precioVenta <= 0) {
            throw new \InvalidArgumentException('El precio de venta debe ser mayor a 0.');
        }

        $iva = (int) ($row['iva'] ?? 10);
        if (! in_array($iva, [0, 5, 10])) {
            $iva = 10;
        }

        // Categoría: buscar por nombre o crear
        $categoryId = null;
        $catNombre  = trim($row['categoria'] ?? '');
        if ($catNombre !== '') {
            $category   = Category::firstOrCreate(
                ['slug' => Str::slug($catNombre)],
                ['name' => $catNombre, 'active' => true]
            );
            $categoryId = $category->id;
        }

        $sku     = trim($row['sku'] ?? '') ?: null;
        $barcode = trim($row['barcode'] ?? '') ?: null;

        // Buscar producto existente (por SKU, luego barcode, luego nombre exacto)
        $product = null;
        if ($sku) {
            $product = Product::withTrashed()->where('sku', $sku)->first();
        }
        if (! $product && $barcode) {
            $product = Product::withTrashed()->where('barcode', $barcode)->first();
        }

        $productData = [
            'name'           => $nombre,
            'category_id'    => $categoryId,
            'brand'          => trim($row['marca'] ?? '') ?: null,
            'sku'            => $sku,
            'barcode'        => $barcode,
            'cost_price'     => (float) str_replace(['.', ','], ['', '.'], $row['precio_costo'] ?? '0'),
            'sale_price'     => $precioVenta,
            'tax_percentage' => $iva,
            'min_stock'      => max(0, (int) ($row['stock_minimo'] ?? 0)),
            'description'    => trim($row['descripcion'] ?? '') ?: null,
            'active'         => true,
        ];

        $isNew = $product === null;

        if ($product) {
            // Si está eliminado, restaurar
            if ($product->trashed()) {
                $product->restore();
            }
            $product->update($productData);
        } else {
            $product = Product::create($productData);
        }

        // Variante base: crear si no existe o actualizar la primera
        $variant = $product->variants()->first();

        if (! $variant) {
            $variant = $product->variants()->create([
                'sku'     => $sku,
                'barcode' => $barcode,
                'price'   => $precioVenta,
                'active'  => true,
            ]);
        } else {
            $variant->update([
                'price'  => $precioVenta,
                'active' => true,
            ]);
        }

        // Stock inicial (solo si es nuevo y tiene cantidad)
        $stockInicial = max(0, (int) ($row['stock_inicial'] ?? 0));

        if ($isNew && $stockInicial > 0) {
            $this->inventoryService->addStock($variant, $warehouse, $stockInicial, [
                'type'  => 'adjustment',
                'notes' => 'Carga inicial - importación CSV',
            ]);
        }

        return $isNew ? 'created' : 'updated';
    }

    /**
     * Genera el contenido CSV de la plantilla de importación.
     */
    public static function buildTemplate(): string
    {
        $headers = ['nombre', 'sku', 'barcode', 'categoria', 'marca', 'precio_venta', 'precio_costo', 'iva', 'stock_inicial', 'stock_minimo', 'descripcion'];

        $examples = [
            ['Martillo 500g',       'MART-001', '7891234500001', 'Herramientas',  'Stanley',  '85000', '59500', '10', '5',  '2', 'Martillo de carpintero 500 gramos'],
            ['Tornillo 3x20 (100u)', 'TORN-020', '',              'Tornillos',     '',         '12000', '8000',  '10', '50', '10', 'Caja 100 unidades'],
            ['Pintura Blanca 4L',    'PINT-B04', '7891234500002', 'Pinturas',      'Sherwin',  '95000', '67000', '5',  '3',  '1', 'Látex interior blanco 4 litros'],
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
