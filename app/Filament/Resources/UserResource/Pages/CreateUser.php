<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $this->syncRole();
        $this->syncDirectPermissions();
    }

    protected function syncRole(): void
    {
        $user = $this->record;
        $roleName = $this->data['role'] ?? null;

        if ($roleName) {
            $user->syncRoles([$roleName]);
        }
    }

    protected function syncDirectPermissions(): void
    {
        $user = $this->record;
        $data = $this->data;
        $directPermissions = [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permissions_') && is_array($value)) {
                $directPermissions = array_merge($directPermissions, $value);
            }
        }

        if (! empty($directPermissions)) {
            $user->givePermissionTo($directPermissions);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
