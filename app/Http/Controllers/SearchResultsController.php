<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchResultsController extends Controller
{
    public function index(Request $request): View
    {
        $matches = $request->user()
            ->listingMatches()
            ->whereNotNull('emailed_at')
            ->with('searchAgent')
            ->orderByDesc('emailed_at')
            ->orderByDesc('id')
            ->paginate(10);

        $listings = Listing::whereIn('id', $matches->flatMap(fn ($match) => $match->listing_ids_json ?? [])->unique())
            ->get()
            ->keyBy('id');

        $savedIds = $request->user()
            ->savedListings()
            ->whereIn('listings.id', $listings->keys())
            ->pluck('listings.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('search-results.index', [
            'matches' => $matches,
            'listings' => $listings,
            'savedIds' => $savedIds,
        ]);
    }

    public function saved(): View
    {
        return view('search-results.saved');
    }
}
