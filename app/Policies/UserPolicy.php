<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_usuarios');
    }

    public function view(User $user, User $record): bool
    {
        return $user->hasPermissionTo('ver_usuarios');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_usuarios');
    }

    public function update(User $user, User $record): bool
    {
        return $user->hasPermissionTo('editar_usuarios');
    }

    public function delete(User $user, User $record): bool
    {
        return $user->hasPermissionTo('eliminar_usuarios');
    }

    public function restore(User $user, User $record): bool
    {
        return $user->hasPermissionTo('eliminar_usuarios');
    }

    public function forceDelete(User $user, User $record): bool
    {
        return $user->hasPermissionTo('eliminar_usuarios');
    }
}
