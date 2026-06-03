<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Service::query()->with(['Operator', 'merchant']);

        if ($request->has('search')) {
            $query->where('service', 'like', '%' . $request->search . '%')
                  ->orWhere('sdc', 'like', '%' . $request->search . '%');
        }

        $services = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();


        // dd($services);
        return Inertia::render('Services/Index', [
            'services' => $services,
            'count_services' => Service::count(),
            'filters' => $request->only(['search']),
            'merchants' => Merchant::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request) 
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ServiceExport, 'services.xlsx');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        $service->load('Operator');
        return Inertia::render('Services/Show', [
            'service' => $service
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        $request->validate([
            'id_merchant' => 'nullable|exists:merchants,id',
        ]);

        $service->update([
            'id_merchant' => $request->id_merchant,
        ]);

        return redirect()->back()->with('success', 'Merchant updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        //
    }
}
