<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dealers\DealerController;
use App\Http\Controllers\Dealers\DealerUploadController;
use App\Http\Controllers\Dealers\SchemeAmountController;

Route::middleware(['auth'])->group(function () {

    // ==========================================
    // VIEW ROUTES (Page Loads)
    // ==========================================

    // Dealers
    Route::get('/dealers', [DealerController::class, 'index'])->name('dealers.index');

    // Dealer Uploads
    Route::get('/dealers/upload', [DealerUploadController::class, 'index'])->name('dealers.upload.index');

    // Scheme Amounts
    Route::get('/dealers/scheme-amounts', [SchemeAmountController::class, 'index'])->name('dealers.scheme_amounts.index');


    // ==========================================
    // AJAX / API ROUTES (Data & Actions)
    // ==========================================

    // Dealers
    Route::get('/dealers/export', [DealerController::class, 'export'])->name('dealers.export');
    Route::post('/dealers/list', [DealerController::class, 'list'])->name('dealers.list');
    Route::post('/dealers', [DealerController::class, 'store'])->name('dealers.store');
    Route::get('/dealers/{encrypted_id}/show', [DealerController::class, 'show'])->name('dealers.show');
    Route::get('/dealers/{encrypted_id}/edit', [DealerController::class, 'edit'])->name('dealers.edit');
    Route::post('/dealers/{encrypted_id}/update', [DealerController::class, 'update'])->name('dealers.update');

    // Dealer Uploads
    Route::post('/dealers/upload/list', [DealerUploadController::class, 'list'])->name('dealers.upload.list');
    Route::post('/dealers/upload/import', [DealerUploadController::class, 'import'])->name('dealers.upload.import');
    Route::get('/dealers/upload/template', [DealerUploadController::class, 'downloadTemplate'])->name('dealers.upload.template'); // Download is an action

    // Scheme Amounts
    Route::post('/dealers/scheme-amounts/list', [SchemeAmountController::class, 'list'])->name('dealers.scheme_amounts.list');
    Route::post('/dealers/scheme-amounts/store', [SchemeAmountController::class, 'store'])->name('dealers.scheme_amounts.store');
    Route::post('/dealers/scheme-amounts/delete', [SchemeAmountController::class, 'delete'])->name('dealers.scheme_amounts.delete');
    Route::post('/dealers/scheme-amounts/get-monthly-quantity', [SchemeAmountController::class, 'getMonthlyQuantity'])->name('dealers.scheme_amounts.get_monthly_quantity');
    Route::get('/dealers/scheme-amounts/export-excel', [SchemeAmountController::class, 'exportExcel'])->name('dealers.scheme_amounts.export_excel');
    Route::get('/dealers/scheme-amounts/export-pdf', [SchemeAmountController::class, 'exportPdf'])->name('dealers.scheme_amounts.export_pdf');

});
