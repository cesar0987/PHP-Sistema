<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class SalesTodayWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->startOfDay()->subDay();

        $todayData = Cache::remember('widget_sales_today_'.$today->format('Y-m-d'), 300, function () use ($today) {
            return [
                'total' => Sale::whereDate('sale_date', $today)
                    ->where('status', 'completed')
                    ->sum('total'),
                'count' => Sale::whereDate('sale_date', $today)
                    ->where('status', 'completed')
                    ->count(),
            ];
        });

        $yesterdaySales = Cache::remember('widget_sales_yesterday_'.$yesterday->format('Y-m-d'), 300, function () use ($yesterday) {
            return Sale::whereDate('sale_date', $yesterday)
                ->where('status', 'completed')
                ->sum('total');
        });

        $percentage = $yesterdaySales > 0
            ? (($todayData['total'] - $yesterdaySales) / $yesterdaySales) * 100
            : 0;

        return [
            Stat::make('Ventas de Hoy', number_format($todayData['total'], 0, ',', '.').' Gs')
                ->description($todayData['count'].' transacciones')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color($percentage >= 0 ? 'success' : 'danger'),
            Stat::make('Vs. Ayer', number_format($yesterdaySales, 0, ',', '.').' Gs')
                ->description(number_format($percentage, 1).'%')
                ->descriptionIcon($percentage >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'),
        ];
    }
}
