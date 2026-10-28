<?php

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// 1. Halaman Welcome
Route::get('/', function () {
    return view('welcome');
})->name('home');

// 2. Halaman Daftar
Route::get('/daftar', [RegisterController::class, 'create'])->name('daftar');
Route::post('/daftar', [RegisterController::class, 'store'])->name('daftar.store');

// 3. Halaman Verifikasi
Route::get('/verifikasi', [RegisterController::class, 'showVerification'])->name('verifikasi');
Route::post('/verifikasi', [RegisterController::class, 'storeVerification'])->name('verifikasi.store');
Route::post('/verifikasi/resend', [RegisterController::class, 'resendVerification'])->name('verifikasi.resend');

// 4. Halaman Selesai (Akun Siap)
Route::get('/selesai', [RegisterController::class, 'showComplete'])->name('selesai');

// Placeholder masuk & logout
Route::get('/masuk', function () {
    return redirect()->route('daftar');
})->name('masuk');

Route::any('/keluar', function () {
    \Illuminate\Support\Facades\Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('daftar');
})->name('logout');