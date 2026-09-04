<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users\UserController;

Route::middleware(['auth'])->prefix('users')->group(function () {

    // ==========================================
    // VIEW ROUTES (Page Loads)
    // ==========================================
    Route::get('/', [UserController::class, 'index'])->name('users.index');
    // Map the create route from sidebar to the index view (since we use a modal)
    Route::get('/create', [UserController::class, 'index'])->name('users.create');

    // User Upload Routes
    Route::get('/uploads', [\App\Http\Controllers\Users\UserUploadController::class, 'index'])->name('users.uploads.index');
    Route::post('/uploads/list', [\App\Http\Controllers\Users\UserUploadController::class, 'list'])->name('users.uploads.list');
    Route::post('/uploads/import', [\App\Http\Controllers\Users\UserUploadController::class, 'import'])->name('users.uploads.import');
    Route::get('/uploads/template', [\App\Http\Controllers\Users\UserUploadController::class, 'downloadTemplate'])->name('users.uploads.template');
    Route::get('/uploads/errors/{id}', [\App\Http\Controllers\Users\UserUploadController::class, 'downloadErrorReport'])->name('users.uploads.errors');

    // ==========================================
    // AJAX / API ROUTES (Data & Actions)
    // ==========================================
    Route::post('/list', [UserController::class, 'list'])->name('users.list');
    Route::post('/store', [UserController::class, 'store'])->name('users.store');
    Route::post('/find-by-phone', [UserController::class, 'findByPhone'])->name('users.find_by_phone');
    Route::get('/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::post('/{id}/update', [UserController::class, 'update'])->name('users.update');
    Route::get('/{id}/show', [UserController::class, 'show'])->name('users.show');

    // ==========================================
    // USER ROLES & COMPANIES ROUTES
    // ==========================================
    Route::get('/roles', [\App\Http\Controllers\Users\UserRoleController::class, 'index'])->name('users.roles.index');
    Route::post('/roles/list', [\App\Http\Controllers\Users\UserRoleController::class, 'list'])->name('users.roles.list');
    Route::post('/roles/parent-users', [\App\Http\Controllers\Users\UserRoleController::class, 'getParentUsers'])->name('users.roles.parent-users');
    Route::get('/roles/{id}/mappings', [\App\Http\Controllers\Users\UserRoleController::class, 'mappings'])->name('users.roles.mappings');
    Route::post('/roles/{id}/store', [\App\Http\Controllers\Users\UserRoleController::class, 'store'])->name('users.roles.store');
    Route::post('/roles/mapping/{mapping_id}/delete', [\App\Http\Controllers\Users\UserRoleController::class, 'destroy'])->name('users.roles.destroy');
    Route::post('/roles/mapping/{mapping_id}/update', [\App\Http\Controllers\Users\UserRoleController::class, 'updateMapping'])->name('users.roles.update-mapping');
    Route::get('/roles/mapping/{mapping_id}/details', [\App\Http\Controllers\Users\UserRoleController::class, 'getMappingDetails'])->name('users.roles.mapping-details');

});
