<?php

namespace App\Filament\Resources\StockBatchResource\Pages;

use App\Filament\Resources\StockBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStockBatches extends ManageRecords
{
    protected static string $resource = StockBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
