<?php

namespace App\Filament\Resources\TransactionHourResource\Pages;

use App\Filament\Resources\TransactionHourResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransactionHour extends EditRecord
{
    protected static string $resource = TransactionHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
