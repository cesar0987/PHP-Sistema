<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashRegisterResource\Pages\CreateCashRegister;
use App\Filament\Resources\CashRegisterResource\Pages\EditCashRegister;
use App\Filament\Resources\CashRegisterResource\Pages\ListCashRegisters;
use App\Models\CashRegister;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashRegisterResource extends Resource
{
    protected static ?string $model = CashRegister::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?string $modelLabel = 'Caja';

    protected static ?string $pluralModelLabel = 'Cajas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nombre')->required()->default('Caja'),
            Forms\Components\Select::make('branch_id')->label('Sucursal')->relationship('branch', 'name')->required(),
            Forms\Components\Select::make('user_id')->label('Cajero')->relationship('user', 'name')->required(),
            Forms\Components\TextInput::make('opening_amount')->label('Monto de apertura')->numeric()->default(0)->suffix('Gs'),
            Forms\Components\TextInput::make('closing_amount')->label('Monto de cierre')->numeric()->suffix('Gs'),
            Forms\Components\DateTimePicker::make('opened_at')->label('Fecha apertura'),
            Forms\Components\DateTimePicker::make('closed_at')->label('Fecha cierre'),
            Forms\Components\Select::make('status')->label('Estado')->options([
                'open' => 'Abierta',
                'closed' => 'Cerrada',
            ])->default('open'),
            Forms\Components\Textarea::make('notes')->label('Notas'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('branch.name')->label('Sucursal'),
                Tables\Columns\TextColumn::make('user.name')->label('Cajero'),
                Tables\Columns\TextColumn::make('opening_amount')->label('Apertura')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' Gs'),
                Tables\Columns\TextColumn::make('closing_amount')->label('Cierre')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.').' Gs' : '-'),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Abierta',
                        'closed' => 'Cerrada',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('opened_at')->label('Apertura')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'open' => 'Abierta',
                    'closed' => 'Cerrada',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashRegisters::route('/'),
            'create' => CreateCashRegister::route('/create'),
            'edit' => EditCashRegister::route('/{record}/edit'),
        ];
    }
}
