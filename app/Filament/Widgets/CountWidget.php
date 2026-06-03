<?php

namespace App\Filament\Widgets;

use App\Models\Country;
use App\Models\Merchant;
use App\Models\Operator;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Service;

class CountWidget extends BaseWidget
{
    // Opsi: Atur interval polling untuk memperbarui data secara otomatis
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Hitung total layanan dari model Service
        $serviceCount = Service::count();
        $operatorCount = Operator::count();
        $countryCount = Country::count();
        $patnerCount = Merchant::count();

        return [
            Stat::make('Total Country', $countryCount)
                // ->description('Jumlah layanan yang terdaftar')
                ->descriptionIcon('heroicon-o-flag')
                ->color('success'),
            Stat::make('Total Operator', $operatorCount)
                // ->description('Jumlah Operator yang terdaftar')
                ->descriptionIcon('heroicon-o-signal')
                ->color('success'),
            Stat::make('Total Patner', $patnerCount)
                // ->description('Jumlah Patner yang terdaftar')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success'),
            Stat::make('Total Services', $serviceCount)
                // ->description('Jumlah layanan yang terdaftar')
                ->descriptionIcon('heroicon-o-server-stack')
                ->color('success'),
        ];
    }
}
