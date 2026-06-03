<?php

namespace App\Http\Controllers;

use App\Models\MoHour;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MoHourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MoHour::query()->with(['operator', 'service']);

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('date', [$request->startDate, $request->endDate]);
        }
        
        if ($request->filled('operator')) {
            $query->where('id_operator', $request->operator);
        }
        if ($request->filled('service')) {
            $query->where('id_service', $request->service);
        }

        $data = $query->latest('date')->orderBy('hour')->simplePaginate(10)->withQueryString();

        return Inertia::render('MoHour/Index', [
            'data' => $data,
            'filters' => $request->only(['startDate', 'endDate', 'operator', 'service']),
            'countries' => \App\Models\Country::all(['id', 'country']),
            'operators' => \App\Models\Operator::all(['id', 'operator']),
            'services' => \App\Models\Service::all(['id', 'service']),
        ]);
    }

    public function export(Request $request) 
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MoHourExport(
                $request->startDate, 
                $request->endDate,
                $request->operator,
                $request->service
            ), 
            'mo_hours.xlsx'
        );
    }
    // ... Stubs ...
}
