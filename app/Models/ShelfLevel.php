<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShelfLevel extends Model
{
    protected $fillable = [
        'shelf_row_id',
        'number',
        'description',
    ];

    public function row(): BelongsTo
    {
        return $this->belongsTo(ShelfRow::class, 'shelf_row_id');
    }

    public function productLocations(): HasMany
    {
        return $this->hasMany(ProductLocation::class);
    }

    public function getFullLocationAttribute(): string
    {
        return $this->row->shelf->aisle->warehouse->name.' - '.
               $this->row->shelf->aisle->code.'-'.
               $this->row->shelf->number.'-'.
               $this->row->number.'-'.
               $this->number;
    }
}
