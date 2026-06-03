<?php

namespace App\Filament\Resources\StatusTelcoResource\Pages;

use App\Filament\Resources\StatusTelcoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStatusTelcos extends ListRecords
{
    protected static string $resource = StatusTelcoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
