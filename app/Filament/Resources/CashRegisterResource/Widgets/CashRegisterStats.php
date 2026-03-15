<?php

namespace App\Filament\Resources\CashRegisterResource\Widgets;

use App\Models\CashRegister;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class CashRegisterStats extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        /** @var CashRegister $cashRegister */
        $cashRegister = $this->record;

        if (! $cashRegister) {
            return [];
        }

        $totalSales = $cashRegister->sales()->where('status', 'completed')->where('payment_method', 'contado')->sum('total');
        $customerPayments = \App\Models\CustomerPayment::whereHas('sale', fn($q) => $q->where('cash_register_id', $cashRegister->id))
            ->whereBetween('created_at', [$cashRegister->opened_at, $cashRegister->closed_at ?? now()])
            ->sum('amount');

        $expectedCash = $cashRegister->opening_amount + $totalSales + $customerPayments;

        return [
            Stat::make('Monto de Apertura', number_format($cashRegister->opening_amount, 0, ',', '.').' Gs')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Total Vendido (Pagado)', number_format($totalSales, 0, ',', '.').' Gs')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('Efectivo Esperado', number_format($expectedCash, 0, ',', '.').' Gs')
                ->icon('heroicon-o-calculator')
                ->description('Apertura + Total Vendido')
                ->color('info'),
        ];
    }
}
