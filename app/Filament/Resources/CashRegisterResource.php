<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashRegisterResource\Pages\CreateCashRegister;
use App\Filament\Resources\CashRegisterResource\Pages\EditCashRegister;
use App\Filament\Resources\CashRegisterResource\Pages\ListCashRegisters;
use App\Filament\Resources\CashRegisterResource\Pages\ViewCashRegister;
use App\Filament\Resources\CashRegisterResource\RelationManagers\SalesRelationManager;
use App\Models\CashRegister;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->disabled()
                ->hiddenOn('create'),
            Forms\Components\Select::make('branch_id')
                ->label('Sucursal')
                ->relationship('branch', 'name')
                ->required(),
            Forms\Components\Select::make('user_id')
                ->label('Cajero')
                ->relationship('user', 'name')
                ->required()
                ->default(fn () => auth()->id()),
            Forms\Components\TextInput::make('opening_amount')
                ->label('Monto de apertura')
                ->numeric()
                ->default(0)
                ->suffix('Gs')
                ->required(),
            Forms\Components\TextInput::make('closing_amount')
                ->label('Monto de cierre')
                ->numeric()
                ->suffix('Gs')
                ->disabled()
                ->hiddenOn('create'),
            Forms\Components\DateTimePicker::make('opened_at')
                ->label('Fecha apertura')
                ->disabled()
                ->hiddenOn('create'),
            Forms\Components\DateTimePicker::make('closed_at')
                ->label('Fecha cierre')
                ->disabled()
                ->hiddenOn('create'),
            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options([
                    'open' => 'Abierta',
                    'closed' => 'Cerrada',
                ])
                ->disabled()
                ->hiddenOn('create'),
            Forms\Components\Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
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
                            Tables\Filters\TrashedFilter::make()->label('Eliminados'),
                        ])
            ->actions([
                            Tables\Actions\ViewAction::make(),
                            Tables\Actions\EditAction::make(),
                            Tables\Actions\Action::make('cerrar')
                                ->label('Cerrar Caja')
                                ->icon('heroicon-o-lock-closed')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Cierre de Caja')
                                ->modalDescription('Ingrese el efectivo físico que tiene en caja para efectuar el cierre ciego.')
                                ->form([
                                    Forms\Components\TextInput::make('closing_amount')
                                        ->label('Efectivo Físico')
                                        ->numeric()
                                        ->required()
                                        ->prefix('Gs'),
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Observaciones')
                                        ->rows(2),
                                ])
                                ->action(function (CashRegister $record, array $data, Tables\Actions\Action $action): void {
                                    $cashSales = \App\Models\Sale::where('cash_register_id', $record->id)
                                        ->where('status', 'completed')
                                        ->where('payment_method', 'contado')
                                        ->sum('total');
                                        
                                    $customerPayments = \App\Models\CustomerPayment::where('sale_id', null) // Pagos directos o cuotas
                                        ->orWhereHas('sale', fn($q) => $q->where('cash_register_id', $record->id))
                                        ->whereBetween('created_at', [$record->opened_at, now()])
                                        ->sum('amount');

                                    $expected = $record->opening_amount + $cashSales + $customerPayments;
                                    $reported = (float) $data['closing_amount'];
                                    
                                    if ($expected > 0) {
                                        $difference = abs($reported - $expected);
                                        if (($difference / $expected) > 0.10) {
                                            Notification::make()
                                                ->warning()
                                                ->title('Alerta de Descuadre')
                                                ->body("Diferencia mayor al 10%. Esperado: ".number_format($expected, 0, ',', '.')." Gs. Reportado: ".number_format($reported, 0, ',', '.')." Gs.")
                                                ->send();
                                        }
                                    }

                                    $record->update([
                                        'status' => 'closed',
                                        'closed_at' => now(),
                                        'closing_amount' => $reported,
                                        'notes' => ltrim($record->notes."\nCierre: ".($data['notes'] ?? '')),
                                    ]);

                                    Notification::make()
                                        ->title('Caja Cerrada Exitosamente')
                                        ->success()
                                        ->send();
                                })
                                ->visible(fn (CashRegister $record): bool => $record->status === 'open'),
                            Tables\Actions\Action::make('print')
                                ->label('Imprimir Reporte')
                                ->icon('heroicon-o-printer')
                                ->color('info')
                                ->url(fn (CashRegister $record) => route('cash-register.print', $record))
                                ->openUrlInNewTab(),
                            Tables\Actions\RestoreAction::make(),
                        ]);
    }

    public static function getRelations(): array
    {
        return [
            SalesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashRegisters::route('/'),
            'create' => CreateCashRegister::route('/create'),
            'view' => ViewCashRegister::route('/{record}'),
            'edit' => EditCashRegister::route('/{record}/edit'),
        ];
    }
}
