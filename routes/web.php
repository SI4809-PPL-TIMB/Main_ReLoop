<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LokasiController;
use Illuminate\Support\Facades\Route;

// 1. Halaman Welcome
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::view('/profil', 'account')->name('account');
    Route::get('/lokasi', [LokasiController::class, 'edit'])->name('lokasi');
    Route::post('/lokasi', [LokasiController::class, 'update'])->name('lokasi.update');
    Route::post('/lokasi/gps', [LokasiController::class, 'gps'])->middleware('throttle:20,1')->name('lokasi.gps');
    Route::get('/wilayah/cari', [LokasiController::class, 'cari'])->name('wilayah.cari');
    Route::get('/wilayah/{level}', [LokasiController::class, 'anak'])->name('wilayah.anak');
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
