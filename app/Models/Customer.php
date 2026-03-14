<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'document',
        'phone',
        'email',
        'address',
        'active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'document', 'phone', 'email', 'active'])
            ->logOnlyDirty()
            ->useLogName('cliente')
            ->setDescriptionForEvent(fn (string $eventName) => "Cliente '{$this->name}' fue {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
