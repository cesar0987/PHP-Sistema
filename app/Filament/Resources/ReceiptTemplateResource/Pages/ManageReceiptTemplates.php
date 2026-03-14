<?php

namespace App\Filament\Resources\ReceiptTemplateResource\Pages;

use App\Filament\Resources\ReceiptTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageReceiptTemplates extends ManageRecords
{
    protected static string $resource = ReceiptTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
