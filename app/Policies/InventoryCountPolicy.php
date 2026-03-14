<?php

namespace App\Policies;

use App\Models\InventoryCount;
use App\Models\User;

class InventoryCountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_ajustes_inventario');
    }

    public function view(User $user, InventoryCount $record): bool
    {
        return $user->hasPermissionTo('ver_ajustes_inventario');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_ajustes_inventario');
    }

    public function update(User $user, InventoryCount $record): bool
    {
        if ($record->status === 'completed') {
            return false; // conteos completados no se editan
        }

        return $user->hasPermissionTo('editar_ajustes_inventario');
    }

    public function delete(User $user, InventoryCount $record): bool
    {
        if ($record->status === 'completed') {
            return $user->hasRole(['super_admin', 'admin']);
        }

        return $user->hasPermissionTo('eliminar_ajustes_inventario');
    }

    public function restore(User $user, InventoryCount $record): bool
    {
        return $user->hasPermissionTo('eliminar_ajustes_inventario');
    }

    public function forceDelete(User $user, InventoryCount $record): bool
    {
        return $user->hasRole('super_admin');
    }
}
