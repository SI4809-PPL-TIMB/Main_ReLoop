<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 7.2.2 — Otomatisasi pembatalan pesanan tak dibayar dan penyelesaian pesanan otomatis (PPLTIMB-99).
 */
class ProcessOrdersAutoStatus extends Command
{
    /**
     * Nama dan signature command.
     *
     * @var string
     */
    protected $signature = 'orders:process-auto';

    /**
     * Deskripsi command console.
     *
     * @var string
     */
    protected $description = 'Proses otomatisasi status pesanan: membatalkan pesanan kedaluwarsa tak dibayar dan menyelesaikan pesanan yang lewat batas auto-complete';

    public function handle(): int
    {
        $this->info('Memulai pemrosesan otomatisasi status pesanan...');

        $cancelledCount = $this->cancelUnpaidOrders();
        $completedCount = $this->autoCompleteHandedOverOrders();

        $this->info("Selesai! {$cancelledCount} pesanan dibatalkan otomatis, {$completedCount} pesanan diselesaikan otomatis.");

        return Command::SUCCESS;
    }

    /**
     * Batalkan pesanan menunggu_bayar yang telah melewati batas 24 jam.
     */
    protected function cancelUnpaidOrders(): int
    {
        // Cari pesanan menunggu_bayar yang dibuat lebih dari 24 jam lalu atau expired_at telah lewat
        $unpaidOrders = Order::with(['listing', 'payment'])
            ->where('status', Order::STATUS_MENUNGGU_BAYAR)
            ->where(function ($q) {
                $q->where('created_at', '<=', now()->subHours(24))
                  ->orWhereHas('payment', fn ($pq) => $pq->whereNotNull('expired_at')->where('expired_at', '<=', now()));
            })
            ->get();

        $count = 0;

        foreach ($unpaidOrders as $order) {
            DB::transaction(function () use ($order) {
                $order->update([
                    'status'        => Order::STATUS_DIBATALKAN,
                    'cancelled_at'  => now(),
                    'cancel_reason' => 'Dibatalkan otomatis oleh sistem karena melewati batas waktu pembayaran 24 jam.',
                ]);

                // Kembalikan barang ke status aktif agar dapat dibeli pembeli lain
                $order->listing?->update(['status' => Listing::AKTIF]);

                if ($order->payment && $order->payment->status === Payment::STATUS_MENUNGGU) {
                    $order->payment->update(['status' => Payment::STATUS_KEDALUWARSA]);
                }
            });

            $this->line("Pesanan {$order->order_number} dibatalkan otomatis (kedaluwarsa pembayaran).");
            $count++;
        }

        return $count;
    }

    /**
     * Selesaikan pesanan diserahkan yang telah melewati auto_complete_at (3 hari tanpa komplain).
     */
    protected function autoCompleteHandedOverOrders(): int
    {
        $ordersToComplete = Order::with(['listing', 'seller.profile', 'buyer.profile'])
            ->where('status', Order::STATUS_DISERAHKAN)
            ->whereNotNull('auto_complete_at')
            ->where('auto_complete_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($ordersToComplete as $order) {
            DB::transaction(function () use ($order) {
                $order->update([
                    'status'             => Order::STATUS_SELESAI,
                    'buyer_confirmed_at' => now(),
                ]);

                // Update status barang menjadi terjual
                $order->listing?->update(['status' => Listing::TERJUAL]);

                // Tambah penjualan dan streak penjual
                if ($order->seller?->profile) {
                    $order->seller->profile->increment('total_sold');
                    $order->seller->profile->increment('streak_count');
                    $order->seller->profile->increment('total_points', 50);
                    $order->seller->profile->update(['streak_last_at' => now()->toDateString()]);
                }

                // Tambah streak & poin pembeli
                if ($order->buyer?->profile) {
                    $order->buyer->profile->increment('streak_count');
                    $order->buyer->profile->increment('total_points', 30);
                    $order->buyer->profile->update(['streak_last_at' => now()->toDateString()]);
                }
            });

            $this->line("Pesanan {$order->order_number} diselesaikan otomatis (batas 3 hari terlampaui).");
            $count++;
        }

        return $count;
    }
}
