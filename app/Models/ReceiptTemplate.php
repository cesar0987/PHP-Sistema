<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'content_html',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
