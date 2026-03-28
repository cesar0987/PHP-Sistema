<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;
    public string $backup_frequency;
    public string $backup_time;
    public string $backup_disk;

    public static function group(): string
    {
        return 'general';
    }
}
