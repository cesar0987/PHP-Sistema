<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CreditService;

class CustomerPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'sale_id',
        'amount',
        'date',
        'method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    protected static function booted()
    {
        $updateBalance = function ($payment) {
            if ($payment->customer) {
                app(CreditService::class)->updateCustomerBalance($payment->customer);
            }
        };

        static::created($updateBalance);
        static::updated($updateBalance);
        static::deleted($updateBalance);
    }
}
