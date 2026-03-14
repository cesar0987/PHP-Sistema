<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver_stock');
    }

    public function view(User $user, Stock $record): bool
    {
        return $user->hasPermissionTo('ver_stock');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Stock $record): bool
    {
        return false;
    }

    public function delete(User $user, Stock $record): bool
    {
        return false;
    }

    public function restore(User $user, Stock $record): bool
    {
        return false;
    }

    public function forceDelete(User $user, Stock $record): bool
    {
        return false;
    }
}
