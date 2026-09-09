<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Purchase\SupplierController;
use App\Http\Controllers\Purchase\PurchaseInvoiceController;
use App\Http\Controllers\Inventory\StockController;

Route::middleware(['auth'])->group(function () {

    // Suppliers CRUD Routes
    Route::get('/purchase/suppliers', [SupplierController::class, 'index'])->name('purchase.suppliers.index');
    Route::post('/purchase/suppliers/list', [SupplierController::class, 'list'])->name('purchase.suppliers.list');
    Route::post('/purchase/suppliers/store', [SupplierController::class, 'store'])->name('purchase.suppliers.store');
    Route::get('/purchase/suppliers/{encrypted_id}/edit', [SupplierController::class, 'edit'])->name('purchase.suppliers.edit');
    Route::post('/purchase/suppliers/{encrypted_id}/update', [SupplierController::class, 'update'])->name('purchase.suppliers.update');

    // Purchase Invoice Views & Pages
    Route::get('/purchase/invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase.invoices.index');
    Route::get('/purchase/invoices/export', [PurchaseInvoiceController::class, 'export'])->name('purchase.invoices.export');
    Route::get('/purchase/invoices/generate/{purchase_id?}', [PurchaseInvoiceController::class, 'generate'])->name('purchase.invoices.generate');
    Route::get('/purchase/invoices/view/{id}', [PurchaseInvoiceController::class, 'view'])->name('purchase.invoices.view');

    // Purchase Invoice Action & Ajax APIs
    Route::post('/purchase/invoices/list', [PurchaseInvoiceController::class, 'list'])->name('purchase.invoices.list');
    Route::post('/purchase/invoices/store', [PurchaseInvoiceController::class, 'store'])->name('purchase.invoices.store');
    Route::post('/purchase/invoices/fetch-items', [PurchaseInvoiceController::class, 'fetchPurchaseItems'])->name('purchase.invoices.fetch_items');
    Route::post('/purchase/invoices/store-item', [PurchaseInvoiceController::class, 'storePurchaseItem'])->name('purchase.invoices.store_item');
    Route::post('/purchase/invoices/delete-item', [PurchaseInvoiceController::class, 'deletePurchaseItem'])->name('purchase.invoices.delete_item');
    Route::post('/purchase/invoices/finalize', [PurchaseInvoiceController::class, 'finalizePurchase'])->name('purchase.invoices.finalize');
    Route::post('/purchase/invoices/payment', [PurchaseInvoiceController::class, 'storePayment'])->name('purchase.invoices.payment');

    // Purchase Invoice Excel Upload Submodule Routes
    Route::get('/purchase/invoices/upload', [\App\Http\Controllers\Purchase\PurchaseInvoiceUploadController::class, 'index'])->name('purchase.invoices.upload.index');
    Route::post('/purchase/invoices/upload/list', [\App\Http\Controllers\Purchase\PurchaseInvoiceUploadController::class, 'list'])->name('purchase.invoices.upload.list');
    Route::post('/purchase/invoices/upload/import', [\App\Http\Controllers\Purchase\PurchaseInvoiceUploadController::class, 'import'])->name('purchase.invoices.upload.import');
    Route::get('/purchase/invoices/upload/template', [\App\Http\Controllers\Purchase\PurchaseInvoiceUploadController::class, 'downloadTemplate'])->name('purchase.invoices.upload.template');

    // Purchase Invoice Payment Upload Submodule Routes
    Route::get('/purchase/invoices/payment-upload', [\App\Http\Controllers\Purchase\PurchaseInvoiceUploadController::class, 'paymentUploadIndex'])->name('purchase.invoices.payment_upload.index');
    Route::post('/purchase/invoices/payment-upload/list', [\App\Http\Controllers\Purchase\PurchaseInvoiceUploadController::class, 'paymentUploadList'])->name('purchase.invoices.payment_upload.list');
    Route::post('/purchase/invoices/payment-upload/import', [\App\Http\Controllers\Purchase\PurchaseInvoiceUploadController::class, 'paymentUploadImport'])->name('purchase.invoices.payment_upload.import');
    Route::get('/purchase/invoices/payment-upload/template', [\App\Http\Controllers\Purchase\PurchaseInvoiceUploadController::class, 'paymentUploadTemplate'])->name('purchase.invoices.payment_upload.template');

    // Stock Ledger Routing
    Route::get('/stocks', [StockController::class, 'index'])->name('inventory.stocks.index');
    Route::post('/stocks/adjust', [StockController::class, 'adjust'])->name('inventory.stocks.adjust');
});
