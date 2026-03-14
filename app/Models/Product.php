<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'cost_price', 'sale_price', 'active', 'min_stock', 'barcode', 'sku'])
            ->logOnlyDirty()
            ->useLogName('producto')
            ->setDescriptionForEvent(fn (string $eventName) => "Producto '{$this->name}' fue {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'active' => 'boolean',
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
}
