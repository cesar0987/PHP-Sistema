<?php

use App\Settings\GeneralSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Configuración por defecto para no romper el booteo durante migraciones
$backupFrequency = 'daily';
$backupTime = '02:00';

try {
    // Solo intentamos cargar ajustes si la tabla existe y no estamos en medio de una migración bloqueante
    if (!app()->runningInConsole() || !str_contains(implode(' ', $_SERVER['argv'] ?? []), 'migrate')) {
        $settings = app(GeneralSettings::class);
        $backupFrequency = $settings->backup_frequency;
        $backupTime = $settings->backup_time ?? '02:00';
    }
} catch (\Exception $e) {
    // Fallback silencioso
}

// Limpieza de backups antiguos (Diario)
Schedule::command('backup:clean')->daily()->at('01:00');

// Ejecución de backups según configuración dinámica
$backupTask = Schedule::command('backup:run');

match($backupFrequency) {
    'hourly' => $backupTask->hourly(),
    'six_hours' => $backupTask->everySixHours(),
    'twice_daily' => $backupTask->twiceDaily(1, 13),
    default => $backupTask->dailyAt($backupTime),
};
