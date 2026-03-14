<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryCountResource\Pages;
use App\Filament\Resources\InventoryCountResource\RelationManagers;
use App\Models\InventoryCount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryCountResource extends Resource
{
    protected static ?string $model = InventoryCount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de Toma Física')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('warehouse_id')
                                    ->label('Almacén / Sucursal')
                                    ->relationship('warehouse', 'name')
                                    ->required()
                                    ->disabledOn('edit')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de la Toma (opcional)')
                                    ->placeholder('Ej: Corte de caja mensual')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notas / Observaciones')
                                    ->columnSpanFull()
                                    ->rows(2),
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'in_progress' => 'En Progreso',
                                        'completed' => 'Completado',
                                        'cancelled' => 'Cancelado',
                                    ])
                                    ->required()
                                    ->disabled()
                                    ->default('pending')
                                    ->visibleOn('edit'),
                                Forms\Components\DateTimePicker::make('started_at')
                                    ->label('Iniciado en')
                                    ->disabled()
                                    ->visibleOn('edit'),
                                Forms\Components\DateTimePicker::make('completed_at')
                                    ->label('Finalizado en')
                                    ->disabled()
                                    ->visibleOn('edit'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Almacén')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Título')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Responsable')
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Almacén')
                    ->relationship('warehouse', 'name')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (InventoryCount $record) => $record->status !== 'completed'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryCounts::route('/'),
            'create' => Pages\CreateInventoryCount::route('/create'),
            'edit' => Pages\EditInventoryCount::route('/{record}/edit'),
        ];
    }
}
