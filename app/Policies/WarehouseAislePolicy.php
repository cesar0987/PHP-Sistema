<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WarehouseAisle;

class WarehouseAislePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_ubicaciones');
    }

    public function view(User $user, WarehouseAisle $record): bool
    {
        return $user->hasPermissionTo('ver_ubicaciones');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_ubicaciones');
    }

    public function update(User $user, WarehouseAisle $record): bool
    {
        return $user->hasPermissionTo('editar_ubicaciones');
    }

    public function delete(User $user, WarehouseAisle $record): bool
    {
        return $user->hasPermissionTo('eliminar_ubicaciones');
    }

    public function restore(User $user, WarehouseAisle $record): bool
    {
        return $user->hasPermissionTo('eliminar_ubicaciones');
    }

    public function forceDelete(User $user, WarehouseAisle $record): bool
    {
        return $user->hasPermissionTo('eliminar_ubicaciones');
    }
}
