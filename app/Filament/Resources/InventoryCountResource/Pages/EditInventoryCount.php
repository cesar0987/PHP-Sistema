<?php

namespace App\Filament\Resources\InventoryCountResource\Pages;

use App\Filament\Resources\InventoryCountResource;
use App\Models\InventoryAdjustment;
use App\Models\InventoryCountItem;
use App\Services\InventoryService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditInventoryCount extends EditRecord
{
    protected static string $resource = InventoryCountResource::class;

    public function form(Forms\Form $form): Forms\Form
    {
        return parent::form($form)->schema([
            Forms\Components\Section::make('Información')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('status_display')
                                ->label('Estado')
                                ->default(fn () => match ($this->record->status) {
                                    'in_progress' => 'En Progreso',
                                    'completed' => 'Completado',
                                    'cancelled' => 'Cancelado',
                                    default => 'Pendiente',
                                })
                                ->disabled(),
                            Forms\Components\TextInput::make('warehouse_display')
                                ->label('Almacén')
                                ->default(fn () => $this->record->warehouse->name)
                                ->disabled(),
                        ]),
                ]),

            Forms\Components\Section::make('Productos a Contar')
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\Hidden::make('product_variant_id'),
                            Forms\Components\TextInput::make('product_name')
                                ->label('Producto')
                                ->formatStateUsing(fn ($record) => $record?->productVariant?->product?->name.' - '.$record?->productVariant?->sku)
                                ->disabled()
                                ->columnSpan(3),
                            Forms\Components\TextInput::make('system_quantity')
                                ->label('Stock Sistema')
                                ->disabled()
                                ->numeric()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('counted_quantity')
                                ->label('Conteo Físico')
                                ->numeric()
                                ->required()
                                ->disabled(fn () => $this->record->status !== 'in_progress')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $sys = (int) $get('system_quantity');
                                    $counted = (int) $state;
                                    $set('difference', $counted - $sys);
                                    $set('is_matched', $counted === $sys);
                                })
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('difference')
                                ->label('Diferencia')
                                ->disabled()
                                ->numeric()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('notes')
                                ->label('Notas')
                                ->disabled(fn () => $this->record->status !== 'in_progress')
                                ->columnSpan(3),
                        ])
                        ->columns(12)
                        ->disableItemCreation()
                        ->disableItemDeletion()
                        ->disableItemMovement()
                        ->defaultItems(0), // Loaded from relation
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('completar')
                ->label('Cerrar y Ajustar Inventario')
                ->requiresConfirmation()
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->modalHeading('Finalizar Toma Física')
                ->modalDescription('¿Estás seguro de finalizar? Esto generará un Ajuste de Inventario automático por las diferencias encontradas.')
                ->visible(fn () => $this->record->status === 'in_progress')
                ->action(function () {
                    $this->save(); // Save form changes first

                    DB::transaction(function () {
                        $count = $this->record;
                        $count->status = 'completed';
                        $count->completed_at = now();
                        $count->save();

                        // Crear reporte de ajuste si hay diferencias
                        $hasDiff = $count->items()->where('is_matched', false)->exists();

                        if ($hasDiff) {
                            $adj = InventoryAdjustment::create([
                                'warehouse_id' => $count->warehouse_id,
                                'user_id' => auth()->id(),
                                'reason' => 'Auditoría Física #'.$count->id,
                                'status' => 'approved', // Auto aprobar
                            ]);

                            $service = app(InventoryService::class);

                            foreach (InventoryCountItem::where('inventory_count_id', $count->id)->get() as $item) {
                                if (! $item->is_matched && $item->counted_quantity !== null) {
                                    $adj->items()->create([
                                        'product_variant_id' => $item->product_variant_id,
                                        'expected_quantity' => $item->system_quantity,
                                        'actual_quantity' => $item->counted_quantity,
                                    ]);

                                    $service->adjustStock(
                                        $item->productVariant,
                                        $count->warehouse,
                                        $item->counted_quantity,
                                        'Toma Física #'.$count->id,
                                        auth()->id()
                                    );
                                }
                            }
                        }
                    });

                    Notification::make()
                        ->title('Toma finalizada con éxito.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status !== 'completed'),
        ];
    }
}
