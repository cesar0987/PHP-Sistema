<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Historial de Movimientos de Stock';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Entrada',
                        'out' => 'Salida',
                        'adjustment' => 'Ajuste',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Motivo / Procedencia')
                    ->formatStateUsing(function ($state, $record) {
                        // Intentar mapear las clases o tipos de modelo al español
                        $classBasename = class_basename($state);

                        return match ($classBasename) {
                            'Sale' => 'Venta #'.$record->reference_id,
                            'Purchase' => 'Compra #'.$record->reference_id,
                            'InventoryAdjustment' => 'Ajuste #'.$record->reference_id,
                            default => $classBasename.($record->reference_id ? ' #'.$record->reference_id : ''),
                        };
                    }),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Sucursal / Almacén'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(30),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Sin acciones de cabecera porque es solo historial
            ])
            ->actions([
                //
            ]);
    }
}
