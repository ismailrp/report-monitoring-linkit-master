<?php

namespace App\Filament\Resources\StatusTelcoResource\Pages;

use App\Filament\Resources\StatusTelcoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatusTelco extends EditRecord
{
    protected static string $resource = StatusTelcoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
