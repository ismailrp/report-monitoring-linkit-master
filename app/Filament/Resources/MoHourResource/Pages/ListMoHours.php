<?php

namespace App\Filament\Resources\MoHourResource\Pages;

use App\Filament\Resources\MoHourResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMoHours extends ListRecords
{
    protected static string $resource = MoHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
