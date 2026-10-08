<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchAgentMatchClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'search_agent_listing_match_id',
        'user_id',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(SearchAgentListingMatch::class, 'search_agent_listing_match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
