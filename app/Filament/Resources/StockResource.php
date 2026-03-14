<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages\ListStocks;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Stock';

    protected static ?string $pluralModelLabel = 'Stock';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_variant_id')
                ->label('Producto')
                ->relationship('productVariant', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->product->name.($record->color ? ' - '.$record->color : '').($record->size ? ' - '.$record->size : ''))
                ->required()
                ->disabled(),
            Forms\Components\Select::make('warehouse_id')->label('Almacen')->relationship('warehouse', 'name')->required()->disabled(),
            Forms\Components\TextInput::make('quantity')->label('Cantidad')->numeric()->default(0)->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('productVariant.product.name')->label('Producto')->searchable(),
                Tables\Columns\TextColumn::make('productVariant.color')->label('Color'),
                Tables\Columns\TextColumn::make('productVariant.size')->label('Talla'),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Almacen'),
                Tables\Columns\TextColumn::make('quantity')->label('Cantidad'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->label('Almacen')->relationship('warehouse', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStocks::route('/'),
        ];
    }
}
