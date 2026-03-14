<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-clipboard-document-list')
                ->badge(fn () => Activity::count()),

            'ventas' => Tab::make('Ventas')
                ->icon('heroicon-o-shopping-cart')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('log_name', ['venta', 'cliente']))
                ->badge(fn () => Activity::whereIn('log_name', ['venta', 'cliente'])->count()),

            'compras' => Tab::make('Compras')
                ->icon('heroicon-o-truck')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('log_name', ['compra', 'proveedor']))
                ->badge(fn () => Activity::whereIn('log_name', ['compra', 'proveedor'])->count()),

            'inventario' => Tab::make('Inventario')
                ->icon('heroicon-o-cube')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('log_name', [
                    'producto', 'stock', 'ajuste_inventario', 'conteo_inventario', 'almacen',
                ]))
                ->badge(fn () => Activity::whereIn('log_name', [
                    'producto', 'stock', 'ajuste_inventario', 'conteo_inventario', 'almacen',
                ])->count()),

            'finanzas' => Tab::make('Finanzas')
                ->icon('heroicon-o-banknotes')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('log_name', [
                    'caja', 'gasto', 'categoria_gasto',
                ]))
                ->badge(fn () => Activity::whereIn('log_name', [
                    'caja', 'gasto', 'categoria_gasto',
                ])->count()),

            'configuracion' => Tab::make('Configuración')
                ->icon('heroicon-o-cog-6-tooth')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('log_name', [
                    'users', 'sucursal', 'categoria',
                ]))
                ->badge(fn () => Activity::whereIn('log_name', [
                    'users', 'sucursal', 'categoria',
                ])->count()),

            'auth' => Tab::make('Autenticación')
                ->icon('heroicon-o-lock-closed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('log_name', 'auth'))
                ->badge(fn () => Activity::where('log_name', 'auth')->count()),
        ];
    }
}
