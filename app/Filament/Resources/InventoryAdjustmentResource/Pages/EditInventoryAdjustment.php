<?php

namespace App\Filament\Resources\InventoryAdjustmentResource\Pages;

use App\Filament\Resources\InventoryAdjustmentResource;
use App\Models\InventoryAdjustment;
use App\Models\StockMovement;
use App\Services\InventoryService;
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
            $hasMovements = StockMovement::where('reference_type', InventoryAdjustment::class)
                ->where('reference_id', $this->record->id)
                ->exists();

            if (! $hasMovements) {
                app(InventoryService::class)->processAdjustment($this->record);
            }
        }
    }
}
