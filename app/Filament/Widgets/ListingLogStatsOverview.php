<?php

namespace App\Filament\Widgets;

use App\Models\ListingLog;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ListingLogStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Emails';

    protected function getStats(): array
    {
        $query = ListingLog::query()->where('message', 'Email subject indicates more than 4 listings');

        if ($startDate = $this->filters['dateRange']['start'] ?? null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $this->filters['dateRange']['end'] ?? null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return [
            Stat::make('Emails with more than 4 listings', $query->count()),
        ];
    }
}
