<?php

use Illuminate\Foundation\Inspiring;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
