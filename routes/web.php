<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::view('/profil', 'account')->name('account');
    Route::post('/keluar', [AuthController::class, 'destroy'])->name('logout');
});

Route::get('/', function () {
    return view('welcome');
});
