<?php

namespace App\Filament\Resources\SummaryWeeklyResource\Pages;

use App\Filament\Resources\SummaryWeeklyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSummaryWeekly extends EditRecord
{
    protected static string $resource = SummaryWeeklyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
