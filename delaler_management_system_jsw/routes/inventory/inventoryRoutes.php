<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\ProductPricingController;
use App\Http\Controllers\Inventory\InventoryUploadController;

Route::middleware(['auth'])->group(function () {

    // ==========================================
    // VIEW ROUTES (Page Loads)
    // ==========================================

    // Inventory Upload Tracking
    Route::get('/inventory/upload', [InventoryUploadController::class, 'index'])->name('inventory.upload.index');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    // Product Pricing
    Route::get('/product-pricings', [ProductPricingController::class, 'index'])->name('product_pricings.index');

    // ==========================================
    // AJAX / API ROUTES (Data & Actions)
    // ==========================================

    // Inventory Upload Tracking
    Route::post('/inventory/upload/list', [InventoryUploadController::class, 'list'])->name('inventory.upload.list');
    Route::post('/inventory/upload/import', [InventoryUploadController::class, 'import'])->name('inventory.upload.import');
    Route::get('/inventory/upload/template', [InventoryUploadController::class, 'downloadTemplate'])->name('inventory.upload.template'); // Download is an action

    // Categories
    Route::post('/categories/list', [CategoryController::class, 'list'])->name('categories.list');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{encrypted_id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::post('/categories/{encrypted_id}/update', [CategoryController::class, 'update'])->name('categories.update');

    // Products
    Route::post('/products/list', [ProductController::class, 'list'])->name('products.list');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{encrypted_id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::post('/products/{encrypted_id}/update', [ProductController::class, 'update'])->name('products.update');

    // Product Pricing
    Route::post('/product-pricings/list', [ProductPricingController::class, 'list'])->name('product_pricings.list');
    Route::post('/product-pricings', [ProductPricingController::class, 'store'])->name('product_pricings.store');
    Route::get('/product-pricings/{encrypted_id}/edit', [ProductPricingController::class, 'edit'])->name('product_pricings.edit');
    Route::post('/product-pricings/{encrypted_id}/update', [ProductPricingController::class, 'update'])->name('product_pricings.update');
});
