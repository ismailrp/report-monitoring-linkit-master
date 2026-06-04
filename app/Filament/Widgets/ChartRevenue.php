<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ChartRevenue extends ApexChartWidget
{
    protected static ?string $heading = 'Revenue per Jam (Kemarin)';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $contentHeight = 300;

    protected function getOptions(): array
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $data = DB::table('summary_daily')
            ->join('countries', 'summary_daily.id_country', '=', 'countries.id')
            ->select(
                'summary_daily.hour',
                'countries.country',
                DB::raw("ROUND(SUM(summary_daily.revenue * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END)), 0) as total_revenue")
            )
            ->where('summary_daily.date', $yesterday)
            ->groupBy('summary_daily.hour', 'countries.country')
            ->orderBy('summary_daily.hour')
            ->get();

        $hours = range(0, 23);
        $countries = $data->pluck('country')->unique()->values();

        $colors = [
            '#36A2EB', '#FF6384', '#4BC0C0', '#9966FF',
            '#FF9F40', '#FFCD56', '#2ECC71', '#E74C3C',
            '#1ABC9C', '#2C3E50', '#8E44AD', '#D35400',
        ];

        $series = [];
        foreach ($countries as $index => $country) {
            $revenues = [];
            foreach ($hours as $hour) {
                $revenues[] = (int) ($data
                    ->where('country', $country)
                    ->where('hour', $hour)
                    ->pluck('total_revenue')
                    ->first() ?? 0);
            }
            $series[] = [
                'name' => $country,
                'data' => $revenues,
                'color' => $colors[$index % count($colors)],
            ];
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 300,
                'toolbar' => ['show' => false],
                'zoom' => ['enabled' => false],
            ],
            'series' => $series,
            'xaxis' => [
                'categories' => array_map(fn($h) => "{$h}:00", $hours),
                'title' => ['text' => 'Hour'],
                'tickAmount' => 23,
                'labels' => ['rotate' => 0],
            ],
            'yaxis' => [
                'title' => ['text' => 'Revenue (USD)'],
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],
            'tooltip' => [
                'shared' => true,
                'intersect' => false,
            ],
            'legend' => [
                'position' => 'bottom',
            ],
            'grid' => [
                'borderColor' => '#e0e0e0',
            ],
        ];
    }
}
