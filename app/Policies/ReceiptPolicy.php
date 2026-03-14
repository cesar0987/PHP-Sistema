<?php

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;

class ReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_comprobantes');
    }

    public function view(User $user, Receipt $record): bool
    {
        return $user->hasPermissionTo('ver_comprobantes');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_comprobantes');
    }

    public function update(User $user, Receipt $record): bool
    {
        return $user->hasPermissionTo('editar_comprobantes');
    }

    public function delete(User $user, Receipt $record): bool
    {
        return $user->hasPermissionTo('eliminar_comprobantes');
    }

    public function restore(User $user, Receipt $record): bool
    {
        return $user->hasPermissionTo('eliminar_comprobantes');
    }

    public function forceDelete(User $user, Receipt $record): bool
    {
        return $user->hasPermissionTo('eliminar_comprobantes');
    }
}
