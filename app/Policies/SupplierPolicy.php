<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_proveedores');
    }

    public function view(User $user, Supplier $record): bool
    {
        return $user->hasPermissionTo('ver_proveedores');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_proveedores');
    }

    public function update(User $user, Supplier $record): bool
    {
        return $user->hasPermissionTo('editar_proveedores');
    }

    public function delete(User $user, Supplier $record): bool
    {
        return $user->hasPermissionTo('eliminar_proveedores');
    }

    public function restore(User $user, Supplier $record): bool
    {
        return $user->hasPermissionTo('eliminar_proveedores');
    }

    public function forceDelete(User $user, Supplier $record): bool
    {
        return $user->hasPermissionTo('eliminar_proveedores');
    }
}
