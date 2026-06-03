<?php

namespace App\Http\Controllers;

use App\Models\SubActiveUserHour;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubActiveUserHourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SubActiveUserHour::query()->with(['operator', 'service']);

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('date', [$request->startDate, $request->endDate]);
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

        return Inertia::render('SubActiveUserHour/Index', [
            'data' => $data,
            'filters' => $request->only(['startDate', 'endDate', 'operator', 'service']),
            'operators' => \App\Models\Operator::all(['id', 'operator']),
            'services' => \App\Models\Service::all(['id', 'service']),
        ]);
    }

    public function export(Request $request) 
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SubActiveUserHourExport(
                $request->startDate, 
                $request->endDate,
                $request->operator,
                $request->service
            ), 
            'sub_active_user_hours.xlsx'
        );
    }
    // ... Stubs ...
}
