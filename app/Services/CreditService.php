<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\CustomerPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditService
{
    /**
     * Registra un pago o deuda inicial basado en una venta.
     */
    public function recordSalePayment(Sale $sale, float $amountPaid): void
    {
        if (! $sale->customer_id) {
            return;
        }

        DB::transaction(function () use ($sale, $amountPaid) {
            // Si es contado y no pasaron amount_paid, asuminos el total
            if ($sale->payment_method === 'contado' && $amountPaid <= 0) {
                $amountPaid = $sale->total;
            }

            // Si el monto pagado > 0, registramos la entrega (pago)
            if ($amountPaid > 0) {
                CustomerPayment::create([
                    'customer_id' => $sale->customer_id,
                    'sale_id'     => $sale->id,
                    'amount'      => $amountPaid,
                    'date'        => now()->toDateString(),
                    'method'      => $sale->payment_method === 'credito' ? 'Entrega a cuenta' : 'Pagado al contado',
                    'notes'       => "Pago inicial de Factura/Ticket #{$sale->id}",
                ]);
            }

            // Calculamos el saldo actual del cliente
            $this->updateCustomerBalance($sale->customer);
        });
    }

    /**
     * Actualiza el saldo (current_balance) del cliente sumando las deudas y restando los pagos.
     */
    public function updateCustomerBalance($customer): void
    {
        if (! $customer) {
            return;
        }

        // Total de ventas a crédito (deuda total generada)
        $totalDebt = $customer->sales()
            ->where('payment_method', 'credito')
            ->where('status', 'completed')
            ->sum('total');

        // Total de pagos realizados por el cliente
        $totalPaid = $customer->payments()->sum('amount');

        // El saldo actual es la deuda menos los pagos (si > 0 debe dinero todavía)
        $balance = $totalDebt - $totalPaid;

        // Idealmente el Customer model tendría la columna current_balance
        // Por ahora, asumimos que existe o podemos dejarlo sin guardar si la columna no existe aún
        // Verificamos si la columna current_balance existe:
        if (\Illuminate\Support\Facades\Schema::hasColumn('customers', 'current_balance')) {
            $customer->update(['current_balance' => $balance]);
        }
    }
}
