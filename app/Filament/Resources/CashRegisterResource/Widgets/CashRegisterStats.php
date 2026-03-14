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

        $totalSales = $cashRegister->sales()->whereIn('status', ['completed', 'pending'])->sum('total');
        $expectedCash = $cashRegister->opening_amount + $totalSales;

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
