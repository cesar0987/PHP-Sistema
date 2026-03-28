<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
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
