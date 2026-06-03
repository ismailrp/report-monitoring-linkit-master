<?php

namespace App\Http\Controllers;

use App\Models\SummaryWeekly;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SummaryWeeklyController extends Controller
{
    public function index(Request $request)
    {
        $query = SummaryWeekly::query()->with(['country', 'operator']);

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('start_date', [$request->startDate, $request->endDate]);
        }

        if ($request->filled('country')) {
            $query->where('id_country', $request->country);
        }
        if ($request->filled('operator')) {
            $query->where('id_operator', $request->operator);
        }

        // Apply User Scope
        $user = auth()->user();
        if (!$user->hasRole(['super_admin', 'admin'])) {
            $operatorIds = $user->operators()->pluck('operators.id');
            $query->whereIn('id_operator', $operatorIds);
        }

        $data = $query->orderByDesc('start_date')->paginate(10)->withQueryString();

        // Dependent Filters
        $operatorsQuery = \App\Models\Operator::query();
        if ($request->filled('country')) {
            $operatorsQuery->where('id_country', $request->country);
        }

        $periods = SummaryWeekly::query()
            ->select('start_date', 'end_date')
            ->distinct()
            ->orderByDesc('start_date')
            ->get()
            ->map(function ($item) {
                $start = $item->start_date ? \Carbon\Carbon::parse($item->start_date)->format('Y-m-d') : '';
                $end = $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('Y-m-d') : '';
                return [
                    'start_date' => $start,
                    'end_date' => $end,
                    'label' => "$start to $end",
                ];
            });

        return Inertia::render('SummaryWeekly/Index', [
            'data' => $data,
            'filters' => $request->only(['startDate', 'endDate', 'country', 'operator']),
            'countries' => \App\Models\Country::all(['id', 'country']),
            'operators' => $operatorsQuery->get(['id', 'operator']),
            'periods' => $periods,
        ]);
    }

    public function export(Request $request) 
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SummaryWeeklyExport(
                $request->startDate, 
                $request->endDate,
                $request->country,
                $request->operator
            ), 
            'summary_weekly.xlsx'
        );
    }
}
