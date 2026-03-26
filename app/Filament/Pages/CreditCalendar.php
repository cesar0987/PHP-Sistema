<?php

namespace App\Filament\Pages;

use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Services\CreditService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CreditCalendar extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendario de Créditos';

    protected static ?string $title = 'Calendario de Vencimientos de Créditos';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.credit-calendar';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasRole('admin') || $user->hasRole('supervisor') || $user->hasRole('cobrador'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sale::query()
                    ->with(['customer'])
                    ->withSum('creditPayments', 'amount')
                    ->where('payment_method', 'credito')
                    ->where('status', 'completed')
                    ->orderBy('credit_due_date', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Comprobante')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Fecha Venta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total Venta')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.') . ' Gs')
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_payments_sum_amount')
                    ->label('Cobrado')
                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 0, ',', '.') . ' Gs')
                    ->color('success'),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Saldo Pendiente')
                    ->state(fn (Sale $record): float => max(0, (float) $record->total - (float) ($record->credit_payments_sum_amount ?? 0)))
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.') . ' Gs')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('credit_due_date')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(function ($state) {
                        if (! $state) {
                            return 'gray';
                        }
                        $date = \Carbon\Carbon::parse($state);
                        if ($date->isPast()) {
                            return 'danger';
                        }
                        if ($date->diffInDays(now()) <= 7) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->icon(function ($state) {
                        if (! $state) {
                            return null;
                        }
                        $date = \Carbon\Carbon::parse($state);
                        if ($date->isPast()) {
                            return 'heroicon-o-exclamation-triangle';
                        }
                        if ($date->diffInDays(now()) <= 7) {
                            return 'heroicon-o-clock';
                        }

                        return 'heroicon-o-check-circle';
                    })
                    ->description(function (Sale $record): ?string {
                        if (! $record->credit_due_date) {
                            return null;
                        }
                        $date = \Carbon\Carbon::parse($record->credit_due_date);
                        if ($date->isPast()) {
                            return 'Venció hace ' . $date->diffForHumans(now(), true);
                        }

                        return 'Vence en ' . $date->diffForHumans(now(), true);
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('con_saldo')
                    ->label('Solo con saldo pendiente')
                    ->query(function (Builder $query): Builder {
                        // Clientes con saldo = ventas cuyo monto cobrado < total
                        return $query->whereRaw(
                            '(SELECT COALESCE(SUM(cp.amount), 0) FROM customer_payments cp WHERE cp.sale_id = sales.id AND cp.deleted_at IS NULL) < sales.total'
                        );
                    })
                    ->default(true),

                Tables\Filters\SelectFilter::make('estado_vencimiento')
                    ->label('Estado del Vencimiento')
                    ->options([
                        'vencido'    => '🔴 Vencido',
                        'por_vencer' => '🟡 Por vencer (7 días)',
                        'vigente'    => '🟢 Vigente',
                        'sin_fecha'  => '⚫ Sin fecha asignada',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'vencido'    => $query->where('credit_due_date', '<', now()->toDateString()),
                            'por_vencer' => $query->whereBetween('credit_due_date', [now()->toDateString(), now()->addDays(7)->toDateString()]),
                            'vigente'    => $query->where('credit_due_date', '>', now()->addDays(7)->toDateString()),
                            'sin_fecha'  => $query->whereNull('credit_due_date'),
                            default      => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('registrar_cobro')
                    ->label('Registrar Cobro')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading(fn (Sale $record) => 'Registrar Cobro — ' . ($record->customer?->name ?? 'Sin cliente') . ' · ' . ($record->invoice_number ?? "Venta #{$record->id}"))
                    ->modalSubmitActionLabel('Registrar cobro')
                    ->form(function (Sale $record): array {
                        $saldo = max(0, (float) $record->total - (float) ($record->credit_payments_sum_amount ?? CustomerPayment::where('sale_id', $record->id)->sum('amount')));

                        return [
                            Forms\Components\Placeholder::make('resumen')
                                ->label('Resumen de la deuda')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div class="grid grid-cols-3 gap-3 text-center">'
                                    . '<div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3"><p class="text-xs text-gray-500">Total venta</p><p class="font-bold">' . number_format((float) $record->total, 0, ',', '.') . ' Gs</p></div>'
                                    . '<div class="rounded-lg bg-success-50 dark:bg-success-900 p-3"><p class="text-xs text-success-600">Cobrado</p><p class="font-bold text-success-700">' . number_format((float) ($record->credit_payments_sum_amount ?? 0), 0, ',', '.') . ' Gs</p></div>'
                                    . '<div class="rounded-lg bg-danger-50 dark:bg-danger-900 p-3"><p class="text-xs text-danger-600">Saldo pendiente</p><p class="font-bold text-danger-700">' . number_format($saldo, 0, ',', '.') . ' Gs</p></div>'
                                    . '</div>'
                                )),

                            Forms\Components\TextInput::make('amount')
                                ->label('Monto a cobrar')
                                ->numeric()
                                ->required()
                                ->prefix('Gs')
                                ->default($saldo)
                                ->minValue(1)
                                ->helperText('Puede registrar un pago parcial.'),

                            Forms\Components\Select::make('method')
                                ->label('Método de pago')
                                ->options([
                                    'Efectivo'      => 'Efectivo',
                                    'Transferencia' => 'Transferencia',
                                    'Tarjeta'       => 'Tarjeta',
                                    'Cheque'        => 'Cheque',
                                    'QR'            => 'QR (Bancard)',
                                ])
                                ->default('Efectivo')
                                ->required(),

                            Forms\Components\DatePicker::make('fecha_cobro')
                                ->label('Fecha del cobro')
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required()
                                ->live()
                                ->helperText('La nueva fecha de vencimiento se calculará desde esta fecha.'),

                            Forms\Components\Toggle::make('tiene_saldo_restante')
                                ->label('¿Queda saldo pendiente después de este cobro?')
                                ->default($saldo > 0)
                                ->live()
                                ->helperText('Activá si el cliente va a pagar el resto en otra fecha.'),

                            Forms\Components\DatePicker::make('nueva_fecha_vencimiento')
                                ->label('Nueva fecha de vencimiento')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->default(now()->addDays(30))
                                ->minDate(now())
                                ->visible(fn (\Filament\Forms\Get $get) => (bool) $get('tiene_saldo_restante'))
                                ->helperText('Fecha límite para el próximo cobro. Se calcula desde la fecha del cobro.'),

                            Forms\Components\TextInput::make('notes')
                                ->label('Notas / Referencia')
                                ->placeholder('Ej: Transferencia Bancard ref. 123456'),
                        ];
                    })
                    ->action(function (Sale $record, array $data): void {
                        $saldo = max(0, (float) $record->total - CustomerPayment::where('sale_id', $record->id)->sum('amount'));

                        if ($data['amount'] > $saldo + 1) {
                            Notification::make()
                                ->title('Monto excede el saldo')
                                ->body('El monto ingresado (' . number_format($data['amount'], 0, ',', '.') . ' Gs) supera el saldo pendiente (' . number_format($saldo, 0, ',', '.') . ' Gs).')
                                ->warning()
                                ->send();

                            return;
                        }

                        // Registrar el cobro en customer_payments
                        CustomerPayment::create([
                            'customer_id' => $record->customer_id,
                            'sale_id'     => $record->id,
                            'amount'      => $data['amount'],
                            'date'        => $data['fecha_cobro'],
                            'method'      => $data['method'],
                            'notes'       => $data['notes'] ?? "Cobro parcial — {$record->invoice_number}",
                        ]);

                        // Actualizar la fecha de vencimiento de la VENTA según la fecha del cobro
                        $nuevaFecha = $data['tiene_saldo_restante'] && ! empty($data['nueva_fecha_vencimiento'])
                            ? $data['nueva_fecha_vencimiento']
                            : null;

                        if ($nuevaFecha) {
                            $record->update(['credit_due_date' => $nuevaFecha]);
                        } elseif (! $data['tiene_saldo_restante']) {
                            // Saldo saldado — limpiar fecha de vencimiento
                            $record->update(['credit_due_date' => null]);
                        }

                        Notification::make()
                            ->title('Cobro registrado')
                            ->body('Se registraron ' . number_format($data['amount'], 0, ',', '.') . ' Gs para ' . ($record->customer?->name ?? 'el cliente') . '.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Sale $record): bool => (float) $record->total > (float) ($record->credit_payments_sum_amount ?? 0)),
            ])
            ->defaultSort('credit_due_date', 'asc');
    }
}
