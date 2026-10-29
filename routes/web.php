<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

// 1. Halaman Welcome
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/lupa-sandi', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/lupa-sandi', [PasswordResetController::class, 'send'])->middleware('throttle:5,1,password-email:')->name('password.email');
    Route::get('/lupa-sandi/terkirim', [PasswordResetController::class, 'sent'])->name('password.sent');
    Route::post('/lupa-sandi/verifikasi', [PasswordResetController::class, 'verify'])->middleware('throttle:5,1,password-verify:')->name('password.verify');
    Route::get('/atur-ulang-sandi/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/atur-ulang-sandi', [PasswordResetController::class, 'update'])->middleware('throttle:5,1,password-update:')->name('password.update');
    Route::view('/kata-sandi-diperbarui', 'auth.password', ['step' => 4])->name('password.complete');
});

Route::middleware('auth')->group(function () {
    Route::view('/profil', 'account')->name('account');
    Route::post('/keluar', [AuthController::class, 'destroy'])->name('logout');
});

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
