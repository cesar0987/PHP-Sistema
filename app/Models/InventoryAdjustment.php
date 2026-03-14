<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_id
 * @property int $warehouse_id
 * @property string $reason
 * @property string $status
 */
class InventoryAdjustment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'warehouse_id',
        'reason',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reason', 'status', 'warehouse_id'])
            ->logOnlyDirty()
            ->useLogName('ajuste_inventario')
            ->setDescriptionForEvent(fn (string $eventName) => "Ajuste de inventario #{$this->id} fue {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentItem::class, 'adjustment_id');
    }
}
