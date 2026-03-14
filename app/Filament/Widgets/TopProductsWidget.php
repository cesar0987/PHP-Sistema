<?php

namespace App\Filament\Widgets;

use App\Models\SaleItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopProductsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top 10 Productos Mas Vendidos (Este Mes)')
            ->query(
                SaleItem::query()
                    ->whereHas('sale', function ($query) {
                        $query->whereMonth('sale_date', now()->month)
                            ->whereYear('sale_date', now()->year)
                            ->where('status', 'completed');
                    })
                    ->selectRaw('product_variant_id as id, product_variant_id, SUM(quantity) as total_sold, SUM(subtotal) as total_revenue')
                    ->groupBy('product_variant_id')
                    ->orderByDesc('total_sold')
                    ->with(['productVariant.product'])
            )
            ->columns([
                TextColumn::make('productVariant.product.name')
                    ->label('Producto'),
                TextColumn::make('total_sold')
                    ->label('Cantidad Vendida')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_revenue')
                    ->label('Ingresos')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' Gs')
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Sin ventas este mes')
            ->emptyStateDescription('No se registraron ventas completadas este mes.')
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }
}
