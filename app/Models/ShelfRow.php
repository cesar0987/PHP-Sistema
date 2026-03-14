<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShelfRow extends Model
{
    protected $fillable = [
        'shelf_id',
        'number',
        'description',
    ];

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(ShelfLevel::class);
    }
}
