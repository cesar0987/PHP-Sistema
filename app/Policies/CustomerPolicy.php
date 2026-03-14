<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_clientes');
    }

    public function view(User $user, Customer $record): bool
    {
        return $user->hasPermissionTo('ver_clientes');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_clientes');
    }

    public function update(User $user, Customer $record): bool
    {
        return $user->hasPermissionTo('editar_clientes');
    }

    public function delete(User $user, Customer $record): bool
    {
        return $user->hasPermissionTo('eliminar_clientes');
    }

    public function restore(User $user, Customer $record): bool
    {
        return $user->hasPermissionTo('eliminar_clientes');
    }

    public function forceDelete(User $user, Customer $record): bool
    {
        return $user->hasPermissionTo('eliminar_clientes');
    }
}
