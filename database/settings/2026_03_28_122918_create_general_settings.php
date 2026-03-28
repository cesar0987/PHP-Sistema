<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Terracota POS');
        $this->migrator->add('general.backup_frequency', 'daily'); // daily, twice_daily, hourly
        $this->migrator->add('general.backup_time', '02:00');
    }
};
