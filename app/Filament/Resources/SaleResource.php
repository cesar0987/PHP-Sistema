<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages\CreateSale;
use App\Filament\Resources\SaleResource\Pages\EditSale;
use App\Filament\Resources\SaleResource\Pages\ListSales;
use App\Models\Branch;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Services\SaleService;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?string $modelLabel = 'Venta';

    protected static ?string $pluralModelLabel = 'Ventas';

    protected static int $defaultTableRecordsPerPage = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- Header: Info de la venta ---
                Forms\Components\Section::make('Datos de la venta')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('Cliente')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Consumidor final')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->label('Nombre')->required(),
                                        Forms\Components\TextInput::make('document')->label('RUC / CI'),
                                        Forms\Components\TextInput::make('phone')->label('Telefono'),
                                        Forms\Components\TextInput::make('email')->label('Email')->email(),
                                        Forms\Components\TextInput::make('address')->label('Direccion'),
                                    ]),
                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->relationship('branch', 'name')
                                    ->required()
                                    ->default(fn () => Branch::first()?->id),
                                Forms\Components\Select::make('user_id')
                                    ->label('Vendedor')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->default(fn () => auth()->id()),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('sale_date')
                                    ->label('Fecha de venta')
                                    ->required()
                                    ->default(now()),
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'pending' => 'Nota de Pedido',
                                        'completed' => 'Completado',
                                        'cancelled' => 'Cancelado',
                                        'returned' => 'Devuelto',
                                    ])
                                    ->default('completed')
                                    ->required(),
                                Forms\Components\Select::make('condition')
                                    ->label('Condición')
                                    ->options([
                                        'contado' => 'Contado',
                                        'credito' => 'Crédito',
                                    ])
                                    ->default('contado')
                                    ->required(),
                                Forms\Components\Select::make('document_type')
                                    ->label('Tipo de Documento')
                                    ->options([
                                        'ticket' => 'Ticket (Interno)',
                                        'invoice' => 'Factura (SIFEN/Legal)',
                                    ])
                                    ->default('ticket')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state === 'ticket') {
                                            $set('invoice_number', 'TCK-' . strtoupper(substr(uniqid(), -6)));
                                            $set('timbrado', null);
                                            $set('cdc', null);
                                        } else {
                                            $set('invoice_number', null);
                                        }
                                    })
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Nro. Documento / Factura')
                                    ->placeholder(fn (Get $get) => $get('document_type') === 'invoice' ? '001-001-1234567' : 'TCK-XXXXX')
                                    ->regex(fn (Get $get) => $get('document_type') === 'invoice' ? '/^\d{3}-\d{3}-\d{7}$/' : null)
                                    ->default(fn() => 'TCK-' . strtoupper(substr(uniqid(), -6)))
                                    ->maxLength(15)
                                    ->required(),
                                Forms\Components\TextInput::make('timbrado')
                                    ->label('Timbrado')
                                    ->numeric()
                                    ->length(8)
                                    ->visible(fn (Get $get) => $get('document_type') === 'invoice'),
                                Forms\Components\TextInput::make('cdc')
                                    ->label('CDC (Factura Electrónica)')
                                    ->numeric()
                                    ->length(44)
                                    ->columnSpan(2)
                                    ->visible(fn (Get $get) => $get('document_type') === 'invoice'),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('cash_register_id')
                                    ->label('Caja')
                                    ->relationship('cashRegister', 'name')
                                    ->default(fn () => \App\Models\CashRegister::where('user_id', auth()->id())->where('status', 'open')->first()?->id)
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\Toggle::make('is_b2b')
                                    ->label('Precios B2B (Sin IVA)')
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        $items = $get('items') ?? [];
                                        foreach ($items as $key => $item) {
                                            if (isset($item['product_variant_id'])) {
                                                $variant = \App\Models\ProductVariant::with('product')->find($item['product_variant_id']);
                                                if ($variant) {
                                                    $basePrice = $variant->price ?? $variant->product->sale_price;
                                                    // Si es B2B le quitamos el 10% de IVA (Dividimos por 1.1)
                                                    $price = $state ? ($basePrice / 1.1) : $basePrice;
                                                    
                                                    $set("items.{$key}.price", (float) $price);
                                                    $qty = $item['quantity'] ?? 1;
                                                    $discount = $item['discount'] ?? 0;
                                                    $set("items.{$key}.subtotal", ($price * $qty) - $discount);
                                                }
                                            }
                                        }
                                        self::updateTotals($get, $set);
                                    }),
                            ]),
                    ]),

                // --- Items: Tabla de productos ---
                Forms\Components\Section::make('Productos')
                    ->headerActions([
                        Action::make('escanear')
                            ->label('Escanear producto')
                            ->icon('heroicon-o-qr-code')
                            ->form([
                                Forms\Components\TextInput::make('barcode')
                                    ->label('Codigo de barras o SKU')
                                    ->placeholder('Escanee o ingrese el codigo...')
                                    ->autofocus()
                                    ->required(),
                            ])
                            ->action(function (array $data, Set $set, Get $get): void {
                                $variant = ProductVariant::with('product')
                                    ->where('barcode', $data['barcode'])
                                    ->orWhere('sku', $data['barcode'])
                                    ->orWhereHas('product', fn ($q) => $q->where('barcode', $data['barcode']))
                                    ->first();

                                if (! $variant) {
                                    Notification::make()
                                        ->title('Producto no encontrado')
                                        ->body("No se encontro un producto con el codigo: {$data['barcode']}")
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                $items = $get('items') ?? [];
                                $price = (float) ($variant->price ?? $variant->product->sale_price);

                                // Check if already in items
                                $found = false;
                                foreach ($items as $key => $item) {
                                    if (($item['product_variant_id'] ?? null) == $variant->id) {
                                        $items[$key]['quantity'] = ($item['quantity'] ?? 1) + 1;
                                        $items[$key]['subtotal'] = $items[$key]['quantity'] * $price - ($items[$key]['discount'] ?? 0);
                                        $found = true;
                                        break;
                                    }
                                }

                                if (! $found) {
                                    $items[] = [
                                        'product_variant_id' => $variant->id,
                                        'quantity' => 1,
                                        'price' => $get('is_b2b') ? ($price / 1.1) : $price,
                                        'discount' => 0,
                                        'subtotal' => $get('is_b2b') ? ($price / 1.1) : $price,
                                    ];
                                }

                                $set('items', $items);
                                self::updateTotals($get, $set);

                                Notification::make()
                                    ->title('Producto agregado')
                                    ->body($variant->product->name)
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_variant_id')
                                    ->label('Producto')
                                    ->options(function () {
                                        return ProductVariant::with('product')
                                            ->whereHas('product', fn ($q) => $q->where('active', true))
                                            ->get()
                                            ->mapWithKeys(fn ($v) => [
                                                $v->id => $v->product->name
                                                    .($v->color ? " - {$v->color}" : '')
                                                    .($v->size ? " - {$v->size}" : '')
                                                    ." (SKU: {$v->sku})",
                                            ]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        if ($state) {
                                            $variant = ProductVariant::with('product')->find($state);
                                            if ($variant) {
                                                $basePrice = $variant->price ?? $variant->product->sale_price;
                                                $isB2b = $get('../../is_b2b');
                                                $price = $isB2b ? ($basePrice / 1.1) : $basePrice;
                                                $set('price', (float) $price);
                                                $qty = $get('quantity') ?: 1;
                                                $discount = $get('discount') ?: 0;
                                                $set('subtotal', ($price * $qty) - $discount);
                                            }
                                        }
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $price = (float) ($get('price') ?: 0);
                                        $qty = (int) ($get('quantity') ?: 1);
                                        $discount = (float) ($get('discount') ?: 0);
                                        $set('subtotal', ($price * $qty) - $discount);
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('price')
                                    ->label('Precio unit.')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $price = (float) ($get('price') ?: 0);
                                        $qty = (int) ($get('quantity') ?: 1);
                                        $discount = (float) ($get('discount') ?: 0);
                                        $set('subtotal', ($price * $qty) - $discount);
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('discount')
                                    ->label('Desc.')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $price = (float) ($get('price') ?: 0);
                                        $qty = (int) ($get('quantity') ?: 1);
                                        $discount = (float) ($get('discount') ?: 0);
                                        $set('subtotal', ($price * $qty) - $discount);
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly()
                                    ->columnSpan(2),
                            ])
                            ->columns(11)
                            ->addActionLabel('Agregar producto')
                            ->reorderable(false)
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateTotals($get, $set);
                            })
                            ->deleteAction(
                                fn (Action $action) => $action->after(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
                            ),
                    ]),

                // --- Totals ---
                Forms\Components\Section::make('Totales')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('discount')
                                    ->label('Descuento general')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $subtotal = (float) ($get('subtotal') ?: 0);
                                        $discount = (float) ($get('discount') ?: 0);
                                        $tax = (float) ($get('tax') ?: 0);
                                        $set('total', $subtotal - $discount + $tax);
                                    }),
                                Forms\Components\TextInput::make('tax')
                                    ->label('Impuesto')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('total')
                                    ->label('TOTAL')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly(),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2),
                    ]),
            ]);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        // Detect if we are inside the repeater ('../../items') or at the root ('items')
        $isInsideRepeater = $get('../../items') !== null;
        $items = $isInsideRepeater ? $get('../../items') : ($get('items') ?? []);

        $subtotal = 0;
        $tax = 0;

        foreach ($items as $item) {
            $itemSubtotal = (float) ($item['subtotal'] ?? 0);
            $subtotal += $itemSubtotal;

            // Calculate tax from product
            if (! empty($item['product_variant_id'])) {
                $variant = ProductVariant::with('product')->find($item['product_variant_id']);
                if ($variant && $variant->product) {
                    $taxPercent = (float) ($variant->product->tax_percentage ?? 0);
                    $tax += $itemSubtotal * ($taxPercent / 100);
                }
            }
        }

        $discount = (float) ($isInsideRepeater ? $get('../../discount') : $get('discount')) ?: 0;
        $total = $subtotal - $discount + $tax;

        if ($isInsideRepeater) {
            $set('../../subtotal', round($subtotal));
            $set('../../tax', round($tax));
            $set('../../total', round($total));
        } else {
            $set('subtotal', round($subtotal));
            $set('tax', round($tax));
            $set('total', round($total));
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->placeholder('Consumidor final')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Vendedor'),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal'),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Productos')
                    ->counts('items')
                    ->suffix(' items'),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' Gs')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Descuento')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' Gs')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' Gs')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'returned' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Completado',
                        'pending' => 'Nota de Pedido',
                        'cancelled' => 'Cancelado',
                        'returned' => 'Devuelto',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Documento')
                    ->formatStateUsing(fn ($state) => $state === 'invoice' ? 'Factura' : 'Ticket')
                    ->badge()
                    ->color(fn ($state) => $state === 'invoice' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nro.')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('condition')
                    ->label('Condición')
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('sale_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Nota de Pedido',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'returned' => 'Devuelto',
                    ]),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Vendedor')
                    ->relationship('user', 'name'),
                Tables\Filters\TrashedFilter::make()
                    ->label('Eliminados'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar a Venta')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->modalHeading('Aprobar Nota de Pedido y Registrar Pagos')
                    ->modalSubmitActionLabel('Confirmar Venta')
                    ->form([
                        Forms\Components\Repeater::make('payments')
                            ->label('Pagos recibidos')
                            ->schema([
                                Forms\Components\Select::make('method')
                                    ->label('Método de pago')
                                    ->options([
                                        'cash' => 'Efectivo',
                                        'card' => 'Tarjeta',
                                        'transfer' => 'Transferencia',
                                        'qr' => 'Código QR',
                                        'other' => 'Otro',
                                    ])
                                    ->required()
                                    ->default('cash'),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Monto')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Gs'),
                                Forms\Components\TextInput::make('reference')
                                    ->label('Referencia / Comprobante'),
                            ])
                            ->defaultItems(1)
                            ->columns(3),
                    ])
                    ->action(function (Sale $record, array $data, Tables\Actions\Action $action): void {
                        try {
                            $payments = $data['payments'] ?? [];
                            $totalPagado = array_sum(array_column($payments, 'amount'));
                            
                            if ($totalPagado < $record->total) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error de validación')
                                    ->body("El monto pagado (" . number_format($totalPagado, 0, ',', '.') . ") es menor al total de la venta (" . number_format($record->total, 0, ',', '.') . ").")
                                    ->danger()
                                    ->send();
                                
                                $action->cancel();
                                return;
                            }

                            $saleService = app(\App\Services\SaleService::class);
                            $saleService->approveSale($record, $payments);

                            \Filament\Notifications\Notification::make()
                                ->title('Venta aprobada')
                                ->body("Nota de pedido #{$record->id} fue aprobada y cobrada correctamente.")
                                ->success()
                                ->send();
                        } catch (\Filament\Support\Exceptions\Halt $e) {
                            // Let Filament handle the halt exception to keep the modal open
                            throw $e;
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al aprobar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Sale $record): bool => $record->status === 'pending'),
                Tables\Actions\Action::make('cobro_rapido')
                    ->label('Cobro Rápido (Efectivo)')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Cobro rápido en efectivo')
                    ->modalDescription('¿Confirmar el pago total en efectivo por el monto exacto y aprobar la venta al instante?')
                    ->modalSubmitActionLabel('Sí, cobrar y aprobar')
                    ->action(function (Sale $record): void {
                        try {
                            $payments = [
                                [
                                    'method' => 'cash',
                                    'amount' => $record->total,
                                    'reference' => 'Cobro rápido',
                                ]
                            ];
                            $saleService = app(\App\Services\SaleService::class);
                            $saleService->approveSale($record, $payments);

                            \Filament\Notifications\Notification::make()
                                ->title('Cobro rápido exitoso')
                                ->body("Venta #{$record->id} aprobada y pagada en efectivo.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al procesar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Sale $record): bool => $record->status === 'pending'),
                Tables\Actions\Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->action(function (Sale $record) {
                        $receiptService = app(\App\Services\ReceiptService::class);
                        $receipt = $record->receipts()->where('type', 'ticket')->first();
                        if (!$receipt) {
                            $receipt = $receiptService->generateReceipt($record, 'ticket');
                        }
                        return response()->streamDownload(function () use ($receiptService, $receipt) {
                            echo $receiptService->generatePdf($receipt->sale, $receipt, 'ticket')->output();
                        }, "receipt_{$receipt->number}.pdf");
                    })
                    ->visible(fn (Sale $record): bool => in_array($record->status, ['completed', 'pending'])),
                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anular venta')
                    ->modalDescription('Esta accion cancelara la venta y devolvera el stock. Esta seguro?')
                    ->modalSubmitActionLabel('Si, anular venta')
                    ->form([
                        Forms\Components\Select::make('cancellation_reason_type')
                            ->label('Motivo de anulacion')
                            ->options([
                                'error_precio' => 'Error en precio',
                                'error_producto' => 'Producto equivocado',
                                'devolucion_cliente' => 'Devolucion del cliente',
                                'producto_defectuoso' => 'Producto defectuoso',
                                'duplicada' => 'Venta duplicada',
                                'otro' => 'Otro motivo',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('cancellation_notes')
                            ->label('Detalle adicional')
                            ->rows(2)
                            ->placeholder('Describa el motivo con mas detalle...'),
                    ])
                    ->action(function (Sale $record, array $data): void {
                        $reason = $data['cancellation_reason_type'];
                        if (! empty($data['cancellation_notes'])) {
                            $reason .= ': '.$data['cancellation_notes'];
                        }

                        try {
                            $saleService = app(SaleService::class);
                            $saleService->cancelSale($record);
                            $record->update(['cancellation_reason' => $reason]);

                            Notification::make()
                                ->title('Venta anulada')
                                ->body("Venta #{$record->id} fue anulada correctamente. Stock devuelto.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al anular')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Sale $record): bool => $record->status === 'completed'),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSales::route('/'),
            'create' => CreateSale::route('/create'),
            'edit' => EditSale::route('/{record}/edit'),
        ];
    }
}
