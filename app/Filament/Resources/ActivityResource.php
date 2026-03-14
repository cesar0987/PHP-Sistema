<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Model;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationGroup = 'Administracion';

    protected static ?string $modelLabel = 'Registro de Actividad';

    protected static ?string $pluralModelLabel = 'Registros de Actividad';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Acción')
                    ->schema([
                        Forms\Components\TextInput::make('log_name')
                            ->label('Módulo')
                            ->disabled(),
                        Forms\Components\TextInput::make('event')
                            ->label('Acción')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'created' => 'Creado',
                                'updated' => 'Actualizado',
                                'deleted' => 'Eliminado',
                                'restored' => 'Restaurado',
                                default => $state,
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('causer_id')
                            ->label('Usuario (ID)')
                            ->formatStateUsing(function ($record) {
                                return $record->causer ? $record->causer->name . ' (' . $record->causer->email . ')' : 'Sistema';
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('subject_type')
                            ->label('Tipo de Registro')
                            ->disabled()
                            ->columnSpan(2),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Fecha y Hora')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Cambios en los Datos')
                    ->schema([
                        Forms\Components\KeyValue::make('properties.old')
                            ->label('Valores Anteriores')
                            ->disabled(),
                        Forms\Components\KeyValue::make('properties.attributes')
                            ->label('Valores Nuevos')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Módulo')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('Evento')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'created' => 'Creación',
                        'updated' => 'Actualización',
                        'deleted' => 'Eliminación',
                        'restored' => 'Restauración',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Sistema'),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Módulo')
                    ->options([
                        'venta' => 'Ventas',
                        'compra' => 'Compras',
                        'producto' => 'Productos',
                        'cliente' => 'Clientes',
                        'proveedor' => 'Proveedores',
                        'ajuste_inventario' => 'Ajuste Inventario',
                    ]),
                Tables\Filters\SelectFilter::make('event')
                    ->label('Evento')
                    ->options([
                        'created' => 'Creación',
                        'updated' => 'Actualización',
                        'deleted' => 'Eliminación',
                        'restored' => 'Restauración',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions allowed for logs
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }
}
