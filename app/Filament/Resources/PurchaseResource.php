<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages\CreatePurchase;
use App\Filament\Resources\PurchaseResource\Pages\EditPurchase;
use App\Filament\Resources\PurchaseResource\Pages\ListPurchases;
use App\Models\Branch;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Warehouse;
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
                                    ->default(now()),
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'received' => 'Recibido',
                                        'cancelled' => 'Cancelado',
                                    ])
                                    ->default('pending')
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
                                Forms\Components\Placeholder::make('items_summary')
                                    ->label('Productos')
                                    ->content(function (Get $get) {
                                        $items = $get('items') ?? [];

                                        return count(array_filter($items, fn ($i) => ! empty($i['product_variant_id']))).' producto(s)';
                                    }),
                                Forms\Components\TextInput::make('discount')
                                    ->label('Descuento general')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateTotals($get, $set);
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
        $items = $get('items') ?? [];

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['subtotal'] ?? 0);
        }

        $discount = (float) ($get('discount') ?: 0);
        $tax = (float) ($get('tax') ?: 0);
        $total = $subtotal - $discount + $tax;

        $set('total', round($total));
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
                Tables\Actions\Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->action(function (Purchase $record) {
                        $receiptService = app(\App\Services\ReceiptService::class);
                        $receipt = $record->receipts()->where('type', 'purchase_ticket')->first();
                        if (!$receipt) {
                            $receipt = $receiptService->generateReceipt($record, 'purchase_ticket');
                        }
                        return response()->streamDownload(function () use ($receiptService, $receipt, $record) {
                            echo $receiptService->generatePdf($record, $receipt, 'purchase_ticket')->output();
                        }, "compra_{$receipt->number}.pdf");
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
