<?php

namespace App\Filament\Resources\InventoryCountResource\Pages;

use App\Filament\Resources\InventoryCountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\ProductVariant;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CreateInventoryCount extends CreateRecord
{
    protected static string $resource = InventoryCountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'in_progress';
        $data['started_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var \App\Models\InventoryCount $record */
        $record = $this->record;

        // Populate items with all active product variants
        $variants = ProductVariant::whereHas('product', fn ($q) => $q->where('active', true))->get();
        
        $itemsData = [];
        $now = now();
        
        foreach ($variants as $variant) {
            $stock = \App\Models\Stock::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $record->warehouse_id)
                ->first();

            $systemQty = $stock ? $stock->quantity : 0;

            $itemsData[] = [
                'inventory_count_id' => $record->id,
                'product_variant_id' => $variant->id,
                'system_quantity' => $systemQty,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Chunk insert to avoid hitting DB limits
        foreach (array_chunk($itemsData, 500) as $chunk) {
            \App\Models\InventoryCountItem::insert($chunk);
        }

        Notification::make()
            ->title('Inventario inicializado')
            ->body('Se han cargado ' . count($itemsData) . ' productos para contar.')
            ->success()
            ->send();
    }
}
