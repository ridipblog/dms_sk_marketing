<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');

// You can add your other auth routes here later, for example:
Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
// Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');

// Set active context for users with multiple roles/companies
Route::post('/set-active-context', [AuthController::class, 'setActiveContext'])->name('set.active.context')->middleware('auth');
