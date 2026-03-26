<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\CashRegister;
use App\Models\Sale;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        $hasOpenRegister = CashRegister::where('user_id', auth()->id())
            ->where('status', 'open')
            ->exists();

        return [
            Actions\CreateAction::make()
                ->disabled(! $hasOpenRegister)
                ->tooltip(fn () => ! $hasOpenRegister ? 'Debe tener una caja abierta para crear una venta' : null),
        ];
    }

    public function getTabs(): array
    {
        $pendingCount  = Sale::where('status', 'pending')->count();
        $creditCount   = Sale::where('payment_method', 'credito')
            ->where('status', 'completed')
            ->whereNotNull('credit_due_date')
            ->count();

        return [
            'todas' => Tab::make('Todas'),

            'presupuestos' => Tab::make('Notas de Pedido / Presupuestos')
                ->icon('heroicon-o-document-text')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge($pendingCount ?: null)
                ->badgeColor('warning'),

            'completadas' => Tab::make('Completadas')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),

            'creditos' => Tab::make('Créditos Activos')
                ->icon('heroicon-o-credit-card')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('payment_method', 'credito')
                    ->where('status', 'completed')
                )
                ->badge($creditCount ?: null)
                ->badgeColor('info'),
        ];
    }
}
