<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryAdjustmentResource\Pages\CreateInventoryAdjustment;
use App\Filament\Resources\InventoryAdjustmentResource\Pages\EditInventoryAdjustment;
use App\Filament\Resources\InventoryAdjustmentResource\Pages\ListInventoryAdjustments;
use App\Models\InventoryAdjustment;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryAdjustmentResource extends Resource
{
    protected static ?string $model = InventoryAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Ajuste de inventario';

    protected static ?string $pluralModelLabel = 'Ajustes de inventario';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del Ajuste')
                ->schema([
                    Forms\Components\Select::make('user_id')->label('Usuario')->relationship('user', 'name')->required()->default(auth()->id()),
                    Forms\Components\Select::make('warehouse_id')->label('Almacen')->relationship('warehouse', 'name')->required()->reactive()
                        ->afterStateUpdated(fn (Set $set) => $set('items', [])),
                    Forms\Components\Textarea::make('reason')->label('Motivo')->required(),
                    Forms\Components\Select::make('status')->label('Estado')->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                    ])->default('pending')->required(),
                ])->columns(2),

            Forms\Components\Section::make('Productos')
                ->schema([
                    Forms\Components\Repeater::make('items')
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
                                    if ($state && $get('../../warehouse_id')) {
                                        $variant = ProductVariant::find($state);
                                        $warehouse = Warehouse::find($get('../../warehouse_id'));
                                        if ($variant && $warehouse) {
                                            $stock = app(InventoryService::class)->getStockByWarehouse($variant, $warehouse);
                                            $set('quantity_before', $stock ? $stock->quantity : 0);
                                            $set('quantity_after', $stock ? $stock->quantity : 0);
                                        }
                                    }
                                })->columnSpan(2),
                            Forms\Components\TextInput::make('quantity_before')
                                ->label('Stock Actual')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('quantity_after')
                                ->label('Nuevo Stock (Real)')
                                ->numeric()
                                ->required(),
                        ])
                        ->columns(4)
                        ->defaultItems(1)
                        ->required()
                        ->minItems(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('user.name')->label('Usuario'),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Almacen'),
                Tables\Columns\TextColumn::make('reason')->label('Motivo')->limit(50),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Aprobado',
                        'pending' => 'Pendiente',
                        'rejected' => 'Rechazado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'pending' => 'Pendiente',
                    'approved' => 'Aprobado',
                    'rejected' => 'Rechazado',
                ]),
                Tables\Filters\SelectFilter::make('warehouse_id')->label('Almacen')->relationship('warehouse', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryAdjustments::route('/'),
            'create' => CreateInventoryAdjustment::route('/create'),
            'edit' => EditInventoryAdjustment::route('/{record}/edit'),
        ];
    }
}
