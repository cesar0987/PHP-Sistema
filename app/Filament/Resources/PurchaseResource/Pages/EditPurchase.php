<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Services\InventoryService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected ?string $oldStatus = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldStatus = $this->record->status;
        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Purchase $purchase */
        $purchase = $this->record;

        $inventoryService = app(InventoryService::class);
        $warehouse = $purchase->warehouse;

        if (! $warehouse) {
            return;
        }

        // Si pasó de algo distinto a received -> a received: agregamos el stock
        if ($this->oldStatus !== 'received' && $purchase->status === 'received') {
            foreach ($purchase->items as $item) {
                $inventoryService->addStock(
                    $item->productVariant,
                    $warehouse,
                    $item->quantity,
                    [
                        'type' => 'purchase',
                        'reference_id' => $purchase->id,
                        'reference_type' => Purchase::class,
                        'notes' => "Compra #{$purchase->id}",
                    ]
                );
            }
        } 
        // Si estaba received y pasó a pending/cancelled: removemos el stock
        elseif ($this->oldStatus === 'received' && $purchase->status !== 'received') {
            foreach ($purchase->items as $item) {
                try {
                    $inventoryService->removeStock(
                        $item->productVariant,
                        $warehouse,
                        $item->quantity,
                        [
                            'type' => 'purchase_return',
                            'reference_id' => $purchase->id,
                            'reference_type' => Purchase::class,
                            'notes' => "Reversión de Compra #{$purchase->id}",
                        ]
                    );
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("No se pudo revertir stock de compra {$purchase->id}: " . $e->getMessage());
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
