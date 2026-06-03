<?php

namespace App\Filament\Resources\TelcoResource\Pages;

use App\Filament\Resources\TelcoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTelco extends EditRecord
{
    protected static string $resource = TelcoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
