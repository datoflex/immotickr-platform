<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Benutzer';

    protected function getStats(): array
    {
        $query = User::query();

        if ($startDate = $this->filters['dateRange']['start'] ?? null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $this->filters['dateRange']['end'] ?? null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $userCount = $query->count();
        $usersWithSearchAgentCount = (clone $query)->whereHas('searchAgents')->count();

        $share = $userCount > 0
            ? number_format($usersWithSearchAgentCount / $userCount * 100, 0, ',', '.').' % aller Benutzer'
            : 'Noch keine Benutzer';

        return [
            Stat::make('Benutzer', $userCount),
            Stat::make('Benutzer mit Suchagent', $usersWithSearchAgentCount)
                ->description($share),
        ];
    }
}
