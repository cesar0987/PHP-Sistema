<?php

namespace App\Filament\Pages;

use App\Models\Customer;
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

    protected static ?string $navigationLabel = 'Calendario de Vencimientos';

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
                Customer::query()
                    ->where('is_credit_enabled', true)
                    ->whereNotNull('credit_due_date')
                    ->orderBy('credit_due_date', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('document')
                    ->label('RUC / CI')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Saldo Adeudado')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.') . ' Gs')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Límite de Crédito')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.') . ' Gs')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado_vencimiento')
                    ->label('Estado del Crédito')
                    ->options([
                        'vencido' => '🔴 Vencido',
                        'por_vencer' => '🟡 Por vencer (7 días)',
                        'vigente' => '🟢 Vigente',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! $data['value']) {
                            return;
                        }

                        return match ($data['value']) {
                            'vencido' => $query->where('credit_due_date', '<', now()->toDateString()),
                            'por_vencer' => $query->whereBetween('credit_due_date', [now()->toDateString(), now()->addDays(7)->toDateString()]),
                            'vigente' => $query->where('credit_due_date', '>', now()->addDays(7)->toDateString()),
                        };
                    }),
                Tables\Filters\Filter::make('con_saldo')
                    ->label('Solo con saldo pendiente')
                    ->query(fn (Builder $query) => $query->where('current_balance', '>', 0))
                    ->default(true),
            ])
            ->defaultSort('credit_due_date', 'asc');
    }
}
