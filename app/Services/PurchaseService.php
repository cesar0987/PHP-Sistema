<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de gestión de compras.
 *
 * Maneja la creación de órdenes de compra, la recepción de productos
 * en almacén y la cancelación de compras.
 */
class PurchaseService
{
    protected InventoryService $inventoryService;

    /**
     * Crea una nueva instancia del servicio de compras.
     *
     * @param  InventoryService  $inventoryService  Servicio de inventario para gestionar el stock.
     */
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Crea una nueva orden de compra con sus ítems y opcionalmente recibe los productos en inventario.
     *
     * @param  array  $data  Datos de la compra: items (array con product_variant_id, quantity, cost), supplier_id, branch_id, warehouse_id, user_id, discount, tax, status, purchase_date, notes, receive_products (bool).
     * @return Purchase La orden de compra creada.
     *
     * @throws ModelNotFoundException Si alguna variante de producto o almacén no existe.
     */
    public function createPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $total = 0;

            foreach ($data['items'] as $item) {
                $total += $item['quantity'] * $item['cost'];
            }

            $discount = $data['discount'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $total = $total + $tax - $discount;

            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'branch_id' => $data['branch_id'],
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => $data['user_id'] ?? throw new \InvalidArgumentException('user_id es requerido para crear una compra'),
                'total' => $total,
                'discount' => $discount,
                'tax' => $tax,
                'status' => $data['status'] ?? 'pending',
                'purchase_date' => $data['purchase_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $productVariant = ProductVariant::findOrFail($item['product_variant_id']);
                $itemSubtotal = $item['quantity'] * $item['cost'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_variant_id' => $productVariant->id,
                    'quantity' => $item['quantity'],
                    'cost' => $item['cost'],
                    'subtotal' => $itemSubtotal,
                ]);

                if (isset($data['receive_products']) && $data['receive_products']) {
                    $warehouse = Warehouse::findOrFail($data['warehouse_id']);
                    $this->inventoryService->addStock(
                        $productVariant,
                        $warehouse,
                        $item['quantity'],
                        [
                            'type' => 'purchase',
                            'reference_id' => $purchase->id,
                            'reference_type' => Purchase::class,
                            'user_id' => $purchase->user_id,
                            'notes' => "Compra #{$purchase->id}",
                        ]
                    );
                }
            }

            return $purchase;
        });
    }

    /**
     * Recibe los productos de una orden de compra pendiente y los agrega al inventario del almacén.
     *
     * @param  Purchase  $purchase  La orden de compra cuyos productos se van a recibir.
     * @return Purchase La orden de compra actualizada con estado 'received'.
     */
    public function receiveProducts(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $warehouse = $purchase->warehouse;

            foreach ($purchase->items as $item) {
                $this->inventoryService->addStock(
                    $item->productVariant,
                    $warehouse,
                    $item->quantity,
                    [
                        'type' => 'purchase',
                        'reference_id' => $purchase->id,
                        'reference_type' => Purchase::class,
                        'user_id' => $purchase->user_id,
                        'notes' => "Recepción compra #{$purchase->id}",
                    ]
                );
            }

            $purchase->update(['status' => 'received']);

            return $purchase;
        });
    }

    /**
     * Cancela una orden de compra cambiando su estado a 'cancelled'.
     *
     * @param  Purchase  $purchase  La orden de compra a cancelar.
     * @return Purchase La orden de compra actualizada con estado 'cancelled'.
     */
    public function cancelPurchase(Purchase $purchase): Purchase
    {
        $purchase->update(['status' => 'cancelled']);

        return $purchase;
    }
}
