<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Purchase;

class PurchasesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchases';

    protected static ?string $title = 'Historial de Compras (Facturas)';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->defaultSort('purchase_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nro. Factura')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Fecha')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Monto Total')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.') . ' Gs')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Completada',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_purchase')
                    ->label('Ver Compra')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Purchase $record): string => \App\Filament\Resources\PurchaseResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
