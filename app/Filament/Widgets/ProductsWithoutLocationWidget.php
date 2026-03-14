<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProductsWithoutLocationWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Productos sin Ubicacion Asignada')
            ->query(
                Product::query()
                    ->where('active', true)
                    ->whereDoesntHave('variants.productLocations')
                    ->with('category')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU'),
                TextColumn::make('category.name')
                    ->label('Categoria'),
                TextColumn::make('variants_count')
                    ->label('Variantes')
                    ->counts('variants'),
            ])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Todos los productos tienen ubicacion')
            ->emptyStateDescription('Todos los productos activos tienen al menos una ubicacion asignada.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
