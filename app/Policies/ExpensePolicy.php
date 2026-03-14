<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_gastos');
    }

    public function view(User $user, Expense $record): bool
    {
        return $user->hasPermissionTo('ver_gastos');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_gastos');
    }

    public function update(User $user, Expense $record): bool
    {
        return $user->hasPermissionTo('editar_gastos');
    }

    public function delete(User $user, Expense $record): bool
    {
        return $user->hasPermissionTo('eliminar_gastos');
    }

    public function restore(User $user, Expense $record): bool
    {
        return $user->hasPermissionTo('eliminar_gastos');
    }

    public function forceDelete(User $user, Expense $record): bool
    {
        return $user->hasRole('super_admin');
    }
}
