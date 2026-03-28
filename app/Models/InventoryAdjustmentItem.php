<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryAdjustmentItem extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('ajuste_inventario_item')
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Ítem de ajuste '{$this->productVariant->name}' fue {$eventName}");
    }

    protected $fillable = [
        'adjustment_id',
        'product_variant_id',
        'quantity_before',
        'quantity_after',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
        ];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'adjustment_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
