<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_ventas');
    }

    public function view(User $user, Sale $record): bool
    {
        return $user->hasPermissionTo('ver_ventas');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_ventas');
    }

    public function update(User $user, Sale $record): bool
    {
        if (! $user->hasPermissionTo('editar_ventas')) {
            return false;
        }

        if ($record->document_type === 'invoice') {
            return $user->hasRole(['super_admin', 'admin']);
        }

        return true;
    }

    public function delete(User $user, Sale $record): bool
    {
        if (! $user->hasPermissionTo('eliminar_ventas')) {
            return false;
        }

        if ($record->document_type === 'invoice') {
            return $user->hasRole(['super_admin', 'admin']);
        }

        return true;
    }

    public function restore(User $user, Sale $record): bool
    {
        return $user->hasPermissionTo('eliminar_ventas');
    }

    public function forceDelete(User $user, Sale $record): bool
    {
        return $user->hasPermissionTo('eliminar_ventas');
    }
}
