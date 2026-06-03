<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\SummaryDaily;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TableTopRevenue extends BaseWidget
{
    protected static ?string $heading = 'TOP REVENUE SERVICE';

    // ✅ 1/3 halaman (12 grid → 4 col = 1/3)
    // protected int|string|array $columnSpan = 4;

    public function table(Table $table): Table
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        return $table
            ->query(
                SummaryDaily::query()
                    ->join('countries', 'summary_daily.id_country', '=', 'countries.id')
                    ->join('services', 'summary_daily.id_service', '=', 'services.id')
                    ->join('operators', 'summary_daily.id_operator', '=', 'operators.id')
                    ->where('summary_daily.hour', 23)
                    ->where('summary_daily.date', $yesterday)
                    ->select('services.service as service', 'operators.operator as operator', DB::raw("MAX(summary_daily.id) as id"), DB::raw("ROUND(SUM(summary_daily.revenue * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END)), 0) as revenue_usd"))
                    ->groupBy('services.service', 'operators.operator')
                    ->orderByDesc('revenue_usd')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('service')->label('Service'),
                Tables\Columns\TextColumn::make('operator')->label('Operator'),
                Tables\Columns\TextColumn::make('revenue_usd')
                    ->label('Revenue USD')
                    ->money('USD')
                    ->alignRight(),
            ])
            ->defaultSort('revenue_usd', 'desc');
    }
}
