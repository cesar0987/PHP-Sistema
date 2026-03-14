<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\CustomerResource\RelationManagers\PaymentsRelationManager;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            Forms\Components\TextInput::make('document')->label('RUC / CI')->maxLength(255),
            Forms\Components\TextInput::make('phone')->label('Telefono')->tel()->maxLength(255),
            Forms\Components\TextInput::make('email')->label('Correo')->email()->maxLength(255),
            Forms\Components\Textarea::make('address')->label('Direccion'),
            Forms\Components\Toggle::make('active')->label('Activo')->default(true),
            Forms\Components\Toggle::make('is_credit_enabled')
                ->label('Habilitado para Crédito')
                ->default(false)
                ->live(),
            Forms\Components\TextInput::make('credit_limit')
                ->label('Límite de Crédito')
                ->numeric()
                ->prefix('Gs')
                ->hidden(fn (Forms\Get $get) => ! $get('is_credit_enabled')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('document')->label('RUC / CI')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefono')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Correo')->searchable(),
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Saldo Adeudado')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.').' Gs')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')->label('Activo')->boolean(),
                Tables\Columns\IconColumn::make('is_credit_enabled')
                    ->label('Crédito')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->recordUrl(fn (Customer $record): string => CustomerResource::getUrl('view', ['record' => $record]))
            ->filters([
                Tables\Filters\Filter::make('active')->label('Solo activos')->query(fn ($query) => $query->where('active', true)),
                Tables\Filters\TrashedFilter::make()->label('Eliminados'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
