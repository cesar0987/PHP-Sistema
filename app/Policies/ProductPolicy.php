<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_productos');
    }

    public function view(User $user, Product $record): bool
    {
        return $user->hasPermissionTo('ver_productos');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_productos');
    }

    public function update(User $user, Product $record): bool
    {
        return $user->hasPermissionTo('editar_productos');
    }

    public function delete(User $user, Product $record): bool
    {
        return $user->hasPermissionTo('eliminar_productos');
    }

    public function restore(User $user, Product $record): bool
    {
        return $user->hasPermissionTo('eliminar_productos');
    }

    public function forceDelete(User $user, Product $record): bool
    {
        return $user->hasPermissionTo('eliminar_productos');
    }
}
