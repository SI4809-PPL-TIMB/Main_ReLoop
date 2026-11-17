<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 7.2.2 — Jadwal otomatisasi status pesanan: pembatalan tak dibayar dan penyelesaian otomatis (PPLTIMB-99)
Schedule::command('orders:process-auto')->hourly();
