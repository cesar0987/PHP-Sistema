<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function afterCreate(): void
    {
        /** @var Purchase $purchase */
        $purchase = $this->record;

        // Only add stock if the purchase is marked as received
        if ($purchase->status !== 'received') {
            return;
        }

        $inventoryService = app(InventoryService::class);
        $warehouse = $purchase->warehouse;

        if (! $warehouse) {
            return;
        }

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
