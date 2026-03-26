<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
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
