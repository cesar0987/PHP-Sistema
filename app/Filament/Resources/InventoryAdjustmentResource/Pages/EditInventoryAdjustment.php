<?php

namespace App\Filament\Resources\InventoryAdjustmentResource\Pages;

use App\Filament\Resources\InventoryAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryAdjustment extends EditRecord
{
    protected static string $resource = InventoryAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->status === 'approved') {
            $hasMovements = \App\Models\StockMovement::where('reference_type', \App\Models\InventoryAdjustment::class)
                ->where('reference_id', $this->record->id)
                ->exists();
                
            if (!$hasMovements) {
                app(\App\Services\InventoryService::class)->processAdjustment($this->record);
            }
        }
    }
}
