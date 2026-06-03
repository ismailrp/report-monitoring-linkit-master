<?php

use App\Http\Controllers\API\SRController;  
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SummaryDailyController;
use App\Http\Controllers\SummaryWeeklyController;
use App\Http\Controllers\MoHourController;
use App\Http\Controllers\SrHourController;
use App\Http\Controllers\SubActiveUserHourController;
use App\Http\Controllers\TransactionHourController;

Route::get('/', function () {
    // return view('welcome');
    return redirect()->route('filament.admin.auth.login');
});

Route::get('/users-inertia', [UserController::class, 'index'])->name('users.inertia.index');

Route::prefix('v2')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('v2.dashboard')->middleware('permission:view_dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('v2.users.index')->middleware('permission:view_any_user');
    Route::get('/companies', [CompanyController::class, 'index'])->name('v2.companies.index')->middleware('permission:view_any_company');
    Route::get('/countries/export', [CountryController::class, 'export'])->name('v2.countries.export')->middleware('permission:view_any_country');
    Route::get('/countries', [CountryController::class, 'index'])->name('v2.countries.index')->middleware('permission:view_any_country');
    Route::get('/countries/{country}', [CountryController::class, 'show'])->name('v2.countries.show')->middleware('permission:view_any_country');
    
    Route::get('/alerts', [AlertController::class, 'index'])->name('v2.alerts.index')->middleware('permission:view_any_alert');
    
    Route::get('/merchants/export', [MerchantController::class, 'export'])->name('v2.merchants.export')->middleware('permission:view_any_merchant');
    Route::get('/merchants', [MerchantController::class, 'index'])->name('v2.merchants.index')->middleware('permission:view_any_merchant');
    Route::get('/merchants/{merchant}', [MerchantController::class, 'show'])->name('v2.merchants.show')->middleware('permission:view_any_merchant');
    
    Route::get('/operators/export', [OperatorController::class, 'export'])->name('v2.operators.export')->middleware('permission:view_any_operator');
    Route::get('/operators', [OperatorController::class, 'index'])->name('v2.operators.index')->middleware('permission:view_any_operator');
    Route::get('/operators/{operator}', [OperatorController::class, 'show'])->name('v2.operators.show')->middleware('permission:view_any_operator');
    
    Route::get('/services/export', [ServiceController::class, 'export'])->name('v2.services.export')->middleware('permission:view_any_service');
    Route::get('/services', [ServiceController::class, 'index'])->name('v2.services.index')->middleware('permission:view_any_service');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('v2.services.show')->middleware('permission:view_any_service');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('v2.services.update')->middleware('permission:view_any_service');

    // Users
    Route::get('/users/{user}', [UserController::class, 'show'])->name('v2.users.show')->middleware('permission:view_any_user');

    // Roles & Permissions
    Route::get('/roles-permissions', [\App\Http\Controllers\RolePermissionController::class, 'index'])->name('v2.roles.index')->middleware('permission:view_any_role');
    Route::post('/roles', [\App\Http\Controllers\RolePermissionController::class, 'storeRole'])->name('v2.roles.store')->middleware('permission:view_any_role');
    Route::put('/roles/{role}', [\App\Http\Controllers\RolePermissionController::class, 'updateRole'])->name('v2.roles.update')->middleware('permission:view_any_role');
    Route::delete('/roles/{role}', [\App\Http\Controllers\RolePermissionController::class, 'deleteRole'])->name('v2.roles.destroy')->middleware('permission:view_any_role');
    
    Route::get('/summary-daily/export', [SummaryDailyController::class, 'export'])->name('v2.summary-daily.export')->middleware('permission:view_any_summary_daily');
    Route::get('/summary-daily', [SummaryDailyController::class, 'index'])->name('v2.summary-daily.index')->middleware('permission:view_any_summary_daily');
    Route::get('/summary-weekly/export', [SummaryWeeklyController::class, 'export'])->name('v2.summary-weekly.export')->middleware('permission:view_any_summary_weekly');
    Route::get('/summary-weekly', [SummaryWeeklyController::class, 'index'])->name('v2.summary-weekly.index')->middleware('permission:view_any_summary_weekly');
    Route::get('/mo-hours/export', [MoHourController::class, 'export'])->name('v2.mo-hours.export')->middleware('permission:view_any_mo_hour');
    Route::get('/mo-hours', [MoHourController::class, 'index'])->name('v2.mo-hours.index')->middleware('permission:view_any_mo_hour');
    Route::get('/sr-hours/export', [SrHourController::class, 'export'])->name('v2.sr-hours.export')->middleware('permission:view_any_sr_hour');
    Route::get('/sr-hours', [SrHourController::class, 'index'])->name('v2.sr-hours.index')->middleware('permission:view_any_sr_hour');
    Route::get('/sub-active-user-hours/export', [SubActiveUserHourController::class, 'export'])->name('v2.sub-active-user-hours.export')->middleware('permission:view_any_sub_active_user');
    Route::get('/sub-active-user-hours', [SubActiveUserHourController::class, 'index'])->name('v2.sub-active-user-hours.index')->middleware('permission:view_any_sub_active_user');
    Route::get('/transaction-hours/export', [TransactionHourController::class, 'export'])->name('v2.transaction-hours.export')->middleware('permission:view_any_transaction_hour');
    Route::get('/transaction-hours', [TransactionHourController::class, 'index'])->name('v2.transaction-hours.index')->middleware('permission:view_any_transaction_hour');
    
    // Profile & Logout (no permission needed - every user can access their profile)
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('v2.profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('v2.profile.update');
    Route::post('/logout', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('v2.logout');
});
// Route::redirect('/', '/admin/login');
