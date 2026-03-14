<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'ruc',
        'phone',
        'email',
        'address',
        'active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'ruc', 'phone', 'email', 'active'])
            ->logOnlyDirty()
            ->useLogName('proveedor')
            ->setDescriptionForEvent(fn (string $eventName) => "Proveedor '{$this->name}' fue {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'ruc' => 'encrypted',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
