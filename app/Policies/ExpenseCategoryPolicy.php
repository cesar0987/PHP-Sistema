<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;

class ExpenseCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_gastos');
    }

    public function view(User $user, ExpenseCategory $record): bool
    {
        return $user->hasPermissionTo('ver_gastos');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crear_gastos');
    }

    public function update(User $user, ExpenseCategory $record): bool
    {
        return $user->hasPermissionTo('editar_gastos');
    }

    public function delete(User $user, ExpenseCategory $record): bool
    {
        return $user->hasPermissionTo('eliminar_gastos');
    }

    public function restore(User $user, ExpenseCategory $record): bool
    {
        return $user->hasPermissionTo('eliminar_gastos');
    }

    public function forceDelete(User $user, ExpenseCategory $record): bool
    {
        return $user->hasRole('super_admin');
    }
}
