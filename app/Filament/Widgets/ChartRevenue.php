<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChartRevenue extends ChartWidget
{
    protected static ?string $heading = 'Revenue per Jam (Kemarin)';
    protected int|string|array $columnSpan = 'full';


    protected function getData(): array
    {
        $yesterday = Carbon::yesterday()->toDateString();

        // Ambil data revenue per jam per country
        $data = DB::table('summary_daily')
            ->join('countries', 'summary_daily.id_country', '=', 'countries.id')
            ->select('summary_daily.hour', 'countries.country', DB::raw("ROUND(SUM(summary_daily.revenue * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END)), 0) as total_revenue"))
            ->where('summary_daily.date', $yesterday)
            ->groupBy('summary_daily.hour', 'countries.country')
            ->orderBy('summary_daily.hour')
            ->get();

        // Label jam 0-23
        $hours = range(0, 23);

        // Ambil list country unik
        $countries = $data->pluck('country')->unique();

        $datasets = [];
        $colors = [
            '#36A2EB', '#FF6384', '#4BC0C0', '#9966FF',
            '#FF9F40', '#FFCD56', '#2ECC71', '#E74C3C',
            '#1ABC9C', '#2C3E50', '#8E44AD', '#D35400'
        ];

        foreach ($countries as $index => $country) {
            $revenues = [];
            foreach ($hours as $hour) {
                $revenues[] = $data
                    ->where('country', $country)
                    ->where('hour', $hour)
                    ->pluck('total_revenue')
                    ->first() ?? 0;
            }

            $datasets[] = [
                'label' => $country,
                'data' => $revenues,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)],
                'borderWidth' => 3, // garis lebih tebal
                'tension' => 0.4,   // garis smooth
                'fill' => false,
            ];
        }

        return [
            'labels' => $hours,
            'datasets' => $datasets,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    // Biar full width
    // protected function getColumns(): int
    // {
    //     return 12;
    // }
}
