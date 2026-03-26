<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Warehouse;
use App\Services\ProductImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Botón descargar plantilla CSV
            Actions\Action::make('download_template')
                ->label('Descargar Plantilla')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('products.import.template'))
                ->openUrlInNewTab(false),

            // Botón importar CSV
            Actions\Action::make('import_products')
                ->label('Importar CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Forms\Components\Section::make('Importar Productos')
                        ->description('Subí un archivo CSV con los productos a importar. Usá la plantilla para asegurarte del formato correcto.')
                        ->schema([
                            Forms\Components\FileUpload::make('csv_file')
                                ->label('Archivo CSV')
                                ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', '.csv'])
                                ->maxSize(5120)
                                ->required()
                                ->helperText('Máximo 5 MB. Solo archivos .csv'),

                            Forms\Components\Select::make('warehouse_id')
                                ->label('Almacén para stock inicial')
                                ->options(
                                    Warehouse::query()
                                        ->when(
                                            ! auth()->user()?->hasRole('admin'),
                                            fn ($q) => $q->where('branch_id', auth()->user()?->branch_id)
                                        )
                                        ->pluck('name', 'id')
                                )
                                ->required()
                                ->helperText('El stock inicial de cada producto se cargará en este almacén.'),

                            Forms\Components\Placeholder::make('format_info')
                                ->label('Columnas del CSV')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">'
                                    . '<p><strong>Obligatorias:</strong> <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">nombre</code>, <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">precio_venta</code></p>'
                                    . '<p><strong>Opcionales:</strong> <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">sku</code>, <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">barcode</code>, <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">categoria</code>, <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">marca</code>, <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">precio_costo</code>, <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">iva</code> (0/5/10), <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">stock_inicial</code>, <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">stock_minimo</code>, <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">descripcion</code></p>'
                                    . '<p class="text-xs text-gray-500 mt-2">Si ya existe un producto con el mismo SKU o barcode, se actualizará en lugar de crear uno nuevo.</p>'
                                    . '</div>'
                                )),
                        ]),
                ])
                ->action(function (array $data): void {
                    $warehouse = Warehouse::findOrFail($data['warehouse_id']);

                    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file */
                    $file = $data['csv_file'];

                    // Filament FileUpload devuelve el nombre en disco dentro del storage temporal
                    $filePath = is_string($file)
                        ? storage_path('app/public/' . $file)
                        : $file->getRealPath();

                    /** @var ProductImportService $service */
                    $service = app(ProductImportService::class);
                    $result  = $service->import($filePath, $warehouse);

                    $body = "Creados: {$result['created']} | Actualizados: {$result['updated']}";

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
                            ->title('Importación exitosa')
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
