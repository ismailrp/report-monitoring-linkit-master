<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SummaryAirpayCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Summary Airpay';
    protected static ?string $slug = 'summary-airpay';
}
