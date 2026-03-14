<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        $hasOpenRegister = \App\Models\CashRegister::where('user_id', auth()->id())
            ->where('status', 'open')
            ->exists();

        return [
            Actions\CreateAction::make()
                ->disabled(! $hasOpenRegister)
                ->tooltip(fn () => ! $hasOpenRegister ? 'Debe tener una caja abierta para crear una venta' : null),
        ];
    }
}
