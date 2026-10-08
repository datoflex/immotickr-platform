<?php

namespace App\Filament\Widgets;

use App\Support\Metrics\KpiCalculator;
use App\Support\Metrics\KpiResult;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class KpiStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = -1;

    protected ?string $heading = 'Kennzahlen';

    protected ?string $description = 'Startziele aus der Vermarktungsstrategie. Der Zeitraum filtert nach Registrierung, Anlage, Versand bzw. Klick.';

    protected function getStats(): array
    {
        $startDate = $this->filters['dateRange']['start'] ?? null;
        $endDate = $this->filters['dateRange']['end'] ?? null;

        $kpis = new KpiCalculator(
            $startDate ? Carbon::parse($startDate)->startOfDay() : null,
            $endDate ? Carbon::parse($endDate)->endOfDay() : null,
        );

        return [
            $this->stat('Aktivierung', $kpis->activation(), 50, 'Registrierten legen am ersten Tag einen Suchagent an', 'noch im ersten Tag'),
            $this->stat('Erster Treffer', $kpis->firstHit(), 80, 'Suchagenten haben nach 48 Stunden einen Treffer', 'noch in den ersten 48 Stunden'),
            $this->stat('Klickrate', $kpis->clickRate(), 25, 'Treffer-E-Mails wurden geklickt'),
            $this->stat('Bindung', $kpis->retention(), 40, 'Nutzern klicken in Woche 4 noch'),
            $this->stat('Merkliste', $kpis->savedListingRate(), 30, 'aktiven Nutzern haben ein Inserat gemerkt'),
        ];
    }

    private function stat(string $label, KpiResult $result, int $targetPercentage, string $meaning, ?string $pendingMeaning = null): Stat
    {
        $percentage = $result->percentage();
        $pending = $result->pending > 0 ? " · {$result->pending} {$pendingMeaning}" : '';

        if ($percentage === null) {
            return Stat::make($label, '–')
                ->description("Noch keine Daten · Ziel {$targetPercentage} %{$pending}");
        }

        return Stat::make($label, number_format($percentage, 0, ',', '.').' %')
            ->description("{$result->hits} von {$result->total} {$meaning} · Ziel {$targetPercentage} %{$pending}")
            ->color($percentage >= $targetPercentage ? 'success' : 'danger');
    }
}
