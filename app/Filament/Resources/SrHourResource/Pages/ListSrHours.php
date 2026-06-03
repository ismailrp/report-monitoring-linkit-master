<?php

namespace App\Filament\Resources\SrHourResource\Pages;

use App\Filament\Resources\SrHourResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSrHours extends ListRecords
{
    protected static string $resource = SrHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
