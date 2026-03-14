<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages\CreateLocation;
use App\Filament\Resources\LocationResource\Pages\EditLocation;
use App\Filament\Resources\LocationResource\Pages\ListLocations;
use App\Models\WarehouseAisle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    protected static ?string $model = WarehouseAisle::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Ubicacion';

    protected static ?string $pluralModelLabel = 'Ubicaciones';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('warehouse_id')->label('Almacen')->relationship('warehouse', 'name')->required(),
            Forms\Components\TextInput::make('code')->label('Codigo')->required()->maxLength(10)->helperText('Codigo del pasillo: A, B, C... AA, AB...'),
            Forms\Components\Textarea::make('description')->label('Descripcion'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Pasillo')->searchable(),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Almacen'),
                Tables\Columns\TextColumn::make('warehouse.branch.name')->label('Sucursal'),
                Tables\Columns\TextColumn::make('shelves_count')->label('Estantes')->counts('shelves'),
                Tables\Columns\TextColumn::make('description')->label('Descripcion'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->label('Almacen')->relationship('warehouse', 'name'),
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

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
