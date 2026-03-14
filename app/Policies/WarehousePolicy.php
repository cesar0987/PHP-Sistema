<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_almacenes');
    }

    public function view(User $user, Warehouse $record): bool
    {
        return $user->hasPermissionTo('ver_almacenes');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_almacenes');
    }

    public function update(User $user, Warehouse $record): bool
    {
        return $user->hasPermissionTo('editar_almacenes');
    }

    public function delete(User $user, Warehouse $record): bool
    {
        return $user->hasPermissionTo('eliminar_almacenes');
    }

    public function restore(User $user, Warehouse $record): bool
    {
        return $user->hasPermissionTo('eliminar_almacenes');
    }

    public function forceDelete(User $user, Warehouse $record): bool
    {
        return $user->hasPermissionTo('eliminar_almacenes');
    }
}
