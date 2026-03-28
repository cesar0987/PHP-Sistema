<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'brand',
        'barcode',
        'sku',
        'cost_price',
        'sale_price',
        'tax_percentage',
        'min_stock',
        'active',
        'has_expiry',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('producto')
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Producto '{$this->name}' fue {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'active' => 'boolean',
            'has_expiry' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stockMovements(): HasManyThrough
    {
        return $this->hasManyThrough(StockMovement::class, ProductVariant::class);
    }
}
