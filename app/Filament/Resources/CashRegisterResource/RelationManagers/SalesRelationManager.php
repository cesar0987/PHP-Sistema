<?php

namespace App\Filament\Resources\CashRegisterResource\RelationManagers;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('Nro. Documento')->searchable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => $state === 'invoice' ? 'Factura' : 'Ticket')
                    ->badge()
                    ->color(fn ($state) => $state === 'invoice' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('customer.name')->label('Cliente'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' Gs')
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
                        'pending' => 'Pedido',
                        'cancelled' => 'Cancelado',
                        'returned' => 'Devuelto',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('sale_date')->label('Fecha')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No actions needed
            ])
            ->actions([
                Tables\Actions\Action::make('Ver Venta')
                    ->url(fn (Sale $record): string => SaleResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                //
            ]);
    }
}
