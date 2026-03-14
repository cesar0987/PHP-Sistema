<?php

namespace App\Filament\Resources\InventoryCountResource\Pages;

use App\Filament\Resources\InventoryCountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryCounts extends ListRecords
{
    protected static string $resource = InventoryCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
