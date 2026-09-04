<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Accounts\InvoiceController;
use App\Http\Controllers\Accounts\PaymentTrackController;
use App\Http\Controllers\Accounts\DebitNoteController;
use App\Http\Controllers\Accounts\CreditNoteController;
use App\Http\Controllers\Accounts\CashDiscountSlabController;
use App\Http\Controllers\Accounts\OrderTrackingController;
use App\Http\Controllers\Accounts\DealerStatementController;
use App\Http\Controllers\Accounts\AccountsUploadController;

// Public Invoice View Route (accessible via QR Code scan)
Route::get('/accounts/invoices/view/{id?}', [InvoiceController::class, 'view'])->name('accounts.invoices.view');

Route::middleware(['auth'])->prefix('accounts')->group(function () {

    // ==========================================
    // VIEW ROUTES (Page Loads)
    // ==========================================

    // Order Tracking
    Route::get('/order-tracking', [OrderTrackingController::class, 'index'])->name('accounts.order_tracking.index');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('accounts.invoices.index');
    Route::get('/invoices/export', [InvoiceController::class, 'export'])->name('accounts.invoices.export');
    Route::get('/invoices/generate/{invoice_id?}', [InvoiceController::class, 'generate'])->name('accounts.invoices.generate');

    // Upload Excel
    Route::get('/upload-excel', [AccountsUploadController::class, 'index'])->name('accounts.upload.index');
    Route::post('/upload-excel/invoices/list', [AccountsUploadController::class, 'invoicesList'])->name('accounts.upload.invoices.list');
    Route::post('/upload-excel/vouchers/list', [AccountsUploadController::class, 'vouchersList'])->name('accounts.upload.vouchers.list');
    Route::post('/upload-excel/invoices/import', [AccountsUploadController::class, 'importInvoices'])->name('accounts.upload.invoices.import');
    Route::post('/upload-excel/vouchers/import', [AccountsUploadController::class, 'importVouchers'])->name('accounts.upload.vouchers.import');
    Route::get('/upload-excel/invoices/template', [AccountsUploadController::class, 'downloadInvoiceTemplate'])->name('accounts.upload.invoices.template');
    Route::get('/upload-excel/vouchers/template', [AccountsUploadController::class, 'downloadVoucherTemplate'])->name('accounts.upload.vouchers.template');
    Route::get('/upload-excel/errors/download/{track_id}', [AccountsUploadController::class, 'downloadErrorReport'])->name('accounts.upload.errors.download');

    // Payment Tracks
    Route::get('/payment-tracks', [PaymentTrackController::class, 'index'])->name('accounts.payment_tracks.index');
    Route::get('/payment-tracks/voucher/{id}', [PaymentTrackController::class, 'voucher'])->name('accounts.payment_tracks.voucher');

    // Dealer Statement & Ledger
    Route::match(['get', 'post'], '/payment-tracks/ledger/{invoice_id?}', [DealerStatementController::class, 'ledger'])->name('accounts.payment_tracks.ledger');
    Route::get('/dealer-statement', [DealerStatementController::class, 'dealerStatementIndex'])->name('accounts.dealer_statement.index');

    // Invoice Ageing Report
    Route::get('/ageing-report', [\App\Http\Controllers\Accounts\AgeingReportController::class, 'index'])->name('accounts.ageing_report.index');
    Route::post('/ageing-report/list', [\App\Http\Controllers\Accounts\AgeingReportController::class, 'list'])->name('accounts.ageing_report.list');
    Route::get('/ageing-report/export', [\App\Http\Controllers\Accounts\AgeingReportController::class, 'export'])->name('accounts.ageing_report.export');

    // Debit Notes
    Route::get('/debit-notes', [DebitNoteController::class, 'index'])->name('accounts.debit_notes.index');

    // Credit Notes
    Route::get('/credit-notes', [CreditNoteController::class, 'index'])->name('accounts.credit_notes.index');

    // Cash Discount Slabs
    Route::get('/cash-discount-slabs', [CashDiscountSlabController::class, 'index'])->name('accounts.cash_discount_slabs.index');


    // ==========================================
    // AJAX / API ROUTES (Data & Actions)
    // ==========================================

    // Order Tracking
    Route::get('/order-tracking/get-orders/{dealerCompanyId}', [OrderTrackingController::class, 'getOrders'])->name('accounts.order_tracking.get_orders');
    Route::post('/order-tracking/dealer-pdf', [OrderTrackingController::class, 'generateDealerPdf'])->name('accounts.order_tracking.dealer_pdf');
    Route::post('/order-tracking/order-pdf', [OrderTrackingController::class, 'generateOrderPdf'])->name('accounts.order_tracking.order_pdf');
    Route::get('/order-tracking/order-view', [OrderTrackingController::class, 'orderview'])->name('accounts.order_tracking.orderview'); // Assuming this returns modal HTML or data

    // Invoices
    Route::post('/invoices/list', [InvoiceController::class, 'list'])->name('accounts.invoices.list');
    Route::post('/invoices/store', [InvoiceController::class, 'store'])->name('accounts.invoices.store');
    Route::post('/invoices/fetch-items', [InvoiceController::class, 'fetchInvoiceItems'])->name('accounts.invoices.fetch_items');
    Route::post('/invoices/store-item', [InvoiceController::class, 'storeInvoiceItem'])->name('accounts.invoices.store_item');
    Route::post('/invoices/delete-item', [InvoiceController::class, 'deleteInvoiceItem'])->name('accounts.invoices.delete_item');
    Route::post('/invoices/finalize', [InvoiceController::class, 'finalizeInvoice'])->name('accounts.invoices.finalize');
    Route::post('/invoices/check-delete', [InvoiceController::class, 'checkDelete'])->name('accounts.invoices.check_delete');
    Route::post('/invoices/delete', [InvoiceController::class, 'destroy'])->name('accounts.invoices.delete');

    // Payment Tracks
    Route::post('/payment-tracks/list', [PaymentTrackController::class, 'list'])->name('accounts.payment_tracks.list');
    Route::post('/payment-tracks/voucher/list', [PaymentTrackController::class, 'voucherList'])->name('accounts.payment_tracks.voucher_list');
    Route::post('/payment-tracks/voucher/store', [PaymentTrackController::class, 'storeVoucher'])->name('accounts.payment_tracks.voucher_store');
    Route::get('/payment-tracks/voucher/edit/{id}', [PaymentTrackController::class, 'editVoucher'])->name('accounts.payment_tracks.voucher_edit');
    Route::post('/payment-tracks/voucher/update/{id}', [PaymentTrackController::class, 'updateVoucher'])->name('accounts.payment_tracks.voucher_update');
    Route::post('/payment-tracks/voucher/delete/{id}', [PaymentTrackController::class, 'deleteVoucher'])->name('accounts.payment_tracks.voucher_delete');
    Route::get('/payment-tracks/voucher/export-excel/{id}', [PaymentTrackController::class, 'exportVouchersExcel'])->name('accounts.payment_tracks.voucher_export_excel');
    Route::get('/payment-tracks/voucher/export-pdf/{id}', [PaymentTrackController::class, 'exportVouchersPdf'])->name('accounts.payment_tracks.voucher_export_pdf');

    // Debit Notes
    Route::post('/debit-notes/list', [DebitNoteController::class, 'list'])->name('accounts.debit_notes.list');

    // Credit Notes
    Route::post('/credit-notes/list', [CreditNoteController::class, 'list'])->name('accounts.credit_notes.list');

    // Cash Discount Slabs
    Route::post('/cash-discount-slabs/list', [CashDiscountSlabController::class, 'list'])->name('accounts.cash_discount_slabs.list');
});
