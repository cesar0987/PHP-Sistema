<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_compras');
    }

    public function view(User $user, Purchase $record): bool
    {
        return $user->hasPermissionTo('ver_compras');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_compras');
    }

    public function update(User $user, Purchase $record): bool
    {
        return $user->hasPermissionTo('editar_compras');
    }

    public function delete(User $user, Purchase $record): bool
    {
        return $user->hasPermissionTo('eliminar_compras');
    }

    public function restore(User $user, Purchase $record): bool
    {
        return $user->hasPermissionTo('eliminar_compras');
    }

    public function forceDelete(User $user, Purchase $record): bool
    {
        return $user->hasPermissionTo('eliminar_compras');
    }
}
