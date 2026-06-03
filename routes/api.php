<?php

use App\Http\Controllers\API\SRController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/success-ratio', [SRController::class,'store']);
