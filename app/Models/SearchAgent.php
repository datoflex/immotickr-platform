<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SearchAgent extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'postcode',
        'radius',
        'price_from',
        'price_to',
        'size_from',
        'size_to',
        'pot_return_from',
        'pot_return_to',
        'min_rooms',
        'max_rooms',
        'uuid',
        'last_processed_listing_id',
    ];

    protected $casts = [
        'radius' => 'decimal:2',
        'price_from' => 'decimal:2',
        'price_to' => 'decimal:2',
        'size_from' => 'integer',
        'size_to' => 'integer',
        'pot_return_from' => 'decimal:2',
        'pot_return_to' => 'decimal:2',
        'min_rooms' => 'integer',
        'max_rooms' => 'integer',
        'last_processed_listing_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (SearchAgent $searchAgent) {
            $searchAgent->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(SearchAgentRun::class);
    }

    public function listingMatches(): HasMany
    {
        return $this->hasMany(SearchAgentListingMatch::class);
    }
}
