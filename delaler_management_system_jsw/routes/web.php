<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

require __DIR__.'/auth/authRoute.php';
require __DIR__.'/dashboard/dashboardRoute.php';
require __DIR__.'/dealers/dealerRoute.php';
require __DIR__.'/inventory/inventoryRoutes.php';
require __DIR__.'/accounts/accountsRoute.php';
require __DIR__.'/settings/settingsRoute.php';
require __DIR__.'/users/userRoutes.php';
require __DIR__.'/purchase/purchaseRoute.php';
require __DIR__.'/reports/reportsRoute.php';
