<?php

namespace App\Filament\Resources\SummaryAirpayWeeklyResource\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SummaryAirpayWeeklyStatsWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getTablePage(): string
    {
        return \App\Filament\Resources\SummaryAirpayWeeklyResource\Pages\ListSummaryAirpayWeeklies::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $weeklyMt = (clone $query)->sum('total_mt');
        $weeklyMo = (clone $query)->sum('total_mo');
        $weeklyRevenue = (clone $query)->sum('total_revenue');
        $weeklySubActive = (clone $query)->sum('total_sub_active');

        return [
            Stat::make('Weekly Total MT', number_format($weeklyMt))
                ->description('Dari data mingguan (filtered)')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Weekly Total MO', number_format($weeklyMo))
                ->description('Dari data mingguan (filtered)')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Weekly Revenue', number_format($weeklyRevenue))
                ->description('Dari data mingguan (filtered)')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Weekly Sub Active', number_format($weeklySubActive))
                ->description('Dari data mingguan (filtered)')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
        ];
    }
}
