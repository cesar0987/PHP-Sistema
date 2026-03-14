<?php

namespace App\Policies;

use App\Models\ReceiptTemplate;
use App\Models\User;

class ReceiptTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function view(User $user, ReceiptTemplate $record): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function update(User $user, ReceiptTemplate $record): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function delete(User $user, ReceiptTemplate $record): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, ReceiptTemplate $record): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, ReceiptTemplate $record): bool
    {
        return $user->hasRole('super_admin');
    }
}
