<?php

namespace App\Filament\Resources\SummaryWeeklyResource\Pages;

use App\Filament\Resources\SummaryWeeklyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSummaryWeeklies extends ListRecords
{
    protected static string $resource = SummaryWeeklyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
