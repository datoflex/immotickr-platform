<?php

namespace App\Filament\Resources\SearchAgentResource\Pages;

use App\Filament\Resources\SearchAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSearchAgent extends EditRecord
{
    protected static string $resource = SearchAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
