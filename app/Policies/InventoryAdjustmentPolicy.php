<?php

namespace App\Policies;

use App\Models\InventoryAdjustment;
use App\Models\User;

class InventoryAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_ajustes_inventario');
    }

    public function view(User $user, InventoryAdjustment $record): bool
    {
        return $user->hasPermissionTo('ver_ajustes_inventario');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_ajustes_inventario');
    }

    public function update(User $user, InventoryAdjustment $record): bool
    {
        return $user->hasPermissionTo('editar_ajustes_inventario');
    }

    public function delete(User $user, InventoryAdjustment $record): bool
    {
        return $user->hasPermissionTo('eliminar_ajustes_inventario');
    }

    public function restore(User $user, InventoryAdjustment $record): bool
    {
        return $user->hasPermissionTo('eliminar_ajustes_inventario');
    }

    public function forceDelete(User $user, InventoryAdjustment $record): bool
    {
        return $user->hasPermissionTo('eliminar_ajustes_inventario');
    }
}
