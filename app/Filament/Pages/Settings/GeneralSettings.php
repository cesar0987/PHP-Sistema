<?php

namespace App\Filament\Pages\Settings;

use App\Settings\GeneralSettings as Settings;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class GeneralSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administración';
    
    protected static ?string $title = 'Configuración General';

    protected static ?string $navigationLabel = 'Ajustes Generales';

    protected static ?int $navigationSort = 110;

    protected static string $settings = Settings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('General')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Nombre del Sistema')
                            ->required(),
                    ]),
                Section::make('Configuración de Backups')
                    ->schema([
                        Select::make('backup_frequency')
                            ->label('Frecuencia de Backup')
                            ->options([
                                'daily' => 'Diario',
                                'twice_daily' => 'Cada 12 horas',
                                'six_hours' => 'Cada 6 horas',
                                'hourly' => 'Cada hora',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('backup_disk')
                            ->label('Disco de Almacenamiento')
                            ->options([
                                'local' => 'Privado (Local)',
                                'public' => 'Público (Local)',
                                's3' => 'Amazon S3 (Cloud)',
                            ])
                            ->required()
                            ->native(false)
                            ->hint('Por defecto: local (storage/app/private/backups)'),
                        TextInput::make('backup_time')
                            ->label('Hora del Backup Diario')
                            ->placeholder('02:00')
                            ->visible(fn ($get) => $get('backup_frequency') === 'daily')
                            ->required(fn ($get) => $get('backup_frequency') === 'daily'),
                    ])->columns(3),
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }
}

