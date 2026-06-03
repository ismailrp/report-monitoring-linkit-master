<?php

namespace App\Filament\Resources\TransactionHourResource\Pages;

use App\Filament\Resources\TransactionHourResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransactionHours extends ListRecords
{
    protected static string $resource = TransactionHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
