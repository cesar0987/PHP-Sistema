<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptTemplateResource\Pages;
use App\Models\ReceiptTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReceiptTemplateResource extends Resource
{
    protected static ?string $model = ReceiptTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'Plantilla de Impresión';

    protected static ?string $pluralModelLabel = 'Plantillas de Impresión';

    protected static ?int $navigationSort = 90;

    /**
     * Categorías para agrupar los tipos de plantilla.
     */
    public static function getTypeOptions(): array
    {
        return [
            'Ventas' => [
                'sale_ticket' => '🧾 Ticket de Venta (80mm)',
                'sale_invoice' => '📄 Factura de Venta (A4)',
                'sale_receipt' => '📋 Recibo de Venta',
            ],
            'Compras' => [
                'purchase_ticket' => '🧾 Ticket de Compra (80mm)',
                'purchase_invoice' => '📄 Factura de Compra (A4)',
            ],
            'Reportes' => [
                'cash_register_report' => '📊 Reporte de Caja',
            ],
        ];
    }

    /**
     * Mapa plano tipo → etiqueta para la tabla.
     */
    public static function getTypeLabelMap(): array
    {
        $map = [];
        foreach (self::getTypeOptions() as $group => $options) {
            foreach ($options as $key => $label) {
                $map[$key] = $label;
            }
        }

        return $map;
    }

    /**
     * Categoría a la que pertenece un tipo.
     */
    public static function getCategoryForType(string $type): string
    {
        foreach (self::getTypeOptions() as $group => $options) {
            if (array_key_exists($type, $options)) {
                return $group;
            }
        }

        return 'Otros';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Plantilla')
                    ->description('Datos básicos de la plantilla de impresión.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Plantilla')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Factura de Venta Personalizada'),
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Comprobante')
                            ->options(self::getTypeOptions())
                            ->required()
                            ->searchable()
                            ->unique(ignoreRecord: true)
                            ->helperText('Cada tipo solo puede tener una plantilla activa.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Plantilla activa')
                            ->helperText('Si está activa, se usará al imprimir este tipo de documento.')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('Contenido HTML (Blade)')
                    ->description('Editor del contenido de la plantilla. Usa código HTML/CSS y variables Blade.')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        Forms\Components\Placeholder::make('variables_help')
                            ->label('Variables disponibles')
                            ->content(fn (Forms\Get $get) => match (true) {
                                str_starts_with($get('type') ?? '', 'sale') => '{{ $sale->total }}, {{ $sale->customer->name }}, {{ $sale->items }}, {{ $sale->user->name }}, {{ $sale->branch->name }}, {{ $company->name }}, {{ $company->logo }}, {{ $receipt->number }}',
                                str_starts_with($get('type') ?? '', 'purchase') => '{{ $purchase->total }}, {{ $purchase->supplier->name }}, {{ $purchase->items }}, {{ $purchase->user->name }}, {{ $purchase->branch->name }}, {{ $company->name }}, {{ $company->logo }}, {{ $receipt->number }}',
                                default => 'Selecciona un tipo para ver las variables disponibles.',
                            })
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('content_html')
                            ->label('')
                            ->rows(30)
                            ->columnSpanFull()
                            ->extraAttributes(['style' => 'font-family: monospace; font-size: 12px; line-height: 1.4;'])
                            ->placeholder('<!DOCTYPE html>
<html>
<head>
    <style>/* tu CSS aquí */</style>
</head>
<body>
    <!-- tu plantilla aquí -->
</body>
</html>'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-o-document-text'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::getTypeLabelMap()[$state] ?? $state)
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, 'sale') => 'success',
                        str_starts_with($state, 'purchase') => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ventas' => 'success',
                        'Compras' => 'info',
                        'Reportes' => 'warning',
                        default => 'gray',
                    })
                    ->getStateUsing(fn (ReceiptTemplate $record): string => self::getCategoryForType($record->type)),
                Tables\Columns\TextColumn::make('format')
                    ->label('Formato')
                    ->getStateUsing(fn (ReceiptTemplate $record): string => match (true) {
                        str_contains($record->type, 'ticket') => '🖨️ Ticket 80mm',
                        str_contains($record->type, 'invoice') => '📄 Hoja A4',
                        str_contains($record->type, 'report') => '📊 Reporte',
                        str_contains($record->type, 'receipt') => '📋 Recibo',
                        default => '📄 Documento',
                    }),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activa'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última edición')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('type')
            ->groups([
                Tables\Grouping\Group::make('category')
                    ->label('Categoría')
                    ->getTitleFromRecordUsing(fn (ReceiptTemplate $record): string => self::getCategoryForType($record->type))
                    ->collapsible(),
            ])
            ->defaultGroup('category')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'Ventas' => 'Ventas',
                        'Compras' => 'Compras',
                        'Reportes' => 'Reportes',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        $typeOptions = self::getTypeOptions();
                        $types = array_keys($typeOptions[$data['value']] ?? []);

                        return $query->whereIn('type', $types);
                    }),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\Action::make('reset_default')
                    ->label('Restaurar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Restaurar plantilla a los valores por defecto')
                    ->modalDescription('El contenido HTML será reemplazado con la plantilla original del sistema. Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, restaurar')
                    ->action(function (ReceiptTemplate $record) {
                        $viewMap = [
                            'sale_ticket' => 'pdf/ticket.blade.php',
                            'sale_invoice' => 'pdf/invoice.blade.php',
                            'sale_receipt' => 'pdf/receipt.blade.php',
                            'purchase_ticket' => 'pdf/purchase_ticket.blade.php',
                            'purchase_invoice' => 'pdf/purchase_invoice.blade.php',
                            'cash_register_report' => 'pdf/cash_register_report.blade.php',
                        ];

                        $viewFile = $viewMap[$record->type] ?? null;
                        if ($viewFile && file_exists(resource_path("views/{$viewFile}"))) {
                            $record->update([
                                'content_html' => file_get_contents(resource_path("views/{$viewFile}")),
                            ]);

                            Notification::make()
                                ->title('Plantilla restaurada')
                                ->body("La plantilla '{$record->name}' fue restaurada a su contenido por defecto.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Sin plantilla por defecto')
                                ->body("No se encontró un archivo de plantilla por defecto para el tipo '{$record->type}'.")
                                ->warning()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No hay plantillas de impresión')
            ->emptyStateDescription('Crea plantillas para personalizar cómo se imprimen los comprobantes de venta, compra y reportes.')
            ->emptyStateIcon('heroicon-o-document-duplicate');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceiptTemplates::route('/'),
            'create' => Pages\CreateReceiptTemplate::route('/create'),
            'edit' => Pages\EditReceiptTemplate::route('/{record}/edit'),
        ];
    }
}
