<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OperatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Operator::query()->with('country');

        if ($request->has('search')) {
            $query->where('operator', 'like', '%' . $request->search . '%');
        }

        $operators = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();

        return Inertia::render('Operators/Index', [
            'operators' => $operators,
            'filters' => $request->only(['search'])
        ]);
    }

    public function export(Request $request) 
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\OperatorExport, 'operators.xlsx');
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
    public function show(Operator $operator)
    {
        $operator->load('country');
        return Inertia::render('Operators/Show', [
            'operator' => $operator
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Operator $operator)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Operator $operator)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Operator $operator)
    {
        //
    }
}
