<?php

namespace App\Filament\Resources\ReceiptTemplateResource\Pages;

use App\Filament\Resources\ReceiptTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReceiptTemplate extends CreateRecord
{
    protected static string $resource = ReceiptTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
