<?php

namespace App\Services;

use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentItem;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de gestión de inventario.
 *
 * Proporciona operaciones para agregar, remover, ajustar y transferir stock
 * entre almacenes, así como consultar niveles de inventario y productos con
 * stock bajo.
 */
class InventoryService
{
    /**
     * Agrega stock de una variante de producto en un almacén.
     *
     * @param  ProductVariant  $productVariant  La variante de producto a la que se agregará stock.
     * @param  Warehouse  $warehouse  El almacén donde se agregará el stock.
     * @param  int  $quantity  La cantidad de unidades a agregar.
     * @param  array  $data  Datos opcionales del movimiento (type, reference_id, reference_type, user_id, notes).
     * @return StockMovement El movimiento de stock registrado.
     */
    public function addStock(ProductVariant $productVariant, Warehouse $warehouse, int $quantity, array $data = []): StockMovement
    {
        return DB::transaction(function () use ($productVariant, $warehouse, $quantity, $data) {
            $stock = $this->getOrCreateStock($productVariant, $warehouse);
            $stock->increment('quantity', $quantity);

            return StockMovement::create([
                'product_variant_id' => $productVariant->id,
                'warehouse_id' => $warehouse->id,
                'type' => $data['type'] ?? 'purchase',
                'quantity' => $quantity,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Remueve stock de una variante de producto en un almacén.
     *
     * @param  ProductVariant  $productVariant  La variante de producto de la que se removerá stock.
     * @param  Warehouse  $warehouse  El almacén del que se removerá el stock.
     * @param  int  $quantity  La cantidad de unidades a remover.
     * @param  array  $data  Datos opcionales del movimiento (type, reference_id, reference_type, user_id, notes).
     * @return StockMovement El movimiento de stock registrado.
     *
     * @throws \Exception Si el stock disponible es insuficiente para la cantidad solicitada.
     */
    public function removeStock(ProductVariant $productVariant, Warehouse $warehouse, int $quantity, array $data = []): StockMovement
    {
        return DB::transaction(function () use ($productVariant, $warehouse, $quantity, $data) {
            $stock = $this->getOrCreateStock($productVariant, $warehouse);

            if ($stock->quantity < $quantity) {
                throw new \Exception("Stock insuficiente. Disponible: {$stock->quantity}");
            }

            $stock->decrement('quantity', $quantity);

            return StockMovement::create([
                'product_variant_id' => $productVariant->id,
                'warehouse_id' => $warehouse->id,
                'type' => $data['type'] ?? 'sale',
                'quantity' => -$quantity,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Ajusta el stock de una variante de producto a una cantidad específica.
     *
     * @param  ProductVariant  $productVariant  La variante de producto cuyo stock se ajustará.
     * @param  Warehouse  $warehouse  El almacén donde se realizará el ajuste.
     * @param  int  $newQuantity  La nueva cantidad de stock deseada.
     * @param  string  $reason  El motivo del ajuste de inventario.
     * @param  int|null  $userId  El ID del usuario que realiza el ajuste, o null para usar el usuario autenticado.
     * @return InventoryAdjustment El registro de ajuste de inventario creado.
     */
    public function adjustStock(ProductVariant $productVariant, Warehouse $warehouse, int $newQuantity, string $reason, ?int $userId = null): InventoryAdjustment
    {
        return DB::transaction(function () use ($productVariant, $warehouse, $newQuantity, $reason, $userId) {
            $stock = $this->getOrCreateStock($productVariant, $warehouse);
            $quantityBefore = $stock->quantity;

            $adjustment = InventoryAdjustment::create([
                'user_id' => $userId ?? auth()->id(),
                'warehouse_id' => $warehouse->id,
                'reason' => $reason,
                'status' => 'approved',
            ]);

            InventoryAdjustmentItem::create([
                'adjustment_id' => $adjustment->id,
                'product_variant_id' => $productVariant->id,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $newQuantity,
            ]);

            $stock->update(['quantity' => $newQuantity]);

            $difference = $newQuantity - $quantityBefore;
            StockMovement::create([
                'product_variant_id' => $productVariant->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'adjustment',
                'quantity' => $difference,
                'reference_id' => $adjustment->id,
                'reference_type' => InventoryAdjustment::class,
                'user_id' => $userId ?? auth()->id(),
                'notes' => $reason,
            ]);

            return $adjustment;
        });
    }

    /**
     * Procesa un ajuste de inventario completo que ya tiene sus items asociados.
     * Solo debe llamarse cuando el ajuste pasa a estado 'approved'.
     *
     * @param  InventoryAdjustment  $adjustment  El ajuste de inventario a procesar.
     */
    public function processAdjustment(InventoryAdjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment) {
            foreach ($adjustment->items as $item) {
                $stock = $this->getOrCreateStock($item->productVariant, $adjustment->warehouse);
                $quantityBefore = $stock->quantity;
                $newQuantity = $item->quantity_after;
                
                // Actualizamos item quantity_before por si cambió antes de aprobarse
                $item->update(['quantity_before' => $quantityBefore]);

                $stock->update(['quantity' => $newQuantity]);

                $difference = $newQuantity - $quantityBefore;
                if ($difference !== 0) {
                    StockMovement::create([
                        'product_variant_id' => $item->product_variant_id,
                        'warehouse_id' => $adjustment->warehouse_id,
                        'type' => 'adjustment',
                        'quantity' => $difference,
                        'reference_id' => $adjustment->id,
                        'reference_type' => InventoryAdjustment::class,
                        'user_id' => $adjustment->user_id,
                        'notes' => $adjustment->reason,
                    ]);
                }
            }
        });
    }

    /**
     * Transfiere stock de una variante de producto entre dos almacenes.
     *
     * @param  ProductVariant  $productVariant  La variante de producto a transferir.
     * @param  Warehouse  $fromWarehouse  El almacén de origen.
     * @param  Warehouse  $toWarehouse  El almacén de destino.
     * @param  int  $quantity  La cantidad de unidades a transferir.
     * @param  int|null  $userId  El ID del usuario que realiza la transferencia, o null para usar el usuario autenticado.
     * @return array Arreglo asociativo con las claves 'from' y 'to' conteniendo los registros de Stock actualizados.
     *
     * @throws \Exception Si el stock disponible en el almacén de origen es insuficiente.
     */
    public function transferStock(ProductVariant $productVariant, Warehouse $fromWarehouse, Warehouse $toWarehouse, int $quantity, ?int $userId = null): array
    {
        return DB::transaction(function () use ($productVariant, $fromWarehouse, $toWarehouse, $quantity, $userId) {
            $fromStock = $this->getOrCreateStock($productVariant, $fromWarehouse);

            if ($fromStock->quantity < $quantity) {
                throw new \Exception("Stock insuficiente en origen. Disponible: {$fromStock->quantity}");
            }

            $fromStock->decrement('quantity', $quantity);

            StockMovement::create([
                'product_variant_id' => $productVariant->id,
                'warehouse_id' => $fromWarehouse->id,
                'type' => 'transfer',
                'quantity' => -$quantity,
                'reference_type' => Warehouse::class,
                'reference_id' => $toWarehouse->id,
                'user_id' => $userId ?? auth()->id(),
                'notes' => "Transferencia a {$toWarehouse->name}",
            ]);

            $toStock = $this->getOrCreateStock($productVariant, $toWarehouse);
            $toStock->increment('quantity', $quantity);

            StockMovement::create([
                'product_variant_id' => $productVariant->id,
                'warehouse_id' => $toWarehouse->id,
                'type' => 'transfer',
                'quantity' => $quantity,
                'reference_type' => Warehouse::class,
                'reference_id' => $fromWarehouse->id,
                'user_id' => $userId ?? auth()->id(),
                'notes' => "Transferencia desde {$fromWarehouse->name}",
            ]);

            return [
                'from' => $fromStock->fresh(),
                'to' => $toStock->fresh(),
            ];
        });
    }

    /**
     * Verifica si el stock de una variante de producto está en o por debajo del mínimo configurado.
     *
     * @param  ProductVariant  $productVariant  La variante de producto a verificar.
     * @param  Warehouse|null  $warehouse  El almacén específico a consultar, o null para verificar el stock total.
     * @return bool True si el stock está en o por debajo del mínimo, false en caso contrario.
     */
    public function checkMinimum(ProductVariant $productVariant, ?Warehouse $warehouse = null): bool
    {
        $product = $productVariant->product;

        if ($warehouse) {
            $stock = Stock::where('product_variant_id', $productVariant->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();

            return $stock && $stock->quantity <= $product->min_stock;
        }

        $totalStock = Stock::where('product_variant_id', $productVariant->id)->sum('quantity');

        return $totalStock <= $product->min_stock;
    }

    /**
     * Obtiene todas las variantes de producto cuyo stock total está por debajo del mínimo configurado.
     *
     * @return Collection Colección de variantes de producto con stock bajo, incluyendo relaciones de producto, categoría y stocks por almacén.
     */
    public function getLowStockProducts()
    {
        return ProductVariant::whereHas('product', function ($query) {
            $query->whereRaw('(
                SELECT COALESCE(SUM(quantity), 0)
                FROM stocks
                WHERE stocks.product_variant_id = product_variants.id
            ) <= products.min_stock');
        })
            ->with(['product.category', 'stocks.warehouse'])
            ->get();
    }

    /**
     * Obtiene o crea un registro de stock para una variante de producto en un almacén.
     *
     * @param  ProductVariant  $productVariant  La variante de producto.
     * @param  Warehouse  $warehouse  El almacén.
     * @return Stock El registro de stock existente o recién creado con cantidad inicial de 0.
     */
    protected function getOrCreateStock(ProductVariant $productVariant, Warehouse $warehouse): Stock
    {
        return Stock::firstOrCreate(
            [
                'product_variant_id' => $productVariant->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'quantity' => 0,
            ]
        );
    }

    /**
     * Obtiene el registro de stock de una variante de producto en un almacén específico.
     *
     * @param  ProductVariant  $productVariant  La variante de producto a consultar.
     * @param  Warehouse  $warehouse  El almacén donde buscar el stock.
     * @return Stock|null El registro de stock encontrado, o null si no existe.
     */
    public function getStockByWarehouse(ProductVariant $productVariant, Warehouse $warehouse): ?Stock
    {
        return Stock::where('product_variant_id', $productVariant->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();
    }

    /**
     * Obtiene la cantidad total de stock de una variante de producto en todos los almacenes.
     *
     * @param  ProductVariant  $productVariant  La variante de producto a consultar.
     * @return int La suma total de stock en todos los almacenes.
     */
    public function getTotalStock(ProductVariant $productVariant): int
    {
        return Stock::where('product_variant_id', $productVariant->id)->sum('quantity');
    }
}
