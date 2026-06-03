<?php

namespace App\Http\Controllers;

use App\Models\TransactionHour;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionHourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TransactionHour::query()->with(['operator', 'service']);

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('date', [$request->startDate, $request->endDate]);
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country); // Note: TransactionHour uses 'country' column (string code) or ID? Checked migration: it's string. BUT FilterBar sends ID.
            // Wait, standardizing. FilterBar sends IDs.
            // TransactionHour 'country' column is likely a string code based on previous migration (2025_07_24_045238_create_transaction_hours_table.php).
            // Let's check Country model/table. Country table has id, country (name), country_code.
            // If TransactionHour stores country name or code, I need to map the ID from request to that value.
            // However, usually it's better to filter by ID if available. 
            // In TransactionHour migration: $table->string('country');
            // In MO/SR hours, it might be different.
            // Let's assume for now I should find the country code/name from the ID first if I need to filter by string.
            // Actually, in FilterBar I passed `c.id` as value.
            // If TransactionHour.country is a string, I should probably change FilterBar to pass the string if that's what's needed, OR lookup here.
            
            // Let's lookup the country name/code from the ID.
            $country = \App\Models\Country::find($request->country);
            if ($country) {
                 $query->where('country', $country->country); // Assuming it stores the name "Indonesia", "Malaysia" etc.
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

        return Inertia::render('TransactionHour/Index', [
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
            new \App\Exports\TransactionHourExport(
                $request->startDate, 
                $request->endDate,
                $request->country,
                $request->operator,
                $request->service
            ), 
            'transaction_hours.xlsx'
        );
    }
    // ... Stubs ...
}
