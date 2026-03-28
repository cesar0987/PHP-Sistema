<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages\CreatePurchase;
use App\Filament\Resources\PurchaseResource\Pages\EditPurchase;
use App\Filament\Resources\PurchaseResource\Pages\ListPurchases;
use App\Models\Branch;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Warehouse;
use App\Services\ReceiptService;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Compras';

    protected static ?string $modelLabel = 'Compra';

    protected static ?string $pluralModelLabel = 'Compras';

    protected static int $defaultTableRecordsPerPage = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- Header ---
                Forms\Components\Section::make('Datos de la compra')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('supplier_id')
                                    ->label('Proveedor')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->label('Nombre')->required(),
                                        Forms\Components\TextInput::make('ruc')->label('RUC'),
                                        Forms\Components\TextInput::make('phone')->label('Telefono'),
                                        Forms\Components\TextInput::make('email')->label('Email')->email(),
                                        Forms\Components\TextInput::make('address')->label('Direccion'),
                                    ]),
                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->relationship('branch', 'name')
                                    ->required()
                                    ->default(fn () => Branch::first()?->id),
                                Forms\Components\Select::make('warehouse_id')
                                    ->label('Almacen')
                                    ->relationship('warehouse', 'name')
                                    ->required()
                                    ->default(fn () => Warehouse::first()?->id),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Responsable')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->default(fn () => auth()->id()),
                                Forms\Components\DatePicker::make('purchase_date')
                                    ->label('Fecha de compra')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now()),
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'received' => 'Recibido',
                                        'cancelled' => 'Cancelado',
                                    ])
                                    ->default('received')
                                    ->required(),
                                Forms\Components\Select::make('condition')
                                    ->label('Condición')
                                    ->options([
                                        'contado' => 'Contado',
                                        'credito' => 'Crédito',
                                    ])
                                    ->default('contado')
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Nro. Factura')
                                    ->placeholder('001-001-1234567')
                                    ->regex('/^\d{3}-\d{3}-\d{7}$/')
                                    ->maxLength(15),
                                Forms\Components\TextInput::make('timbrado')
                                    ->label('Timbrado')
                                    ->numeric()
                                    ->length(8),
                                Forms\Components\TextInput::make('cdc')
                                    ->label('CDC (Factura Electrónica)')
                                    ->numeric()
                                    ->length(44)
                                    ->columnSpan(2),
                            ]),
                    ]),

                // --- Items ---
                Forms\Components\Section::make('Productos')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->relationship()
                            ->dehydrated()
                            ->disabled(fn (?Purchase $record) => $record?->status === 'received' && $record->exists)
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
                                                $cost = $variant->product->cost_price;
                                                $set('cost', (float) $cost);
                                                $qty = $get('quantity') ?: 1;
                                                $set('subtotal', (float) $cost * (int) $qty);
                                                $set('tax_percentage', $variant->product->tax_percentage ?? 10);
                                                $set('has_expiry', clone $variant->product->has_expiry); // To trigger visibility
                                            }
                                        }
                                    })
                                    ->columnSpan(5),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $cost = (float) ($get('cost') ?: 0);
                                        $qty = (int) ($get('quantity') ?: 1);
                                        $set('subtotal', $cost * $qty);
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('cost')
                                    ->label('Costo unit.')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $cost = (float) ($get('cost') ?: 0);
                                        $qty = (int) ($get('quantity') ?: 1);
                                        $set('subtotal', $cost * $qty);
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly()
                                    ->columnSpan(2),
                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Vencimiento')
                                    ->visible(function (Get $get) {
                                        if ($get('product_variant_id')) {
                                            $variant = ProductVariant::with('product')->find($get('product_variant_id'));
                                            return $variant && $variant->product->has_expiry;
                                        }
                                        return false;
                                    })
                                    ->required(function (Get $get) {
                                        if ($get('product_variant_id')) {
                                            $variant = ProductVariant::with('product')->find($get('product_variant_id'));
                                            return $variant && $variant->product->has_expiry;
                                        }
                                        return false;                                        
                                    })
                                    ->columnSpan(2),
                                Forms\Components\Hidden::make('tax_percentage')
                                    ->default(10),
                                Forms\Components\Hidden::make('has_expiry'),
                            ])
                            ->columns(13)
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
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal_exenta')
                                    ->label('Gravadas Exentas')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('subtotal_5')
                                    ->label('Gravadas 5%')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('subtotal_10')
                                    ->label('Gravadas 10%')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly(),
                            ]),
                        Forms\Components\Grid::make([
                            'default' => 5,
                        ])
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('discount')
                                    ->label('Descuento Gral.')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateTotals($get, $set);
                                    }),
                                Forms\Components\TextInput::make('tax_5')
                                    ->label('Liq. IVA 5%')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('tax_10')
                                    ->label('Liq. IVA 10%')
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
                        Forms\Components\Hidden::make('tax'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $isInsideRepeater = $get('../../items') !== null;
        $items = $isInsideRepeater ? $get('../../items') : ($get('items') ?? []);

        $subtotal = 0;
        $subtotal_exenta = 0;
        $subtotal_5 = 0;
        $subtotal_10 = 0;
        
        $tax_5 = 0;
        $tax_10 = 0;

        foreach ($items as $key => $item) {
            $itemSubtotal = (float) ($item['subtotal'] ?? 0);
            $subtotal += $itemSubtotal;

            $taxPercent = (float) ($item['tax_percentage'] ?? 10);
            $itemTax = 0;

            if ($taxPercent == 10) {
                $subtotal_10 += $itemSubtotal;
                $itemTax = $itemSubtotal / 11;
                $tax_10 += $itemTax;
            } elseif ($taxPercent == 5) {
                $subtotal_5 += $itemSubtotal;
                $itemTax = $itemSubtotal / 21;
                $tax_5 += $itemTax;
            } else {
                $subtotal_exenta += $itemSubtotal;
            }
            
            if ($isInsideRepeater) {
                 $set('../../items.' . $key . '.tax_amount', round($itemTax, 2));
                 $set('../../items.' . $key . '.subtotal', round($itemSubtotal, 2));
            } else {
                 $set('items.' . $key . '.tax_amount', round($itemTax, 2));
                 $set('items.' . $key . '.subtotal', round($itemSubtotal, 2));
            }
        }

        $discount = (float) ($isInsideRepeater ? $get('../../discount') : $get('discount')) ?: 0;
        $total = $subtotal - $discount;

        if ($isInsideRepeater) {
            $set('../../subtotal', round($subtotal));
            $set('../../subtotal_exenta', round($subtotal_exenta));
            $set('../../subtotal_5', round($subtotal_5));
            $set('../../subtotal_10', round($subtotal_10));
            $set('../../tax_5', round($tax_5));
            $set('../../tax_10', round($tax_10));
            $set('../../tax', round($tax_5 + $tax_10));
            $set('../../total', round($total));
        } else {
            $set('subtotal', round($subtotal));
            $set('subtotal_exenta', round($subtotal_exenta));
            $set('subtotal_5', round($subtotal_5));
            $set('subtotal_10', round($subtotal_10));
            $set('tax_5', round($tax_5));
            $set('tax_10', round($tax_10));
            $set('tax', round($tax_5 + $tax_10));
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
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal'),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Almacen'),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Factura')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('condition')
                    ->label('Condición')
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Productos')
                    ->counts('items')
                    ->suffix(' productos'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' Gs')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'received' => 'Recibido',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Responsable')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('purchase_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'received' => 'Recibido',
                        'cancelled' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Proveedor')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make()
                    ->label('Eliminados'),
            ])
            ->actions([
                Tables\Actions\Action::make('imprimir_factura')
                    ->label('Imprimir Factura')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->action(function (Purchase $record) {
                        $receiptService = app(ReceiptService::class);
                        $receipt = $record->receipts()->where('type', 'purchase_invoice')->first();
                        if (! $receipt) {
                            $receipt = $receiptService->generateReceipt($record, 'purchase_invoice');
                        }

                        return response()->streamDownload(function () use ($receiptService, $receipt, $record) {
                            echo $receiptService->generatePdf($record, $receipt, 'purchase_invoice')->output();
                        }, "compra_factura_{$receipt->number}.pdf");
                    }),
                Tables\Actions\Action::make('imprimir_ticket')
                    ->label('Imprimir Ticket')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(function (Purchase $record) {
                        $receiptService = app(ReceiptService::class);
                        $receipt = $record->receipts()->where('type', 'purchase_ticket')->first();
                        if (! $receipt) {
                            $receipt = $receiptService->generateReceipt($record, 'purchase_ticket');
                        }

                        return response()->streamDownload(function () use ($receiptService, $receipt, $record) {
                            echo $receiptService->generatePdf($record, $receipt, 'purchase_ticket')->output();
                        }, "compra_ticket_{$receipt->number}.pdf");
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => ListPurchases::route('/'),
            'create' => CreatePurchase::route('/create'),
            'edit' => EditPurchase::route('/{record}/edit'),
        ];
    }
}
