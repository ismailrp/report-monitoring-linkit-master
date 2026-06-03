<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlertController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $alerts = Alert::latest()->paginate(10);
        return Inertia::render('Alerts/Index', [
            'alerts' => $alerts
        ]);
    }
    // ... Stubs ...
}
