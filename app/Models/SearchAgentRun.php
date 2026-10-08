<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SearchAgentRun extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'search_agent_id',
        'user_id',
        'public_uuid',
        'status',
        'from_listing_id',
        'to_listing_id',
        'new_match_count',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function searchAgent(): BelongsTo
    {
        return $this->belongsTo(SearchAgent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listingMatch(): HasOne
    {
        return $this->hasOne(SearchAgentListingMatch::class, 'run_id');
    }
}
