<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'level',
        'category',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}
