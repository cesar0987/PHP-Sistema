<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\RelationManagers\StockMovementsRelationManager;
use App\Models\Product;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    protected static int $defaultTableRecordsPerPage = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informacion del producto')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('category_id')
                                    ->label('Categoria')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Sin categoria'),
                                Forms\Components\TextInput::make('brand')
                                    ->label('Marca')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('barcode')
                                    ->label('Codigo de barras')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('min_stock')
                                    ->label('Stock minimo')
                                    ->numeric()
                                    ->default(0),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripcion')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Precios')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('cost_price')
                                    ->label('Precio de costo')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs'),
                                Forms\Components\TextInput::make('sale_price')
                                    ->label('Precio de venta')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs')
                                    ->gte('cost_price')
                                    ->helperText('No debe ser menor al costo.'),
                                Forms\Components\Select::make('tax_percentage')
                                    ->label('IVA %')
                                    ->options([
                                        0 => 'Exento (0%)',
                                        5 => 'IVA 5%',
                                        10 => 'IVA 10%',
                                    ])
                                    ->default(10)
                                    ->required(),
                            ]),
                    ]),
                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->default(true),
                Forms\Components\Toggle::make('has_expiry')
                    ->label('Controla Vencimiento (Lotes)')
                    ->helperText('Activar si el producto debe registrar fechas de vencimiento al ingresar stock.')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Cod. Barras')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Costo')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' Gs')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Precio venta')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' Gs')
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Stock min.')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->getStateUsing(function ($record) {
                        return Stock::whereHas('productVariant', function ($query) use ($record) {
                            $query->where('product_id', $record->id);
                        })->sum('quantity');
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        $minStock = $record->min_stock ?? 0;
                        if ($state <= 0) {
                            return 'danger';
                        }
                        if ($state <= $minStock) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->formatStateUsing(function ($state, $record) {
                        $minStock = $record->min_stock ?? 0;
                        if ($state <= 0) {
                            return 'Sin stock';
                        }
                        if ($state <= $minStock) {
                            return "{$state} (Bajo)";
                        }

                        return (string) $state;
                    }),
                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('active')
                    ->label('Solo activos')
                    ->query(fn ($query) => $query->where('active', true))
                    ->default(),
                Tables\Filters\Filter::make('sin_stock')
                    ->label('Sin stock')
                    ->query(function ($query) {
                        $query->whereDoesntHave('variants.stocks', function ($q) {
                            $q->where('quantity', '>', 0);
                        });
                    }),
                Tables\Filters\Filter::make('bajo_minimo')
                    ->label('Bajo stock minimo')
                    ->query(function ($query) {
                        $query->where('min_stock', '>', 0)
                            ->whereHas('variants', function ($variantQuery) {
                                $variantQuery->whereRaw(
                                    '(SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE stocks.product_variant_id = product_variants.id) <= (SELECT min_stock FROM products WHERE products.id = product_variants.product_id)'
                                );
                            });
                    }),
                Tables\Filters\Filter::make('sin_ubicacion')
                    ->label('Sin ubicacion')
                    ->query(function ($query) {
                        $query->whereDoesntHave('variants.productLocations');
                    }),
                Tables\Filters\TrashedFilter::make()
                    ->label('Eliminados'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Product $record, Tables\Actions\DeleteAction $action) {
                        $stock = Stock::whereHas('productVariant', function ($query) use ($record) {
                            $query->where('product_id', $record->id);
                        })->sum('quantity');

                        if ($stock > 0) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('No se puede eliminar')
                                ->body('Este producto aún posee stock activo.')
                                ->send();

                            $action->halt();
                        }
                    }),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, Tables\Actions\DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                $stock = Stock::whereHas('productVariant', function ($query) use ($record) {
                                    $query->where('product_id', $record->id);
                                })->sum('quantity');
                                
                                if ($stock > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->warning()
                                        ->title('Error en eliminación masiva')
                                        ->body("El producto {$record->name} aún posee stock activo.")
                                        ->send();

                                    $action->halt();
                                }
                            }
                        }),
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

    public static function getRelations(): array
    {
        return [
            StockMovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
