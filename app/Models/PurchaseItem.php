<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'purchase_id',
        'product_variant_id',
        'quantity',
        'cost',
        'tax_percentage',
        'tax_amount',
        'subtotal',
        'expiry_date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('compra_item')
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Ítem de compra '{$this->productVariant->name}' fue {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'expiry_date' => 'date',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
