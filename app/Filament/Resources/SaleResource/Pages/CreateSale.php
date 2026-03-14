<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    public function mount(): void
    {
        parent::mount();

        $hasOpenRegister = \App\Models\CashRegister::where('user_id', auth()->id())
            ->where('status', 'open')
            ->exists();

        if (! $hasOpenRegister) {
            \Filament\Notifications\Notification::make()
                ->title('Caja Cerrada')
                ->body('Debe abrir una caja antes de poder realizar una venta.')
                ->danger()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function afterCreate(): void
    {
        /** @var Sale $sale */
        $sale = $this->record;

        // Only deduct stock if the sale is completed
        if ($sale->status !== 'completed') {
            return;
        }

        $inventoryService = app(InventoryService::class);
        $warehouse = $sale->branch?->warehouses()?->first();

        if (! $warehouse) {
            return;
        }

        foreach ($sale->items as $item) {
            try {
                $inventoryService->removeStock(
                    $item->productVariant,
                    $warehouse,
                    $item->quantity,
                    [
                        'type' => 'sale',
                        'reference_id' => $sale->id,
                        'reference_type' => Sale::class,
                        'notes' => "Venta #{$sale->id}",
                    ]
                );
            } catch (\Exception $e) {
                // Log the error but don't block the sale creation
                Log::warning("Stock insuficiente para variante {$item->product_variant_id}: {$e->getMessage()}");
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
