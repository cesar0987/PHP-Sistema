<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockBatchResource\Pages;
use App\Models\StockBatch;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockBatchResource extends Resource
{
    protected static ?string $model = StockBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Lote de Vencimiento';

    protected static ?string $pluralModelLabel = 'Control de Vencimientos';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('productVariant.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Almacén')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Restante')
                    ->numeric()
                    ->badge(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Fecha de Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        $days = now()->diffInDays(\Carbon\Carbon::parse($state), false);
                        if ($days < 0) return 'danger';
                        if ($days <= 30) return 'warning';
                        if ($days <= 90) return 'info';
                        return 'success';
                    }),
            ])
            ->defaultSort('expiry_date', 'asc')
            ->filters([
                Tables\Filters\Filter::make('has_stock')
                    ->label('Lotes con Stock')
                    ->query(fn (Builder $query) => $query->where('quantity', '>', 0))
                    ->default(true),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Vence en < 90 días')
                    ->query(fn (Builder $query) => $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays(90))),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Almacén')
                    ->relationship('warehouse', 'name'),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('expiry_date');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStockBatches::route('/'),
        ];
    }
}
