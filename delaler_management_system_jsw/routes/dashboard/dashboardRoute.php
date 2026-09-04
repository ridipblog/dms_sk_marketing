<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/data', [DashboardController::class, 'fetchData'])->name('dashboard.data');
    Route::post('/dashboard/filter-options', [DashboardController::class, 'getFilterOptions'])->name('dashboard.filter-options');
    Route::get('/restricted', [DashboardController::class, 'restricted'])->name('restricted');
});
