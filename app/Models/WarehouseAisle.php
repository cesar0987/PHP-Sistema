<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseAisle extends Model
{
    protected $fillable = [
        'warehouse_id',
        'code',
        'description',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shelves(): HasMany
    {
        return $this->hasMany(Shelf::class);
    }
}
