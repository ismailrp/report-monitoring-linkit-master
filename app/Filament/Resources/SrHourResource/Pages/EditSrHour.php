<?php

namespace App\Filament\Resources\SrHourResource\Pages;

use App\Filament\Resources\SrHourResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSrHour extends EditRecord
{
    protected static string $resource = SrHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
