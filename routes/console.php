<?php

use Illuminate\Foundation\Inspiring;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

try {
    $settings = app(GeneralSettings::class);

    // Limpieza de backups antiguos (Diario)
    Schedule::command('backup:clean')->daily()->at('01:00');

    // Ejecución de backups según configuración dinámica
    $backupTask = Schedule::command('backup:run');

    match($settings->backup_frequency) {
        'hourly' => $backupTask->hourly(),
        'six_hours' => $backupTask->everySixHours(),
        'twice_daily' => $backupTask->twiceDaily(1, 13),
        default => $backupTask->dailyAt($settings->backup_time ?? '02:00'),
    };
} catch (\Exception $e) {
    // Si la tabla de ajustes no existe (ej. durante migración inicial), 
    // usamos una configuración por defecto para no romper el booteo.
    Schedule::command('backup:clean')->daily()->at('01:00');
    Schedule::command('backup:run')->daily()->at('02:00');
}
