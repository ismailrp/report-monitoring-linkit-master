<?php

namespace App\Filament\Resources\TelcoResource\Pages;

use App\Filament\Resources\TelcoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTelcos extends ListRecords
{
    protected static string $resource = TelcoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
