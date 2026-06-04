<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AlertReport;
use App\Filament\Resources\SrHourResource\Pages\SrHourDaily;
use App\Filament\Resources\SrHourResource\Pages\SRHourly;
use App\Filament\Pages\WeeklySummary;
use App\Filament\Pages\SubActiveRenewalDaily;
use App\Filament\Resources\MOHourResource\Pages\MODaily;
use App\Filament\Resources\MOHourResource\Pages\MOHourly;
use App\Filament\Resources\SubActiveUserHourResource\Pages\SubActiveList;
use App\Filament\Resources\TransactionHourResource\Pages\TransactionDaily;
use App\Filament\Resources\TransactionHourResource\Pages\TransactionHourly;
use App\Filament\Resources\TransactionHourResource\Pages\TransactionMonthly;
use App\Filament\Pages\AlertRenewalWeeklyReport;
use App\Filament\Widgets\CountWidget;
use App\Filament\Widgets\ChartRevenue;
use App\Filament\Widgets\TableTopRevenue;
use App\Filament\Widgets\TableTopDropSr;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Monitoring Transact')
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverResources( app_path('Filament/Resources'),  'App\\Filament\\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverPages( app_path('Filament/Pages'), 'App\\Filament\\Pages')
            ->pages([

                Pages\Dashboard::class,
                WeeklySummary::class,
                AlertReport::class,
                SRHourly::class, // <-- Tambahkan kelas halaman Anda di sini
                SrHourDaily::class,
                MOHourly::class,
                MODaily::class,
                SubActiveRenewalDaily::class,
                // HourlyReportPage::class, // <-- Tambahkan kelas halaman Anda di sini
                TransactionHourly::class, // <-- Tambahkan kelas halaman Anda di sini
                TransactionDaily::class,
                TransactionMonthly::class,
                SubActiveRenewalDaily::class,
                AlertRenewalWeeklyReport::class,
                // WeeklyReport::class,
                // WeeklyReport::class,
                // SubActiveList::class,

            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                CountWidget::class,
                TableTopRevenue::class,
                TableTopDropSr::class,
                ChartRevenue::class,
            ])
            ->navigationGroups([
                // 'Order',
                // 'Product',
                // 'Admin',
                // 'Log',
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->plugin(
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            )
            ->plugin(
                \Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin::make(),
            )
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
