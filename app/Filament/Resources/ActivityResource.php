<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $modelLabel = 'Registro de Actividad';

    protected static ?string $pluralModelLabel = 'Registros de Actividad';

    protected static ?int $navigationSort = 100;

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

    /**
     * Mapa de log_name → nombre legible en español.
     */
    public static function getModuleLabels(): array
    {
        return [
            'venta' => '💰 Ventas',
            'compra' => '📦 Compras',
            'producto' => '🏷️ Productos',
            'cliente' => '👤 Clientes',
            'proveedor' => '🏭 Proveedores',
            'ajuste_inventario' => '📋 Ajustes de Inventario',
            'conteo_inventario' => '📊 Conteos de Inventario',
            'almacen' => '🏪 Almacenes',
            'sucursal' => '🏢 Sucursales',
            'categoria' => '📁 Categorías',
            'categoria_gasto' => '📂 Categorías de Gasto',
            'caja' => '💵 Cajas Registradoras',
            'gasto' => '💸 Gastos',
            'stock' => '📈 Stock',
            'users' => '👥 Usuarios',
            'auth' => '🔐 Autenticación',
        ];
    }

    /**
     * Mapa de subject_type → nombre legible.
     */
    public static function getSubjectTypeLabel(?string $type): string
    {
        if (! $type) {
            return 'Desconocido';
        }

        return match (class_basename($type)) {
            'Sale' => 'Venta',
            'Purchase' => 'Compra',
            'Product' => 'Producto',
            'ProductVariant' => 'Variante',
            'Customer' => 'Cliente',
            'Supplier' => 'Proveedor',
            'InventoryAdjustment' => 'Ajuste de Inventario',
            'InventoryCount' => 'Conteo de Inventario',
            'Warehouse' => 'Almacén',
            'Branch' => 'Sucursal',
            'Category' => 'Categoría',
            'CashRegister' => 'Caja Registradora',
            'Expense' => 'Gasto',
            'ExpenseCategory' => 'Categoría de Gasto',
            'Stock' => 'Stock',
            'User' => 'Usuario',
            default => class_basename($type),
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Acción')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('log_name')
                            ->label('Módulo')
                            ->formatStateUsing(fn ($state) => self::getModuleLabels()[$state] ?? $state)
                            ->disabled(),
                        Forms\Components\TextInput::make('event')
                            ->label('Acción')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'created' => '✅ Creado',
                                'updated' => '✏️ Actualizado',
                                'deleted' => '🗑️ Eliminado',
                                'restored' => '♻️ Restaurado',
                                default => $state,
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('causer_id')
                            ->label('Realizado por')
                            ->formatStateUsing(function ($record) {
                                if (! $record->causer) {
                                    return '🤖 Sistema';
                                }

                                return $record->causer->name.' ('.$record->causer->email.')';
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('subject_type')
                            ->label('Tipo de Registro')
                            ->formatStateUsing(fn ($state) => self::getSubjectTypeLabel($state))
                            ->disabled(),
                        Forms\Components\TextInput::make('subject_id')
                            ->label('ID del Registro')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Fecha y Hora')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Cambios en los Datos')
                    ->icon('heroicon-o-arrow-path')
                    ->description('Comparación de valores anteriores y nuevos.')
                    ->schema([
                        Forms\Components\KeyValue::make('properties.old')
                            ->label('🔴 Valores Anteriores')
                            ->disabled(),
                        Forms\Components\KeyValue::make('properties.attributes')
                            ->label('🟢 Valores Nuevos')
                            ->disabled(),
                    ])->columns(2)
                    ->visible(fn ($record) => $record && (
                        ! empty($record->properties['old'] ?? null) ||
                        ! empty($record->properties['attributes'] ?? null)
                    )),

                Forms\Components\Section::make('Datos Completos')
                    ->icon('heroicon-o-code-bracket')
                    ->description('Propiedades registradas por el sistema.')
                    ->schema([
                        Forms\Components\KeyValue::make('properties.attributes')
                            ->label('Atributos')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record &&
                        empty($record->properties['old'] ?? null) &&
                        ! empty($record->properties['attributes'] ?? null)
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Módulo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::getModuleLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'venta' => 'success',
                        'compra' => 'info',
                        'producto' => 'primary',
                        'cliente', 'proveedor' => 'gray',
                        'ajuste_inventario', 'conteo_inventario', 'stock' => 'warning',
                        'almacen', 'sucursal' => 'info',
                        'caja', 'gasto', 'categoria_gasto' => 'danger',
                        'categoria' => 'primary',
                        'users' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('Acción')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'created' => 'Creación',
                        'updated' => 'Edición',
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
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Registro')
                    ->formatStateUsing(fn ($state) => self::getSubjectTypeLabel($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Sistema')
                    ->icon('heroicon-o-user'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Módulo')
                    ->multiple()
                    ->options(self::getModuleLabels()),
                Tables\Filters\SelectFilter::make('event')
                    ->label('Acción')
                    ->options([
                        'created' => '✅ Creación',
                        'updated' => '✏️ Edición',
                        'deleted' => '🗑️ Eliminación',
                        'restored' => '♻️ Restauración',
                    ]),
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('Usuario')
                    ->options(fn () => User::pluck('name', 'id')->toArray())
                    ->searchable(),
                Tables\Filters\Filter::make('created_at')
                    ->label('Rango de Fechas')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['hasta'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['desde'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Desde '.Carbon::parse($data['desde'])->format('d/m/Y'))
                                ->removeField('desde');
                        }
                        if ($data['hasta'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Hasta '.Carbon::parse($data['hasta'])->format('d/m/Y'))
                                ->removeField('hasta');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalle'),
            ])
            ->bulkActions([])
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }
}
