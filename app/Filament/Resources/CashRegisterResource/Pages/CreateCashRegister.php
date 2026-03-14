<?php

namespace App\Filament\Resources\CashRegisterResource\Pages;

use App\Filament\Resources\CashRegisterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCashRegister extends CreateRecord
{
    protected static string $resource = CashRegisterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = 'Caja - ' . now()->format('d/m/Y H:i:s');
        $data['opened_at'] = now();
        $data['status'] = 'open';

        return $data;
    }
}