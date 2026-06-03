<?php

namespace App\Filament\Resources\SummaryAirpayResource\Pages;

use App\Filament\Resources\SummaryAirpayResource;
use App\Filament\Resources\SummaryAirpayResource\Widgets\SummaryAirpayStatsWidget;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListSummaryAirpays extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = SummaryAirpayResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SummaryAirpayStatsWidget::class,
        ];
    }
}
