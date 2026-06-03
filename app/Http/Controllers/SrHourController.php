<?php

namespace App\Http\Controllers;

use App\Models\SrHour;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SrHourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SrHour::query()->with(['operator', 'service']);

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('date', [$request->startDate, $request->endDate]);
        }

        if ($request->filled('country')) {
             // SrHour has 'country' column (string), same as TransactionHour?
             // Let's check migration/model. The exported file SrHourExport checks $row->country.
             // Migration likely has string column 'country'.
            $country = \App\Models\Country::find($request->country);
            if ($country) {
                $query->where('country', $country->country);
            }
        }
        if ($request->filled('operator')) {
            $query->where('id_operator', $request->operator);
        }
        if ($request->filled('service')) {
            $query->where('id_service', $request->service);
        }

        $user = auth()->user();
        if (!$user->hasRole(['Super Admin', 'Admin'])) {
            $operatorIds = $user->operators()->pluck('operators.id');
            $query->whereIn('id_operator', $operatorIds);
        }

        $data = $query->latest('date')->orderBy('hour')->paginate(10)->withQueryString();

        return Inertia::render('SrHour/Index', [
            'data' => $data,
            'filters' => $request->only(['startDate', 'endDate', 'country', 'operator', 'service']),
            'countries' => \App\Models\Country::all(['id', 'country']),
            'operators' => \App\Models\Operator::all(['id', 'operator']),
            'services' => \App\Models\Service::all(['id', 'service']),
        ]);
    }

    public function export(Request $request) 
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SrHourExport(
                $request->startDate, 
                $request->endDate,
                $request->country,
                $request->operator,
                $request->service
            ), 
            'sr_hours.xlsx'
        );
    }
    // ... Stubs ...
}
