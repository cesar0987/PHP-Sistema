<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de gestión de ventas.
 *
 * Maneja la creación, cálculo, cancelación y consulta de ventas,
 * incluyendo la gestión automática de inventario y pagos asociados.
 */
class SaleService
{
    protected InventoryService $inventoryService;

    /**
     * Crea una nueva instancia del servicio de ventas.
     *
     * @param  InventoryService  $inventoryService  Servicio de inventario para gestionar el stock.
     */
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Crea una nueva venta con sus ítems, descuenta inventario y registra pagos.
     *
     * @param  array  $data  Datos de la venta: items (array con product_variant_id, quantity, price, discount), customer_id, user_id, branch_id, cash_register_id, discount, status, sale_date, notes, warehouse_id, payments (array con method, amount, reference).
     * @return Sale La venta creada con todos sus registros asociados.
     *
     * @throws ModelNotFoundException Si alguna variante de producto no existe.
     * @throws \Exception Si el stock es insuficiente para algún ítem.
     */
    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $subtotal         = 0;
            $tax              = 0;
            $subtotalExenta   = 0;
            $subtotal5        = 0;
            $subtotal10       = 0;
            $tax5             = 0;
            $tax10            = 0;

            // Pre-calculate totals and IVA breakdown by tax rate.
            // Paraguay: prices include IVA. Embedded IVA = price * rate / (100 + rate).
            $itemsData = [];
            foreach ($data['items'] as $item) {
                $productVariant = ProductVariant::findOrFail($item['product_variant_id']);
                $product = $productVariant->product;
                $taxRate = (float) ($product->tax_percentage ?? 0);

                $itemSubtotal = $item['quantity'] * $item['price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemNet      = $itemSubtotal - $itemDiscount;
                // IVA embedded in price: rate / (100 + rate)
                $itemTax = $taxRate > 0 ? $itemNet * $taxRate / (100 + $taxRate) : 0;

                $subtotal += $itemSubtotal;
                $tax      += $itemTax;

                if ($taxRate == 0) {
                    $subtotalExenta += $itemNet;
                } elseif ($taxRate == 5) {
                    $subtotal5 += $itemNet;
                    $tax5      += $itemTax;
                } else {
                    $subtotal10 += $itemNet;
                    $tax10      += $itemTax;
                }

                $itemsData[] = [
                    'productVariant' => $productVariant,
                    'quantity'       => $item['quantity'],
                    'price'          => $item['price'],
                    'discount'       => $itemDiscount,
                    'subtotal'       => $itemNet,
                    'tax_percentage' => $taxRate,
                    'tax_amount'     => $itemTax,
                ];
            }

            // Total = subtotal - discount (IVA already included in prices)
            $discount = $data['discount'] ?? 0;
            $total    = $subtotal - $discount;

            $sale = Sale::create([
                'customer_id'      => $data['customer_id'] ?? null,
                'user_id'          => $data['user_id'] ?? auth()->id(),
                'branch_id'        => $data['branch_id'],
                'cash_register_id' => $data['cash_register_id'] ?? null,
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'tax'              => $tax,
                'total'            => $total,
                'subtotal_exenta'  => $subtotalExenta,
                'subtotal_5'       => $subtotal5,
                'subtotal_10'      => $subtotal10,
                'tax_5'            => $tax5,
                'tax_10'           => $tax10,
                'status'           => $data['status'] ?? 'completed',
                'sale_date'        => $data['sale_date'] ?? now(),
                'notes'            => $data['notes'] ?? null,
            ]);

            $warehouse = isset($data['warehouse_id'])
                ? Warehouse::findOrFail($data['warehouse_id'])
                : null;

            foreach ($itemsData as $item) {
                $productVariant = $item['productVariant'];

                SaleItem::create([
                    'sale_id'            => $sale->id,
                    'product_variant_id' => $productVariant->id,
                    'quantity'           => $item['quantity'],
                    'price'              => $item['price'],
                    'discount'           => $item['discount'],
                    'subtotal'           => $item['subtotal'],
                    'tax_percentage'     => $item['tax_percentage'],
                    'tax_amount'         => $item['tax_amount'],
                ]);

                // Solo descontar stock si la venta está completada.
                // Las notas de pedido (pending) reservan el pedido sin afectar inventario.
                if ($sale->status === 'completed' && $warehouse) {
                    $this->inventoryService->removeStock(
                        $productVariant,
                        $warehouse,
                        $item['quantity'],
                        [
                            'type' => 'sale',
                            'reference_id' => $sale->id,
                            'reference_type' => Sale::class,
                            'notes' => "Venta #{$sale->id}",
                        ]
                    );
                }
            }

            if (isset($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    Payment::create([
                        'sale_id' => $sale->id,
                        'method' => $payment['method'],
                        'amount' => $payment['amount'],
                        'reference' => $payment['reference'] ?? null,
                        'payment_date' => now(),
                    ]);
                }
            }

            return $sale;
        });
    }

    /**
     * Calcula el subtotal, impuesto, descuento y total para un conjunto de ítems,
     * incluyendo el desglose de IVA por tasa (0%, 5%, 10%) requerido por SIFEN v150.
     *
     * @param  array  $items  Arreglo de ítems, cada uno con product_variant_id, quantity y price.
     * @param  float  $discount  Monto de descuento global a aplicar (por defecto 0).
     * @return array subtotal, tax, discount, total, subtotal_exenta, subtotal_5, subtotal_10, tax_5, tax_10.
     *
     * @throws ModelNotFoundException Si alguna variante de producto no existe.
     */
    public function calculateTotal(array $items, float $discount = 0): array
    {
        $subtotal       = 0;
        $tax            = 0;
        $subtotalExenta = 0;
        $subtotal5      = 0;
        $subtotal10     = 0;
        $tax5           = 0;
        $tax10          = 0;

        foreach ($items as $item) {
            $productVariant = ProductVariant::findOrFail($item['product_variant_id']);
            $product        = $productVariant->product;
            $taxRate        = (float) ($product->tax_percentage ?? 0);

            $itemSubtotal = $item['quantity'] * $item['price'];
            // IVA embedded in price: rate / (100 + rate)
            $itemTax = $taxRate > 0 ? $itemSubtotal * $taxRate / (100 + $taxRate) : 0;

            $subtotal += $itemSubtotal;
            $tax      += $itemTax;

            if ($taxRate == 0) {
                $subtotalExenta += $itemSubtotal;
            } elseif ($taxRate == 5) {
                $subtotal5 += $itemSubtotal;
                $tax5      += $itemTax;
            } else {
                $subtotal10 += $itemSubtotal;
                $tax10      += $itemTax;
            }
        }

        // Total = subtotal - discount (IVA already included in prices)
        return [
            'subtotal'        => $subtotal,
            'tax'             => $tax,
            'discount'        => $discount,
            'total'           => $subtotal - $discount,
            'subtotal_exenta' => $subtotalExenta,
            'subtotal_5'      => $subtotal5,
            'subtotal_10'     => $subtotal10,
            'tax_5'           => $tax5,
            'tax_10'          => $tax10,
        ];
    }

    /**
     * Aprueba una nota de pedido (venta pendiente), cambiando su estado a 'completed'
     * y descontando el stock del inventario. Opcionalmente registra los pagos recibidos.
     *
     * @param  Sale  $sale  La venta pendiente a aprobar.
     * @param  array  $payments  Pagos a registrar (cada uno con method, amount y reference).
     * @return Sale La venta actualizada.
     */
    public function approveSale(Sale $sale, array $payments = []): Sale
    {
        return DB::transaction(function () use ($sale, $payments) {
            foreach ($sale->items as $item) {
                $warehouse = $sale->branch->warehouses()->first();
                if ($warehouse) {
                    $this->inventoryService->removeStock(
                        $item->productVariant,
                        $warehouse,
                        $item->quantity,
                        [
                            'type' => 'sale',
                            'reference_id' => $sale->id,
                            'reference_type' => Sale::class,
                            'notes' => "Aprobación de Nota de Pedido #{$sale->id}",
                        ]
                    );
                }
            }

            if (! empty($payments)) {
                foreach ($payments as $payment) {
                    Payment::create([
                        'sale_id' => $sale->id,
                        'method' => $payment['method'],
                        'amount' => $payment['amount'],
                        'reference' => $payment['reference'] ?? null,
                        'payment_date' => now(),
                    ]);
                }
            }

            $sale->update(['status' => 'completed']);

            return $sale;
        });
    }

    /**
     * Cancela una venta y devuelve el stock de todos sus ítems al inventario.
     *
     * @param  Sale  $sale  La venta a cancelar.
     * @return Sale La venta actualizada con estado 'cancelled'.
     */
    public function cancelSale(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                $warehouse = $sale->branch->warehouses()->first();
                if ($warehouse) {
                    $this->inventoryService->addStock(
                        $item->productVariant,
                        $warehouse,
                        $item->quantity,
                        [
                            'type' => 'return',
                            'reference_id' => $sale->id,
                            'reference_type' => Sale::class,
                            'notes' => "Devolución venta #{$sale->id}",
                        ]
                    );
                }
            }

            $sale->update(['status' => 'cancelled']);

            return $sale;
        });
    }

    /**
     * Obtiene todas las ventas completadas de una fecha específica, opcionalmente filtradas por sucursal.
     *
     * @param  mixed  $date  La fecha para filtrar las ventas (formato compatible con whereDate).
     * @param  int|null  $branchId  El ID de la sucursal para filtrar, o null para todas las sucursales.
     * @return Collection Colección de ventas con relaciones de cliente, usuario y pagos.
     */
    public function getSalesByDate($date, $branchId = null)
    {
        $query = Sale::whereDate('sale_date', $date)
            ->where('status', 'completed');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->with(['customer', 'user', 'payments'])->get();
    }

    /**
     * Obtiene los productos más vendidos en un rango de fechas.
     *
     * @param  mixed  $startDate  Fecha de inicio del rango.
     * @param  mixed  $endDate  Fecha de fin del rango.
     * @param  int  $limit  Cantidad máxima de productos a retornar (por defecto 10).
     * @return Collection Colección de ítems de venta agrupados por variante, con total_sold y total_revenue.
     */
    public function getTopProducts($startDate, $endDate, $limit = 10)
    {
        return SaleItem::whereHas('sale', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate])
                ->where('status', 'completed');
        })
            ->selectRaw('product_variant_id, SUM(quantity) as total_sold, SUM(subtotal) as total_revenue')
            ->groupBy('product_variant_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->with('productVariant.product')
            ->get();
    }
}
