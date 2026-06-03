<?php

namespace App\Filament\Resources\SummaryAirpayResource\Widgets;

use App\Models\SummaryDaily;
use App\Models\SummaryWeekly;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SummaryAirpayStatsWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getTablePage(): string
    {
        return \App\Filament\Resources\SummaryAirpayResource\Pages\ListSummaryAirpays::class;
    }

    protected function getStats(): array
    {
        // Query dari tabel (sudah terfilter sesuai filter aktif)
        $query = $this->getPageTableQuery();

        $mtSuccess = (clone $query)->sum('mt_success');
        $mtFailed  = (clone $query)->sum('mt_failed');
        $totalMt   = $mtSuccess + $mtFailed;

        $moReg   = (clone $query)->sum('mo_reg');
        $moUnreg = (clone $query)->sum('mo_unreg');
        $totalMo = $moReg + $moUnreg;

        $totalRevenue = (clone $query)->sum('revenue');
        $totalSubActive = (clone $query)->sum('sub_active');

        return [
            Stat::make('MT SUCCESS', number_format($mtSuccess))
                ->description('Dari data harian (filtered)')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('MT FAILED', number_format($mtFailed))
                ->description('Dari data harian (filtered)')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('REG', number_format($moReg))
                ->description('Dari data harian (filtered)')
                ->descriptionIcon('heroicon-o-arrow-down-tray')
                ->color('success'),

            Stat::make('UNREG', number_format($moUnreg))
                ->description('Dari data harian (filtered)')
                ->descriptionIcon('heroicon-o-arrow-up-tray')
                ->color('warning'),

            Stat::make('REVENUE', number_format($totalRevenue))
                ->description('Dari data harian (filtered)')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
