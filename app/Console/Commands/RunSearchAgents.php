<?php

namespace App\Console\Commands;

use App\Mail\SearchAgentMatchesFound;
use App\Models\Listing;
use App\Models\SearchAgent;
use App\Models\SearchAgentListingMatch;
use App\Models\SearchAgentRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('app:run-search-agents')]
#[Description('Match new listings against every search agent and notify owners of new results')]
class RunSearchAgents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $agents = SearchAgent::query()->orderBy('id')->get();

        if ($agents->isEmpty()) {
            $this->info('No search agents found.');

            return self::SUCCESS;
        }

        foreach ($agents as $agent) {
            $this->processSearchAgent($agent);
        }

        return self::SUCCESS;
    }

    private function processSearchAgent(SearchAgent $agent): void
    {
        $window = $this->findProcessingWindow($agent);

        if ($window === null) {
            $run = SearchAgentRun::create([
                'search_agent_id' => $agent->id,
                'user_id' => $agent->user_id,
                'public_uuid' => $this->generateToken(),
                'status' => 'no_new_candidates',
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            $this->saveAggregatedMatch($run, $agent, []);

            $this->line("Search agent #{$agent->id}: no new candidates.");

            return;
        }

        [$minCents, $maxCents] = $this->priceRangeInCents($agent);
        $listingIds = $this->fetchCandidateListingIds($agent, $window, $minCents, $maxCents);

        /** @var SearchAgentListingMatch $match */
        $match = DB::transaction(function () use ($agent, $window, $listingIds) {
            $run = SearchAgentRun::create([
                'search_agent_id' => $agent->id,
                'user_id' => $agent->user_id,
                'public_uuid' => $this->generateToken(),
                'status' => 'running',
                'from_listing_id' => $window['from_listing_id'],
                'to_listing_id' => $window['to_listing_id'],
                'started_at' => now(),
            ]);

            $match = $this->saveAggregatedMatch($run, $agent, $listingIds);

            $agent->update(['last_processed_listing_id' => $window['to_listing_id']]);

            $run->update([
                'status' => $listingIds === [] ? 'completed_no_matches' : 'completed',
                'new_match_count' => count($listingIds),
                'completed_at' => now(),
            ]);

            return $match;
        });

        $this->line("Search agent #{$agent->id}: ".count($listingIds).' new match(es).');

        if ($listingIds !== []) {
            $this->notifyOwner($agent, $match);
        }
    }

    private function findProcessingWindow(SearchAgent $agent): ?array
    {
        if ($agent->last_processed_listing_id !== null && $agent->last_processed_listing_id > 0) {
            $maxListingId = Listing::where('id', '>', $agent->last_processed_listing_id)->max('id');

            if ($maxListingId === null) {
                return null;
            }

            return [
                'from_listing_id' => $agent->last_processed_listing_id,
                'to_listing_id' => (int) $maxListingId,
                'created_after' => null,
            ];
        }

        $maxListingId = Listing::where('created_at', '>=', $agent->created_at)->max('id');

        if ($maxListingId === null) {
            return null;
        }

        return [
            'from_listing_id' => null,
            'to_listing_id' => (int) $maxListingId,
            'created_after' => $agent->created_at,
        ];
    }

    private function fetchCandidateListingIds(SearchAgent $agent, array $window, ?int $minCents, ?int $maxCents): array
    {
        $query = Listing::query()
            ->whereNotNull('price_cents')
            ->where('id', '<=', $window['to_listing_id']);

        if ($window['from_listing_id'] !== null) {
            $query->where('id', '>', $window['from_listing_id']);
        } elseif ($window['created_after'] !== null) {
            $query->where('created_at', '>=', $window['created_after']);
        }

        if ($minCents !== null) {
            $query->where('price_cents', '>=', $minCents);
        }

        if ($maxCents !== null) {
            $query->where('price_cents', '<=', $maxCents);
        }

        if (filled($agent->postcode)) {
            $query->nearPostcode($agent->postcode, (float) $agent->radius);
        }

        return $query->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function saveAggregatedMatch(SearchAgentRun $run, SearchAgent $agent, array $listingIds): SearchAgentListingMatch
    {
        return SearchAgentListingMatch::create([
            'run_id' => $run->id,
            'search_agent_id' => $agent->id,
            'user_id' => $agent->user_id,
            'listing_ids_json' => array_values($listingIds),
            'match_count' => count($listingIds),
            'public_token' => $this->generateToken(),
            'is_public' => true,
            'matched_at' => now(),
        ]);
    }

    private function priceRangeInCents(SearchAgent $agent): array
    {
        $min = $agent->price_from !== null ? (int) round(((float) $agent->price_from) * 100) : null;
        $max = $agent->price_to !== null ? (int) round(((float) $agent->price_to) * 100) : null;

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }

    private function notifyOwner(SearchAgent $agent, SearchAgentListingMatch $match): void
    {
        $user = $agent->user;

        if ($user === null || empty($user->email)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new SearchAgentMatchesFound($agent, $match));

            $match->update(['emailed_at' => now()]);
        } catch (Throwable $e) {
            $this->warn("Search agent #{$agent->id}: failed to send notification ({$e->getMessage()}).");
        }
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
