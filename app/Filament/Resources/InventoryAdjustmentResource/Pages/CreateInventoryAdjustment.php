<?php

namespace App\Filament\Resources\InventoryAdjustmentResource\Pages;

use App\Filament\Resources\InventoryAdjustmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryAdjustment extends CreateRecord
{
    protected static string $resource = InventoryAdjustmentResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->status === 'approved') {
            app(\App\Services\InventoryService::class)->processAdjustment($this->record);
        }
    }
}
