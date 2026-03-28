<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Company extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'ruc',
        'ruc_dv',
        'tipo_contribuyente',
        'tipo_regimen',
        'address',
        'num_casa',
        'phone',
        'email',
        'logo',
        'active',
        'departamento_code',
        'departamento_desc',
        'ciudad_code',
        'ciudad_desc',
        'actividad_eco_code',
        'actividad_eco_desc',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('empresa')
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Datos de la empresa '{$this->name}' fueron {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
