<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class SearchAgentListingMatch extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'run_id',
        'search_agent_id',
        'user_id',
        'listing_ids_json',
        'match_count',
        'public_token',
        'is_public',
        'matched_at',
        'emailed_at',
    ];

    protected $casts = [
        'listing_ids_json' => 'array',
        'is_public' => 'boolean',
        'matched_at' => 'datetime',
        'emailed_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(SearchAgentRun::class, 'run_id');
    }

    public function searchAgent(): BelongsTo
    {
        return $this->belongsTo(SearchAgent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(SearchAgentMatchClick::class);
    }

    public function listings(): Collection
    {
        return Listing::whereIn('id', $this->listing_ids_json ?? [])->get();
    }
}
