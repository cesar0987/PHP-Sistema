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
        'current_balance',
        'is_credit_enabled',
        'credit_limit',
        'credit_due_date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'document', 'phone', 'email', 'active', 'is_credit_enabled', 'credit_limit'])
            ->logOnlyDirty()
            ->useLogName('cliente')
            ->setDescriptionForEvent(fn (string $eventName) => "Cliente '{$this->name}' fue {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_credit_enabled' => 'boolean',
            'credit_limit' => 'decimal:2',
            'credit_due_date' => 'date',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }
}
