<?php

namespace App\Filament\Pages\Settings;

use Filament\Pages\Page;

class GeneralSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administración';
    
    protected static ?string $title = 'Configuración General';

    protected static ?string $navigationLabel = 'Ajustes Generales';

    protected static ?int $navigationSort = 110;

    protected static string $view = 'filament.pages.settings.general-settings';
    
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }
}
