<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shelf extends Model
{
    protected $fillable = [
        'warehouse_aisle_id',
        'number',
        'description',
    ];

    public function aisle(): BelongsTo
    {
        return $this->belongsTo(WarehouseAisle::class, 'warehouse_aisle_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ShelfRow::class);
    }
}
