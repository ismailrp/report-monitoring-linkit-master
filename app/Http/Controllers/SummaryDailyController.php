<?php

namespace App\Http\Controllers;

use App\Models\SummaryDaily;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SummaryDailyController extends Controller
{
    public function index(Request $request)
    {
        $query = SummaryDaily::query()->with(['country', 'operator', 'service']);

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('date', [$request->startDate, $request->endDate]);
        }
        
        if ($request->filled('country')) {
            $query->where('id_country', $request->country);
        }
        if ($request->filled('operator')) {
            $query->where('id_operator', $request->operator);
        }
        if ($request->filled('service')) {
            $query->where('id_service', $request->service);
        }

        // Apply User Scope: If not admin/superuser, restrict to assigned operators
        // Assuming 'Super Admin' or 'Admin' role exists. Adjust validation as needed.
        $user = auth()->user();
        // dd($user);
        // Apply User Scope: If not admin/superuser, restrict to assigned operators
        $user = auth()->user();
        if (!$user->hasRole(['super_admin', 'admin'])) {
            $operatorIds = $user->operators()->pluck('operators.id');
            $query->whereIn('id_operator', $operatorIds);
        }

        $data = $query->orderByDesc('date')->paginate(10)->withQueryString();

        // Dependent Filters
        $operatorsQuery = \App\Models\Operator::query();
        if ($request->filled('country')) {
            $operatorsQuery->where('id_country', $request->country);
        }

        $servicesQuery = \App\Models\Service::query();
        if ($request->filled('operator')) {
            $servicesQuery->where('id_operator', $request->operator);
        }

        return Inertia::render('SummaryDaily/Index', [
            'data' => $data,
            'filters' => $request->only(['startDate', 'endDate', 'country', 'operator', 'service']),
            'countries' => \App\Models\Country::all(['id', 'country']),
            'operators' => $operatorsQuery->get(['id', 'operator']),
            'services' => $servicesQuery->get(['id', 'service']),
        ]);
    }

    public function export(Request $request) 
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SummaryDailyExport(
                $request->startDate, 
                $request->endDate,
                $request->country,
                $request->operator,
                $request->service
            ), 
            'summary_daily.xlsx'
        );
    }
}
