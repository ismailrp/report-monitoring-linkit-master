<?php

namespace App\Filament\Resources\SummaryAirpayWeeklyResource\Pages;

use App\Filament\Resources\SummaryAirpayWeeklyResource;
use App\Filament\Resources\SummaryAirpayWeeklyResource\Widgets\SummaryAirpayWeeklyStatsWidget;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListSummaryAirpayWeeklies extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = SummaryAirpayWeeklyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SummaryAirpayWeeklyStatsWidget::class,
        ];
    }
}
