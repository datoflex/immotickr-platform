<?php

namespace App\Http\Controllers;

use App\Mail\SearchAgentMatchesFound;
use App\Models\SearchAgentListingMatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchAgentResultsController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $match = SearchAgentListingMatch::where('public_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        if ($request->query('ref') === SearchAgentMatchesFound::LINK_REFERRER) {
            $match->clicks()->create(['user_id' => $match->user_id, 'clicked_at' => now()]);
        }

        return view('search-agent-results.show', [
            'match' => $match,
            'searchAgent' => $match->searchAgent,
            'listings' => $match->listings(),
        ]);
    }
}
