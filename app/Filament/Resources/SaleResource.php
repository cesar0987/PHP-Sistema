<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages\CreateSale;
use App\Filament\Resources\SaleResource\Pages\EditSale;
use App\Filament\Resources\SaleResource\Pages\ListSales;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Services\ReceiptService;
use App\Services\SaleService;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
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
                                Forms\Components\Select::make('payment_method')
                                    ->label('Método de Pago')
                                    ->options([
                                        'contado' => 'Contado',
                                        'credito' => 'Crédito',
                                    ])
                                    ->default('contado')
                                    ->required()
                                    ->helperText('Contado: afecta la caja elegida. Crédito: no entra a caja, va al saldo del cliente.'),
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
                                            $set('invoice_number', 'TCK-'.strtoupper(substr(uniqid(), -6)));
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
                                    ->default(fn () => 'TCK-'.strtoupper(substr(uniqid(), -6)))
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
                                    ->default(fn () => CashRegister::where('user_id', auth()->id())->where('status', 'open')->first()?->id)
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
                                                $variant = ProductVariant::with('product')->find($item['product_variant_id']);
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
                                        'tax_percentage' => $variant->product->tax_percentage ?? 10,
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
                                                $set('tax_percentage', $variant->product->tax_percentage ?? 10);
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
                                    ->rule(
                                        fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $variantId = $get('product_variant_id');
                                            $branchId = $get('../../branch_id');
                                            if (!$variantId || !$branchId) return;
                                            
                                            $stock = \App\Models\Stock::where('product_variant_id', $variantId)
                                                ->where('branch_id', $branchId)
                                                ->sum('quantity');
                                                
                                            if ($value > $stock) {
                                                $fail("Stock insuficiente en sucursal. Disp: {$stock}");
                                            }
                                        }
                                    )
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
                                    ->helperText('Descuento individual')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->readOnly()
                                    ->columnSpan(2),
                                Forms\Components\Hidden::make('tax_percentage')
                                    ->default(10),
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
        // Detect if we are inside the repeater ('../../items') or at the root ('items')
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
            
            // Si el IVA está incluido en el precio, el cálculo del impuesto es subtotal / (1 + (% / 100)) o subtotal / 11, etc.
            // Según sistema paraguayo IVA 10% incluido se saca dividiendo entre 11, IVA 5% entre 21. 
            // Esto asume precios con IVA incluido. B2B quita el 10% fijo por regla anterior (podría requerir ajuste futuro B2B general)
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
                $itemTax = 0;
            }
            
            // Actualizar el impuesto a nivel de item si es posible
            if ($isInsideRepeater && $get('../../items.' . $key . '.subtotal') !== null) {
                 $set('../../items.' . $key . '.tax_amount', round($itemTax, 2));
            }
        }

        $discount = (float) ($isInsideRepeater ? $get('../../discount') : $get('discount')) ?: 0;
        // The total is just the subtotal minus discount since prices include tax.
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
                Tables\Columns\TextColumn::make('payment_method')
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
                                Notification::make()
                                    ->title('Error de validación')
                                    ->body('El monto pagado ('.number_format($totalPagado, 0, ',', '.').') es menor al total de la venta ('.number_format($record->total, 0, ',', '.').').')
                                    ->danger()
                                    ->send();

                                $action->cancel();

                                return;
                            }

                            $saleService = app(SaleService::class);
                            $saleService->approveSale($record, $payments);

                            Notification::make()
                                ->title('Venta aprobada')
                                ->body("Nota de pedido #{$record->id} fue aprobada y cobrada correctamente.")
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            // Let Filament handle the halt exception to keep the modal open
                            throw $e;
                        } catch (\Exception $e) {
                            Notification::make()
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
                                ],
                            ];
                            $saleService = app(SaleService::class);
                            $saleService->approveSale($record, $payments);

                            Notification::make()
                                ->title('Cobro rápido exitoso')
                                ->body("Venta #{$record->id} aprobada y pagada en efectivo.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al procesar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Sale $record): bool => $record->status === 'pending'),
                Tables\Actions\Action::make('imprimir_presupuesto')
                    ->label('Presupuesto')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->color('secondary')
                    ->action(function (Sale $record) {
                        $receiptService = app(ReceiptService::class);
                        // Los presupuestos usan un tipo de comprobante especial para no mezclarse con tickets definitivos.
                        $type = 'sale_budget';
                        $receipt = $record->receipts()->get()->firstWhere('type', $type);
                        if (! $receipt) {
                            $receipt = $receiptService->generateReceipt($record, $type);
                        }
                        $filename = "presupuesto_{$receipt->number}.pdf";

                        return response()->streamDownload(function () use ($receiptService, $record, $receipt, $type) {
                            echo $receiptService->generatePdf($record, $receipt, $type)->output();
                        }, $filename);
                    })
                    ->visible(fn (Sale $record): bool => $record->status === 'pending'),
                Tables\Actions\Action::make('imprimir')
                    ->label('Imprimir Ticket/Factura')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->action(function (Sale $record) {
                        $receiptService = app(ReceiptService::class);
                        // Determinar tipo según document_type
                        $type = $record->document_type === 'invoice' ? 'sale_invoice' : 'sale_ticket';
                        $receipt = $record->receipts()->where('type', $type)->first();
                        if (! $receipt) {
                            $receipt = $receiptService->generateReceipt($record, $type);
                        }
                        $filename = $record->document_type === 'invoice'
                            ? "factura_{$receipt->number}.pdf"
                            : "ticket_{$receipt->number}.pdf";

                        return response()->streamDownload(function () use ($receiptService, $record, $receipt, $type) {
                            echo $receiptService->generatePdf($record, $receipt, $type)->output();
                        }, $filename);
                    })
                    ->visible(fn (Sale $record): bool => $record->status === 'completed'),
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
