<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Productos con Stock Bajo')
            ->query(
                Stock::query()
                    ->join('product_variants', 'stocks.product_variant_id', '=', 'product_variants.id')
                    ->join('products', 'product_variants.product_id', '=', 'products.id')
                    ->whereColumn('stocks.quantity', '<=', 'products.min_stock')
                    ->select('stocks.*')
                    ->with(['productVariant.product', 'warehouse'])
                    ->orderBy('stocks.quantity', 'asc')
            )
            ->columns([
                TextColumn::make('productVariant.product.name')
                    ->label('Producto')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('productVariant.product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('productVariant.sku')
                    ->label('SKU'),
                TextColumn::make('warehouse.name')
                    ->label('Almacen'),
                TextColumn::make('quantity')
                    ->label('Stock')
                    ->badge()
                    ->color(fn ($state) => $state <= 5 ? 'danger' : 'warning'),
                TextColumn::make('productVariant.product.min_stock')
                    ->label('Min. Requerido'),
            ])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Sin alertas de stock bajo')
            ->emptyStateDescription('Todos los productos tienen stock suficiente.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
