<?php

namespace App\Filament\Resources\CashRegisterResource\Pages;

use App\Filament\Resources\CashRegisterResource;
use Filament\Actions;
use App\Filament\Resources\CashRegisterResource\Widgets\CashRegisterStats;
use Filament\Resources\Pages\ViewRecord;

class ViewCashRegister extends ViewRecord
{
    protected static string $resource = CashRegisterResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CashRegisterStats::class ,
        ];
    }
}