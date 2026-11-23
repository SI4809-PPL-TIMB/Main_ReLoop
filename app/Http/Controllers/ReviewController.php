<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 10.1.2 — Backend Rating dan Ulasan Penjual (PPLTIMB-148).
 *
 * Kriteria Penerimaan:
 * - Rating 1–5 dan ulasan bisa diberikan satu kali per pesanan selesai.
 * - Rating rata-rata dan jumlah ulasan tampil di kartu penjual dan profil penjual.
 * - Pesanan yang belum selesai tidak bisa diulas.
 */
class ReviewController extends Controller
{
    /**
     * Tampilkan formulir pemberian rating & ulasan untuk pesanan selesai.
     */
    public function create(Request $request, string $orderNumber): View|RedirectResponse
    {
        $order = Order::with(['listing.primaryPhoto', 'seller.profile', 'reviews'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $userId = $request->user()->id;

        // Validasi: Hanya pembeli dari pesanan ini yang berhak mengulas
        if (! $order->isBuyer($userId)) {
            abort(403, 'Hanya pembeli yang dapat memberikan ulasan untuk pesanan ini.');
        }

        // Kriteria penerimaan: Pesanan yang belum selesai tidak bisa diulas
        if ($order->status !== Order::STATUS_SELESAI) {
            return redirect()->route('order.show', $order->order_number)
                ->with('error', 'Ulasan hanya dapat diberikan setelah pesanan selesai.');
        }

        // Kriteria penerimaan: Ulasan hanya bisa diberikan satu kali per pesanan
        $existingReview = $order->reviews()->where('reviewer_id', $userId)->first();
        if ($existingReview) {
            return redirect()->route('order.show', $order->order_number)
                ->with('info', 'Kamu sudah memberikan ulasan untuk pesanan ini.');
        }

        return view('ulasan.create', compact('order'));
    }

    /**
     * Simpan rating & ulasan dari pembeli ke database serta perbarui profil penjual.
     */
    public function store(Request $request, string $orderNumber): RedirectResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $userId = $request->user()->id;

        // Validasi otorisasi pembeli
        if (! $order->isBuyer($userId)) {
            abort(403, 'Hanya pembeli yang dapat memberikan ulasan untuk pesanan ini.');
        }

        // Kriteria penerimaan: Pesanan yang belum selesai tidak bisa diulas
        if ($order->status !== Order::STATUS_SELESAI) {
            return redirect()->route('order.show', $order->order_number)
                ->with('error', 'Pesanan belum selesai. Ulasan belum dapat dikirim.');
        }

        // Kriteria penerimaan: Hanya bisa satu kali per pesanan selesai
        if ($order->reviews()->where('reviewer_id', $userId)->exists()) {
            return redirect()->route('order.show', $order->order_number)
                ->with('error', 'Kamu sudah memberikan ulasan untuk pesanan ini.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body'   => ['nullable', 'string', 'max:500'],
        ], [
            'rating.required' => 'Pilih rating bintang 1 sampai 5.',
            'rating.min'      => 'Rating minimal 1 bintang.',
            'rating.max'      => 'Rating maksimal 5 bintang.',
            'body.max'        => 'Ulasan maksimal 500 karakter.',
        ]);

        DB::transaction(function () use ($order, $userId, $validated) {
            // 1. Simpan ulasan
            Review::create([
                'order_id'    => $order->id,
                'reviewer_id' => $userId,
                'reviewee_id' => $order->seller_id,
                'listing_id'  => $order->listing_id,
                'rating'      => (int) $validated['rating'],
                'body'        => ! empty($validated['body']) ? trim($validated['body']) : null,
            ]);

            // 2. Hitung ulang rata-rata rating penjual dan simpan ke profil
            self::recalculateSellerRating($order->seller_id);
        });

        return redirect()->route('order.show', $order->order_number)
            ->with('success', 'Terima kasih! Ulasanmu berhasil dikirim.');
    }

    /**
     * Perbarui nilai avg_rating pada profile penjual berdasarkan seluruh ulasan yang diterima.
     */
    public static function recalculateSellerRating(int $sellerId): void
    {
        $seller = User::with('profile')->find($sellerId);
        if (! $seller) {
            return;
        }

        $avg = Review::where('reviewee_id', $sellerId)->avg('rating');

        if ($seller->profile) {
            $seller->profile->update([
                'avg_rating' => $avg !== null ? round((float) $avg, 2) : 0,
            ]);
        }
    }
}
