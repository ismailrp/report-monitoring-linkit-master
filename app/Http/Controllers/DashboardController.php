<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Operator;
use App\Models\Service;
use App\Models\Merchant;
use App\Models\SummaryDaily;
use App\Models\SubActiveUserHour;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $today = date('Y-m-d');
        $user = auth()->user();
        
        // Check role: super_admin sees ALL, otherwise filter by user's operators
        if ($user->hasRole('super_admin')) {
            $operatorIds = Operator::pluck('id');
        } else {
            $operatorIds = $user->operators()->pluck('operators.id');
        }
        // dd($operatorIds);

        // 1. Counts
        $counts = [
            'services' => Service::whereIn('id_operator', $operatorIds)->count(),
            'operators' => $operatorIds->count(),
            'countries' => Operator::whereIn('id', $operatorIds)->distinct('id_country')->count('id_country'),
            'merchants' => Merchant::count(),
        ];

        // 2. Existing Stats (Revenue, MO, MT, Active Users) - Filtered by Operator?
        // Let's filter global stats by these operators too for consistency
        $row = SummaryDaily::where('summary_daily.date', '2026-02-07')
            ->whereIn('summary_daily.id_operator', $operatorIds)
            ->join('countries', 'summary_daily.id_country', '=', 'countries.id')
            ->selectRaw('
                ROUND(SUM(summary_daily.revenue * (CASE WHEN UPPER(countries.country) = \'OMAN\' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END)), 0) as revenue_usd,
                SUM(summary_daily.mo_reg) as mo_reg,
                SUM(summary_daily.mo_unreg) as mo_unreg,
                SUM(summary_daily.mt_success) as mt_success
            ')
            ->first();

        $revenue = $row->revenue_usd ?? 0;
        $mo = ($row->mo_reg ?? 0) + ($row->mo_unreg ?? 0);
        $mt = $row->mt_success ?? 0;
        $latestHour = SubActiveUserHour::where('date', '2026-02-07')->max('hour');
        $active_users = 0;
        if ($latestHour !== null) {
            $active_users = SubActiveUserHour::where('date', '2026-02-07')
                ->where('hour', $latestHour)
                ->whereIn('id_operator', $operatorIds)
                ->sum('total_sub');
        }

        // 3. Analytics Charts (Top Revenue USD)
        // Per Country
        $top_countries = SummaryDaily::where('summary_daily.date', '2026-02-07')
            ->whereIn('summary_daily.id_operator', $operatorIds)
            ->join('countries', 'summary_daily.id_country', '=', 'countries.id')
            ->selectRaw("countries.country, ROUND(SUM(summary_daily.revenue * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END)), 0) as total_revenue_usd")
            ->groupBy('countries.country')
            ->orderByDesc('total_revenue_usd')
            ->take(5)
            ->get();

        // dd($top_countries); 

        // Per Operator
        $top_operators = SummaryDaily::where('summary_daily.date', '2026-02-07')
            ->whereIn('summary_daily.id_operator', $operatorIds)
            ->join('operators', 'summary_daily.id_operator', '=', 'operators.id')
            ->join('countries', 'summary_daily.id_country', '=', 'countries.id')
            ->selectRaw("operators.operator, ROUND(SUM(summary_daily.revenue * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END)), 0) as total_revenue_usd")
            ->groupBy('operators.operator')
            ->orderByDesc('total_revenue_usd')
            ->take(5)
            ->get();

        // 4. Line Chart: Hourly Revenue per Country (USD)
        // Use hour-1, if hour is 0 use 23
        $currentHour = (int) date('H');
        $maxHour = $currentHour === 0 ? 23 : $currentHour - 1;

        $hourlyCountryRaw = SummaryDaily::where('summary_daily.date', '2026-02-07')
            ->whereIn('summary_daily.id_operator', $operatorIds)
            ->where('summary_daily.hour', '<=', $maxHour)
            ->join('countries', 'summary_daily.id_country', '=', 'countries.id')
            ->selectRaw("summary_daily.hour, countries.country, ROUND(SUM(summary_daily.revenue * (CASE WHEN UPPER(countries.country) = 'OMAN' THEN countries.convert_usd / 1000 ELSE countries.convert_usd END)), 0) as revenue_usd")
            ->groupBy('summary_daily.hour', 'countries.country')
            ->orderBy('summary_daily.hour')
            ->get();

        // Pivot: [{ hour: 0, "Indonesia": 100, "Malaysia": 50 }, ...]
        $countryNames = $hourlyCountryRaw->pluck('country')->unique()->values();
        $hourlyChart = [];
        for ($h = 0; $h <= $maxHour; $h++) {
            $row = ['hour' => $h];
            foreach ($countryNames as $name) {
                $row[$name] = 0;
            }
            $hourlyChart[$h] = $row;
        }
        foreach ($hourlyCountryRaw as $item) {
            $hourlyChart[$item->hour][$item->country] = round((float) $item->revenue_usd, 0);
        }
        $hourlyChart = array_values($hourlyChart);

        return Inertia::render('Dashboard', [
            'stats' => [
                'revenue' => $revenue,
                'mo' => $mo,
                'mt' => $mt,
                'active_users' => $active_users,
                'date' => '2026-02-07',
                'latest_hour' => $latestHour
            ],
            'counts' => $counts,
            'charts' => [
                'top_countries' => $top_countries,
                'top_operators' => $top_operators,
                'hourly_country' => $hourlyChart,
                'country_names' => $countryNames,
            ]
        ]);
    }
}
