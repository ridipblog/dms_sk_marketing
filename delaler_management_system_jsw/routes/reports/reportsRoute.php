<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reports\DealerPaymentReportController;
use App\Http\Controllers\Reports\DealerSalesReportController;
use App\Http\Controllers\Reports\DailyStockReportController;

Route::middleware(['auth'])->prefix('reports')->group(function () {

    // Dealer Wise Payment Report
    Route::get('/dealer-wise-payments', [DealerPaymentReportController::class, 'index'])->name('reports.dealer_payments.index');
    Route::post('/dealer-wise-payments/list', [DealerPaymentReportController::class, 'list'])->name('reports.dealer_payments.list');
    Route::get('/dealer-wise-payments/export', [DealerPaymentReportController::class, 'export'])->name('reports.dealer_payments.export');

    // Dealer Wise Sales Report
    Route::get('/dealer-wise-sales', [DealerSalesReportController::class, 'index'])->name('reports.dealer_sales.index');
    Route::post('/dealer-wise-sales/list', [DealerSalesReportController::class, 'list'])->name('reports.dealer_sales.list');
    Route::get('/dealer-wise-sales/export', [DealerSalesReportController::class, 'export'])->name('reports.dealer_sales.export');

    // Daily Stock Report
    Route::get('/daily-stock', [DailyStockReportController::class, 'index'])->name('reports.daily_stock.index');
    Route::post('/daily-stock/list', [DailyStockReportController::class, 'list'])->name('reports.daily_stock.list');
    Route::get('/daily-stock/export', [DailyStockReportController::class, 'export'])->name('reports.daily_stock.export');

});
