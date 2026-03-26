<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int|null $customer_id
 * @property int $user_id
 * @property int $branch_id
 * @property int|null $cash_register_id
 * @property float $subtotal
 * @property float $discount
 * @property float $tax
 * @property float $total
 * @property string $status
 * @property Carbon $sale_date
 * @property string|null $notes
 * @property string|null $cancellation_reason
 */
#[ScopedBy([BranchScope::class])]
class Sale extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'user_id',
        'branch_id',
        'cash_register_id',
        'subtotal',
        'subtotal_exenta',
        'subtotal_5',
        'subtotal_10',
        'discount',
        'tax',
        'tax_5',
        'tax_10',
        'total',
        'status',
        'payment_method',
        'invoice_number',
        'timbrado',
        'cdc',
        'document_type',
        'condition',
        'sale_date',
        'notes',
        'cancellation_reason',
        'credit_due_date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['total', 'status', 'discount', 'customer_id', 'cancellation_reason'])
            ->logOnlyDirty()
            ->useLogName('venta')
            ->setDescriptionForEvent(fn (string $eventName) => "Venta #{$this->id} fue {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'subtotal_exenta' => 'decimal:2',
            'subtotal_5' => 'decimal:2',
            'subtotal_10' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'tax_5' => 'decimal:2',
            'tax_10' => 'decimal:2',
            'total' => 'decimal:2',
            'sale_date' => 'datetime',
            'credit_due_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }
}
