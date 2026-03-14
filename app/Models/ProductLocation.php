<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLocation extends Model
{
    protected $fillable = [
        'product_variant_id',
        'shelf_level_id',
        'quantity',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function shelfLevel(): BelongsTo
    {
        return $this->belongsTo(ShelfLevel::class);
    }
}
