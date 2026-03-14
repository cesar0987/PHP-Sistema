<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('method')
                    ->label('Método de Pago')
                    ->options([
                        'Efectivo' => 'Efectivo',
                        'Transferencia' => 'Transferencia',
                        'Tarjeta' => 'Tarjeta',
                        'Cheque' => 'Cheque',
                    ])
                    ->default('Efectivo')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Monto Abonado')
                    ->required()
                    ->numeric()
                    ->prefix('Gs'),
                Forms\Components\Textarea::make('notes')
                    ->label('Observaciones / Notas')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Método')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto Abonado')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.') . ' Gs')
                    ->weight('bold')
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(30),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
