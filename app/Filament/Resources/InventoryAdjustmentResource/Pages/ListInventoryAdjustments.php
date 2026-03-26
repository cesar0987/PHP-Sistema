<?php

namespace App\Filament\Resources\InventoryAdjustmentResource\Pages;

use App\Filament\Resources\InventoryAdjustmentResource;
use App\Models\Warehouse;
use App\Services\StockImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInventoryAdjustments extends ListRecords
{
    protected static string $resource = InventoryAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Botón descargar plantilla CSV de stock
            Actions\Action::make('download_stock_template')
                ->label('Descargar Plantilla')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('stock.import.template'))
                ->openUrlInNewTab(false),

            // Botón importar actualización de stock
            Actions\Action::make('import_stock')
                ->label('Importar Stock CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Forms\Components\Section::make('Importar Actualización de Stock')
                        ->description('Subí un CSV con SKU o barcode y la cantidad a aplicar.')
                        ->schema([
                            Forms\Components\FileUpload::make('csv_file')
                                ->label('Archivo CSV')
                                ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', '.csv'])
                                ->maxSize(5120)
                                ->required()
                                ->helperText('Máximo 5 MB. Solo archivos .csv'),

                            Forms\Components\Select::make('warehouse_id')
                                ->label('Almacén')
                                ->options(
                                    Warehouse::query()
                                        ->when(
                                            ! auth()->user()?->hasRole('admin'),
                                            fn ($q) => $q->where('branch_id', auth()->user()?->branch_id)
                                        )
                                        ->pluck('name', 'id')
                                )
                                ->required()
                                ->helperText('Almacén donde se aplican los cambios de stock.'),

                            Forms\Components\Select::make('mode')
                                ->label('Modo de actualización')
                                ->options([
                                    'absoluto' => 'Absoluto — fijar la cantidad exacta',
                                    'ajuste'   => 'Ajuste — sumar o restar al stock actual',
                                ])
                                ->default('absoluto')
                                ->required()
                                ->live()
                                ->helperText(fn ($state) => match ($state) {
                                    'absoluto' => 'Si el CSV dice 25, el stock queda en 25 unidades.',
                                    'ajuste'   => 'Si el CSV dice -5, resta 5 del stock actual. Si dice +10, suma 10.',
                                    default    => '',
                                }),

                            Forms\Components\TextInput::make('motivo')
                                ->label('Motivo del ajuste')
                                ->default('Actualización masiva CSV')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Queda registrado en el log de auditoría de cada ajuste.'),

                            Forms\Components\Placeholder::make('format_info')
                                ->label('Columnas del CSV')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">'
                                    . '<p><strong>Identificación</strong> (al menos una): <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">sku</code> o <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">barcode</code></p>'
                                    . '<p><strong>Obligatoria:</strong> <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">cantidad</code></p>'
                                    . '<p><strong>Opcional:</strong> <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">nombre</code> (referencia), <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">motivo</code> (anula el campo de arriba por fila)</p>'
                                    . '<p class="text-xs text-gray-500 mt-2">En modo <strong>Ajuste</strong> podés usar valores negativos (ej. <code>-5</code>) para restar stock.</p>'
                                    . '</div>'
                                )),
                        ]),
                ])
                ->action(function (array $data): void {
                    $warehouse = Warehouse::findOrFail($data['warehouse_id']);

                    $file     = $data['csv_file'];
                    $filePath = is_string($file)
                        ? storage_path('app/public/' . $file)
                        : $file->getRealPath();

                    /** @var StockImportService $service */
                    $service = app(StockImportService::class);
                    $result  = $service->import($filePath, $warehouse, $data['mode'], $data['motivo']);

                    $body = "Actualizados: {$result['updated']} | Sin cambios: {$result['skipped']}";

                    if (! empty($result['errors'])) {
                        $errCount = count($result['errors']);
                        $preview  = implode("\n", array_slice($result['errors'], 0, 5));
                        $extra    = $errCount > 5 ? "\n... y " . ($errCount - 5) . ' más.' : '';

                        Notification::make()
                            ->title("Importación completada con {$errCount} error(es)")
                            ->body($body . "\n\nErrores:\n" . $preview . $extra)
                            ->warning()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Stock actualizado correctamente')
                            ->body($body)
                            ->success()
                            ->send();
                    }
                })
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Importar')
                ->modalCancelActionLabel('Cancelar'),

            Actions\CreateAction::make(),
        ];
    }
}
