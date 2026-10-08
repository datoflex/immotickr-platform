<?php

namespace App\Filament\Pages;

use CodeWithKyrian\FilamentDateRange\Forms\Components\DateRangePicker;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                DateRangePicker::make('dateRange')
                    ->label('Date range')
                    ->displayFormat('d M Y')
                    ->startPlaceholder('From')
                    ->endPlaceholder('Until'),
            ]);
    }
}
