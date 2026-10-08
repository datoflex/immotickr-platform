<?php

namespace App\Filament\Resources\SearchAgentResource\Pages;

use App\Filament\Resources\SearchAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSearchAgents extends ListRecords
{
    protected static string $resource = SearchAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
