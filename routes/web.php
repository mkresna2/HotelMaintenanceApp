<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\MaintenanceScheduleController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes are provided by Laravel Breeze/UI

// Protected routes
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Complaints
    Route::resource('complaints', ComplaintController::class);
    Route::post('complaints/{complaint}/followup', [ComplaintController::class, 'addFollowUp'])->name('complaints.followup');
    
    // Work Orders
    Route::resource('work-orders', WorkOrderController::class);
    Route::post('work-orders/{workOrder}/assign', [WorkOrderController::class, 'assign'])->name('work-orders.assign');
    Route::post('work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])->name('work-orders.status');
    Route::post('work-orders/{workOrder}/log', [WorkOrderController::class, 'addLog'])->name('work-orders.log');
    
    // Assets
    Route::resource('assets', AssetController::class);
    
    // Maintenance Schedules
    Route::resource('schedules', MaintenanceScheduleController::class);
    
    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
});
