<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_categorias');
    }

    public function view(User $user, Category $record): bool
    {
        return $user->hasPermissionTo('ver_categorias');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_categorias');
    }

    public function update(User $user, Category $record): bool
    {
        return $user->hasPermissionTo('editar_categorias');
    }

    public function delete(User $user, Category $record): bool
    {
        return $user->hasPermissionTo('eliminar_categorias');
    }

    public function restore(User $user, Category $record): bool
    {
        return $user->hasPermissionTo('eliminar_categorias');
    }

    public function forceDelete(User $user, Category $record): bool
    {
        return $user->hasPermissionTo('eliminar_categorias');
    }
}
