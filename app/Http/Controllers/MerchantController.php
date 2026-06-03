<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MerchantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Merchant::query();

        if ($request->has('search')) {
            $query->where('merchant_name', 'like', '%' . $request->search . '%')
                  ->orWhere('merchant_code', 'like', '%' . $request->search . '%');
        }

        $merchants = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Merchants/Index', [
            'merchants' => $merchants,
            'filters' => $request->only(['search'])
        ]);
    }

    public function export(Request $request) 
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\MerchantExport, 'merchants.xlsx');
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
    public function show(Merchant $merchant)
    {
        $merchant->load('country');
        return Inertia::render('Merchants/Show', [
            'merchant' => $merchant
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Merchant $merchant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Merchant $merchant)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Merchant $merchant)
    {
        //
    }
}
