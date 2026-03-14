<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages\CreateSupplier;
use App\Filament\Resources\SupplierResource\Pages\EditSupplier;
use App\Filament\Resources\SupplierResource\Pages\ListSuppliers;
use App\Filament\Resources\SupplierResource\Pages\ViewSupplier;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Compras';

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            Forms\Components\TextInput::make('ruc')->label('RUC')->maxLength(255),
            Forms\Components\TextInput::make('phone')->label('Telefono')->tel()->maxLength(255),
            Forms\Components\TextInput::make('email')->label('Correo')->email()->maxLength(255),
            Forms\Components\Textarea::make('address')->label('Direccion'),
            Forms\Components\Toggle::make('active')->label('Activo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('ruc')->label('RUC')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefono')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Correo')->searchable(),
                Tables\Columns\IconColumn::make('active')->label('Activo')->boolean(),
            ])
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\SupplierResource\RelationManagers\PurchasesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}
