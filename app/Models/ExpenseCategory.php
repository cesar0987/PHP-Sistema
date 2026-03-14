<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'description', 'active'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
