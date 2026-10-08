<?php

namespace App\Support\SearchAgents;

use App\Models\Listing;
use App\Models\SearchAgent;
use Illuminate\Database\Eloquent\Builder;

final class RecentMatchCounter
{
    public const DAYS = 7;

    /**
     * Number of listings added in the last seven days that fit the search agent's criteria.
     */
    public function count(SearchAgent $agent): int
    {
        return Listing::query()
            ->where('created_at', '>=', now()->subDays(self::DAYS))
            ->whereNotNull('price_cents')
            ->when($agent->price_from !== null, fn (Builder $query) => $query->where('price_cents', '>=', $this->toCents($agent->price_from)))
            ->when($agent->price_to !== null, fn (Builder $query) => $query->where('price_cents', '<=', $this->toCents($agent->price_to)))
            ->when($agent->pot_return_from !== null, fn (Builder $query) => $query->where('rendite_pot_num', '>=', $agent->pot_return_from))
            ->when($agent->pot_return_to !== null, fn (Builder $query) => $query->where('rendite_pot_num', '<=', $agent->pot_return_to))
            ->when(filled($agent->postcode), fn (Builder $query) => $query->nearPostcode($agent->postcode, (float) $agent->radius))
            ->get(['id', 'flaeche', 'zimmer'])
            ->filter(fn (Listing $listing): bool => $this->isWithin($this->toNumber($listing->flaeche), $agent->size_from, $agent->size_to)
                && $this->isWithin($this->toNumber($listing->zimmer), $agent->min_rooms, $agent->max_rooms))
            ->count();
    }

    private function toCents(string $euros): int
    {
        return (int) round((float) $euros * 100);
    }

    /**
     * Reads the number out of listing texts such as "44m²", "1.200m²" or "2,5"; "-" has none.
     */
    private function toNumber(?string $text): ?float
    {
        if ($text === null || ! preg_match('/\d[\d.]*(?:,\d+)?/', $text, $matches)) {
            return null;
        }

        return (float) str_replace(['.', ','], ['', '.'], $matches[0]);
    }

    private function isWithin(?float $value, ?int $from, ?int $to): bool
    {
        if ($from === null && $to === null) {
            return true;
        }

        return $value !== null
            && ($from === null || $value >= $from)
            && ($to === null || $value <= $to);
    }
}
