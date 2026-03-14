<?php

namespace App\Services;

use App\Models\ProductLocation;
use App\Models\ProductVariant;
use App\Models\Shelf;
use App\Models\ShelfLevel;
use App\Models\ShelfRow;
use App\Models\Warehouse;
use App\Models\WarehouseAisle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de gestión de ubicaciones de almacén.
 *
 * Administra la creación de pasillos, estantes, filas y niveles dentro
 * de almacenes, así como la asignación y consulta de ubicaciones
 * de productos.
 */
class LocationService
{
    /**
     * Convierte un número entero a su representación en letras (estilo columnas de Excel: 1=A, 26=Z, 27=AA).
     *
     * @param  int  $num  El número a convertir (debe ser mayor que 0).
     * @return string La representación en letras del número.
     */
    public function numberToLetters(int $num): string
    {
        $result = '';
        while ($num > 0) {
            $num--;
            $result = chr($num % 26 + 65).$result;
            $num = intdiv($num, 26);
        }

        return $result;
    }

    /**
     * Genera el siguiente código de pasillo disponible para un almacén.
     *
     * @param  Warehouse  $warehouse  El almacén para el cual se generará el código.
     * @return string El siguiente código de pasillo en secuencia alfabética (ej. 'A', 'B', ..., 'AA').
     */
    public function generateNextAisleCode(Warehouse $warehouse): string
    {
        $lastAisle = $warehouse->aisles()
            ->orderByDesc('code')
            ->first();

        if (! $lastAisle) {
            return 'A';
        }

        $nextNum = $this->lettersToNumber($lastAisle->code) + 1;

        return $this->numberToLetters($nextNum);
    }

    /**
     * Convierte una representación en letras a su valor numérico correspondiente (A=1, Z=26, AA=27).
     *
     * @param  string  $letters  La cadena de letras a convertir.
     * @return int El valor numérico correspondiente.
     */
    public function lettersToNumber(string $letters): int
    {
        $result = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $result = $result * 26 + (ord($letters[$i]) - 64);
        }

        return $result;
    }

    /**
     * Crea un nuevo pasillo en un almacén, opcionalmente con estantes, filas y niveles predefinidos.
     *
     * @param  Warehouse  $warehouse  El almacén donde se creará el pasillo.
     * @param  array  $data  Datos del pasillo: code (opcional, se autogenera si no se provee), description, create_shelves (bool), num_shelves (int, por defecto 5).
     * @return WarehouseAisle El pasillo creado con sus estantes asociados si fueron solicitados.
     */
    public function createLocation(Warehouse $warehouse, array $data): WarehouseAisle
    {
        return DB::transaction(function () use ($warehouse, $data) {
            $aisleCode = $data['code'] ?? $this->generateNextAisleCode($warehouse);

            $aisle = WarehouseAisle::create([
                'warehouse_id' => $warehouse->id,
                'code' => $aisleCode,
                'description' => $data['description'] ?? null,
            ]);

            if (isset($data['create_shelves'])) {
                $numShelves = $data['num_shelves'] ?? 5;
                for ($i = 1; $i <= $numShelves; $i++) {
                    $this->createShelfWithRowsAndLevels($aisle, str_pad($i, 2, '0', STR_PAD_LEFT));
                }
            }

            return $aisle;
        });
    }

    /**
     * Crea un estante con filas y niveles predeterminados (3 filas y 4 niveles por fila).
     *
     * @param  WarehouseAisle  $aisle  El pasillo donde se creará el estante.
     * @param  string  $shelfNumber  El número identificador del estante.
     * @return Shelf El estante creado con todas sus filas y niveles.
     */
    public function createShelfWithRowsAndLevels(WarehouseAisle $aisle, string $shelfNumber): Shelf
    {
        return DB::transaction(function () use ($aisle, $shelfNumber) {
            $shelf = Shelf::create([
                'warehouse_aisle_id' => $aisle->id,
                'number' => $shelfNumber,
                'description' => "Estante {$shelfNumber}",
            ]);

            $numRows = 3;
            $numLevels = 4;

            for ($r = 1; $r <= $numRows; $r++) {
                $row = ShelfRow::create([
                    'shelf_id' => $shelf->id,
                    'number' => str_pad($r, 2, '0', STR_PAD_LEFT),
                    'description' => "Fila {$r}",
                ]);

                for ($l = 1; $l <= $numLevels; $l++) {
                    ShelfLevel::create([
                        'shelf_row_id' => $row->id,
                        'number' => str_pad($l, 2, '0', STR_PAD_LEFT),
                        'description' => "Nivel {$l}",
                    ]);
                }
            }

            return $shelf;
        });
    }

    /**
     * Obtiene el código completo de ubicación de un nivel de estante (formato: PASILLO-ESTANTE-FILA-NIVEL).
     *
     * @param  ShelfLevel  $level  El nivel de estante del cual se generará el código.
     * @return string El código de ubicación completo (ej. 'A-01-02-03').
     */
    public function getFullLocationCode(ShelfLevel $level): string
    {
        $aisle = $level->row->shelf->aisle;
        $shelf = $level->row->shelf;
        $row = $level->row;

        return sprintf(
            '%s-%s-%s-%s',
            $aisle->code,
            $shelf->number,
            $row->number,
            $level->number
        );
    }

    /**
     * Asigna una variante de producto a una ubicación específica o incrementa la cantidad si ya existe.
     *
     * @param  ProductVariant  $productVariant  La variante de producto a asignar.
     * @param  ShelfLevel  $level  El nivel de estante donde se ubicará el producto.
     * @param  int  $quantity  La cantidad de unidades a asignar (por defecto 0).
     * @return ProductLocation La ubicación de producto creada o actualizada.
     */
    public function assignLocation(ProductVariant $productVariant, ShelfLevel $level, int $quantity = 0): ProductLocation
    {
        return DB::transaction(function () use ($productVariant, $level, $quantity) {
            $existingLocation = ProductLocation::where('product_variant_id', $productVariant->id)
                ->where('shelf_level_id', $level->id)
                ->first();

            if ($existingLocation) {
                $existingLocation->increment('quantity', $quantity);

                return $existingLocation;
            }

            return ProductLocation::create([
                'product_variant_id' => $productVariant->id,
                'shelf_level_id' => $level->id,
                'quantity' => $quantity,
            ]);
        });
    }

    /**
     * Obtiene todas las ubicaciones asignadas a una variante de producto con sus códigos y almacenes.
     *
     * @param  ProductVariant  $productVariant  La variante de producto a consultar.
     * @return Collection Colección de arreglos con id, quantity, location_code y warehouse.
     */
    public function getProductLocations(ProductVariant $productVariant)
    {
        return ProductLocation::where('product_variant_id', $productVariant->id)
            ->with(['shelfLevel.row.shelf.aisle.warehouse'])
            ->get()
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'quantity' => $location->quantity,
                    'location_code' => $this->getFullLocationCode($location->shelfLevel),
                    'warehouse' => $location->shelfLevel->row->shelf->aisle->warehouse->name,
                ];
            });
    }

    /**
     * Obtiene todas las variantes de producto que no tienen una ubicación asignada.
     *
     * @return Collection Colección de arreglos con id, name, sku y barcode de cada variante sin ubicación.
     */
    public function getProductsWithoutLocation()
    {
        return ProductVariant::whereDoesntHave('productLocations')
            ->with('product')
            ->get()
            ->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                ];
            });
    }
}
