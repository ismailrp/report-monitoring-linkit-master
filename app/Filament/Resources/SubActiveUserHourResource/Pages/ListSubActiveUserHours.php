<?php

namespace App\Filament\Resources\SubActiveUserHourResource\Pages;

use App\Filament\Resources\SubActiveUserHourResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubActiveUserHours extends ListRecords
{
    protected static string $resource = SubActiveUserHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
