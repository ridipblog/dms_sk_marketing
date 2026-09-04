<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Settings\FinanceController;

Route::middleware(['auth'])->group(function () {
    Route::get('/settings/password', [SettingsController::class, 'password'])->name('settings.password');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');

    Route::get('/settings/finance', [FinanceController::class, 'index'])->name('settings.finance.index');
    Route::post('/settings/finance/store', [FinanceController::class, 'store'])->name('settings.finance.store');
    Route::post('/settings/finance/update', [FinanceController::class, 'update'])->name('settings.finance.update');
    Route::post('/settings/finance/set-active', [FinanceController::class, 'setActive'])->name('settings.finance.set_active');
});
