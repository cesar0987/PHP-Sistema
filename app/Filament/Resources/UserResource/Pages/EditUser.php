<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->syncDirectPermissions();
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

        // Sync direct permissions (replaces all existing direct permissions)
        $user->syncPermissions($directPermissions);
    }
}
