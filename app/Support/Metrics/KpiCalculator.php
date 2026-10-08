<?php

namespace App\Support\Metrics;

use App\Models\SearchAgent;
use App\Models\SearchAgentListingMatch;
use App\Models\SearchAgentMatchClick;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class KpiCalculator
{
    public const ACTIVATION_WINDOW_HOURS = 24;

    public const FIRST_HIT_WINDOW_HOURS = 48;

    public const RETENTION_WEEK_STARTS_AFTER_DAYS = 21;

    public const RETENTION_WEEK_ENDS_AFTER_DAYS = 28;

    public function __construct(
        private readonly ?CarbonInterface $from = null,
        private readonly ?CarbonInterface $until = null,
    ) {}

    /**
     * Share of registered users who created a search agent on their first day.
     *
     * Users whose first day is still running are reported as pending instead of being rated.
     */
    public function activation(): KpiResult
    {
        [$pendingUsers, $users] = $this->withinPeriod(User::query(), 'created_at')
            ->withMin('searchAgents', 'created_at')
            ->get()
            ->partition(fn (User $user): bool => $this->isStillWithin($user->created_at, self::ACTIVATION_WINDOW_HOURS));

        $activated = $users->filter(fn (User $user): bool => $this->happenedWithin(
            $user->search_agents_min_created_at,
            $user->created_at,
            self::ACTIVATION_WINDOW_HOURS,
        ));

        return new KpiResult($activated->count(), $users->count(), $pendingUsers->count());
    }

    /**
     * Share of search agents that found at least one listing within 48 hours.
     *
     * Search agents younger than 48 hours are reported as pending instead of being rated.
     */
    public function firstHit(): KpiResult
    {
        [$pendingAgents, $agents] = $this->withinPeriod(SearchAgent::query(), 'created_at')
            ->withMin(['listingMatches' => fn (Builder $query) => $query->where('match_count', '>', 0)], 'matched_at')
            ->get()
            ->partition(fn (SearchAgent $agent): bool => $this->isStillWithin($agent->created_at, self::FIRST_HIT_WINDOW_HOURS));

        $agentsWithHit = $agents->filter(fn (SearchAgent $agent): bool => $this->happenedWithin(
            $agent->listing_matches_min_matched_at,
            $agent->created_at,
            self::FIRST_HIT_WINDOW_HOURS,
        ));

        return new KpiResult($agentsWithHit->count(), $agents->count(), $pendingAgents->count());
    }

    /**
     * Share of sent result emails whose link was clicked at least once.
     */
    public function clickRate(): KpiResult
    {
        $emails = $this->withinPeriod(SearchAgentListingMatch::query(), 'emailed_at')
            ->whereNotNull('emailed_at');

        return new KpiResult(
            (clone $emails)->whereHas('clicks')->count(),
            $emails->count(),
        );
    }

    /**
     * Share of registered users who still clicked a result email in their fourth week.
     *
     * Users registered less than four weeks ago are left out.
     */
    public function retention(): KpiResult
    {
        $users = $this->withinPeriod(User::query(), 'created_at')
            ->where('created_at', '<=', now()->subDays(self::RETENTION_WEEK_ENDS_AFTER_DAYS))
            ->with('matchClicks')
            ->get();

        $retained = $users->filter(fn (User $user): bool => $user->matchClicks->contains(
            fn (SearchAgentMatchClick $click): bool => $click->clicked_at->between(
                $user->created_at->copy()->addDays(self::RETENTION_WEEK_STARTS_AFTER_DAYS),
                $user->created_at->copy()->addDays(self::RETENTION_WEEK_ENDS_AFTER_DAYS),
            ),
        ));

        return new KpiResult($retained->count(), $users->count());
    }

    /**
     * Share of active users (at least one email click in the period) with a saved listing.
     */
    public function savedListingRate(): KpiResult
    {
        $activeUsers = User::query()->whereHas(
            'matchClicks',
            fn (Builder $query) => $this->withinPeriod($query, 'clicked_at'),
        );

        return new KpiResult(
            (clone $activeUsers)->whereHas('savedListings')->count(),
            $activeUsers->count(),
        );
    }

    private function withinPeriod(Builder $query, string $column): Builder
    {
        return $query
            ->when($this->from, fn (Builder $query) => $query->where($column, '>=', $this->from))
            ->when($this->until, fn (Builder $query) => $query->where($column, '<=', $this->until));
    }

    private function isStillWithin(CarbonInterface $start, int $hours): bool
    {
        return $start->copy()->addHours($hours)->isFuture();
    }

    private function happenedWithin(?string $happenedAt, CarbonInterface $start, int $hours): bool
    {
        return $happenedAt !== null
            && Carbon::parse($happenedAt)->lessThanOrEqualTo($start->copy()->addHours($hours));
    }
}
