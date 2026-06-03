<?php

namespace App\Filament\Resources\MoHourResource\Pages;

use App\Filament\Resources\MoHourResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMoHour extends EditRecord
{
    protected static string $resource = MoHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
