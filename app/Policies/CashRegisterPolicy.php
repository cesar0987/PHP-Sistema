<?php

namespace App\Policies;

use App\Models\CashRegister;
use App\Models\User;

class CashRegisterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_cajas');
    }

    public function view(User $user, CashRegister $record): bool
    {
        return $user->hasPermissionTo('ver_cajas');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_cajas');
    }

    public function update(User $user, CashRegister $record): bool
    {
        return $user->hasPermissionTo('editar_cajas');
    }

    public function delete(User $user, CashRegister $record): bool
    {
        return $user->hasPermissionTo('eliminar_cajas');
    }

    public function restore(User $user, CashRegister $record): bool
    {
        return $user->hasPermissionTo('eliminar_cajas');
    }

    public function forceDelete(User $user, CashRegister $record): bool
    {
        return $user->hasPermissionTo('eliminar_cajas');
    }
}
