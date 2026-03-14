<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExpenseCategory extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = ['name', 'description', 'active'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('categoria_gasto')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
