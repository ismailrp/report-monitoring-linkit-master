<?php

namespace App\Filament\Resources\SummaryDailyResource\Pages;

use App\Filament\Resources\SummaryDailyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSummaryDaily extends EditRecord
{
    protected static string $resource = SummaryDailyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
