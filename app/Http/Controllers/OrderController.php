<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderCreateRequest;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WilayahKabupaten;
use App\Models\WilayahKecamatan;
use App\Models\WilayahKelurahan;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 7.1.3 — Backend order menunggu bayar dan kunci barang (PPLTIMB-95).
 */
class OrderController extends Controller
{
    /**
     * Tampilkan halaman checkout untuk barang (7.1.7).
     */
    public function checkout(Request $request, Listing $listing): View|RedirectResponse
    {
        $user = $request->user();

        // Pemilik tidak bisa membeli barang miliknya sendiri
        if ($user->id === $listing->user_id) {
            return redirect()->route('barang.detail', $listing)
                ->with('error', 'Anda tidak dapat membeli barang milik sendiri.');
        }

        // Hanya barang berstatus aktif yang bisa dicheckout
        if ($listing->status !== Listing::AKTIF) {
            return redirect()->route('barang.detail', $listing)
                ->with('error', 'Barang ini tidak tersedia untuk dibeli.');
        }

        $listing->load(['primaryPhoto', 'user.profile.kelurahan']); // 'photos' tidak dipakai tampilan checkout

        // Cek tawaran yang disepakati (offer diterima)
        $acceptedOffer = Offer::whereHas('conversation', function ($q) use ($listing, $user) {
            $q->where('listing_id', $listing->id)
              ->where('buyer_id', $user->id);
        })->whereIn('status', ['diterima', 'accepted'])->latest()->first();

        $isOffer = false;
        $agreedPrice = (int) $listing->price;

        if ($acceptedOffer) {
            $isOffer = true;
            $agreedPrice = (int) $acceptedOffer->amount;
        }

        $platformFee = (int) round($agreedPrice * Payment::KOMISI_RATE);
        $user->load(['profile.kelurahan.kecamatan.kabupaten']);

        // Muat hanya anak wilayah terpilih; seluruh kelurahan Indonesia terlalu besar
        // untuk dimasukkan ke HTML checkout sekaligus.
        $kecamatan = $user->profile?->kelurahan?->kecamatan;
        $kecamatans = WilayahKecamatan::where('kabupaten_id', $kecamatan?->kabupaten_id)
            ->orderBy('nama')->get(['id', 'nama']);
        $kelurahans = WilayahKelurahan::where('kecamatan_id', $kecamatan?->id)
            ->orderBy('nama')->get(['id', 'nama']);
        $kabupatens = WilayahKabupaten::orderBy('nama')->get(['id', 'nama']);

        return view('pesanan.checkout', compact(
            'listing',
            'agreedPrice',
            'isOffer',
            'platformFee',
            'user',
            'acceptedOffer',
            'kecamatans',
            'kelurahans',
            'kabupatens'
        ));
    }

    /**
     * Buat order baru dengan status menunggu_bayar dan kunci status barang menjadi dipesan.
     */
    public function store(OrderCreateRequest $request, Listing $listing): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // 1. Validasi: Penjual tidak dapat membeli barangnya sendiri
        if ($user->id === $listing->user_id) {
            abort(403, 'Anda tidak dapat membeli barang milik sendiri.');
        }

        // 2. Tentukan harga yang disepakati (harga normal atau harga tawaran yang diterima)
        $agreedPrice = (float) $listing->price;

        if ($request->filled('offer_id')) {
            $offer = Offer::with('conversation')
                ->where('id', $request->input('offer_id'))
                ->where('status', 'diterima')
                ->first();

            // Pastikan offer valid, milik listing ini, dan pembelinya adalah user yang login
            if (
                $offer &&
                $offer->conversation &&
                $offer->conversation->listing_id === $listing->id &&
                $offer->conversation->buyer_id === $user->id
            ) {
                $agreedPrice = (float) $offer->amount;
            }
        }

        // 3. Jalankan transaksi database dengan pessimistic locking untuk mencegah race condition
        try {
            $order = DB::transaction(function () use ($request, $listing, $user, $agreedPrice) {
                // Kunci baris listing
                $lockedListing = Listing::where('id', $listing->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Pastikan barang masih berstatus aktif
                if ($lockedListing->status !== Listing::AKTIF) {
                    throw new \DomainException('Barang ini sudah tidak tersedia atau sedang dipesan.');
                }

                // Kunci barang menjadi dipesan
                $lockedListing->update(['status' => Listing::DIPESAN]);

                // Buat record pesanan
                $order = Order::create([
                    'order_number'     => Order::generateOrderNumber(),
                    'listing_id'       => $lockedListing->id,
                    'buyer_id'         => $user->id,
                    'seller_id'        => $lockedListing->user_id,
                    'agreed_price'     => $agreedPrice,
                    'delivery_method'  => $request->input('delivery_method'),
                    'delivery_address' => $request->input('delivery_address'),
                    'status'           => Order::STATUS_MENUNGGU_BAYAR,
                ]);

                // Inisialisasi payment dengan komisi platform 3%
                $platformFee = round($agreedPrice * Payment::KOMISI_RATE);
                Payment::create([
                    'order_id'     => $order->id,
                    'amount'       => $agreedPrice,
                    'platform_fee' => $platformFee,
                    'status'       => Payment::STATUS_MENUNGGU,
                ]);

                return $order;
            });
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['order' => $e->getMessage()]);
        }

        $order->load(['listing.primaryPhoto', 'payment', 'seller.profile', 'buyer.profile']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pesanan berhasil dibuat, barang telah dikunci.',
                'order'   => $order,
            ], 201);
        }

        return redirect()->route('order.show', $order->order_number)
            ->with('success', 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran.');
    }

    /**
     * Tampilkan detail status pesanan.
     */
    public function show(Request $request, string $orderNumber): JsonResponse|View
    {
        $order = Order::with(['listing.primaryPhoto', 'payment', 'seller.profile', 'buyer.profile'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $user = $request->user();

        // Hanya pembeli atau penjual yang boleh mengakses pesanan ini
        if ($user->id !== $order->buyer_id && $user->id !== $order->seller_id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        if ($request->expectsJson()) {
            return response()->json(['order' => $order]);
        }

        return view('pesanan.detail', compact('order'));
    }

    /**
     * Halaman status pembayaran pesanan (sukses, menunggu, gagal/kedaluwarsa).
     */
    public function paymentStatus(Request $request, string $orderNumber, MidtransService $midtransService): JsonResponse|View
    {
        $order = Order::with(['listing.primaryPhoto', 'payment', 'seller.profile', 'buyer.profile'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $user = $request->user();

        // Hanya pembeli atau penjual yang boleh mengakses status pesanan ini
        if ($user->id !== $order->buyer_id && $user->id !== $order->seller_id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        // Otomatis sinkronkan status dengan Midtrans API jika pesanan masih menunggu bayar
        if ($order->status === Order::STATUS_MENUNGGU_BAYAR && ($order->payment?->snap_token || ! app()->environment('testing'))) {
            $midtransService->syncOrderWithMidtrans($order);
            $order->refresh();
            $order->load(['listing.primaryPhoto', 'payment', 'seller.profile', 'buyer.profile']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'order_number' => $order->order_number,
                'status'       => $order->status,
                'is_paid'      => $order->status === Order::STATUS_DIBAYAR,
                'order'        => $order,
            ]);
        }

        return view('pesanan.status', compact('order'));
    }

    /**
     * Dapatkan Snap Token Midtrans untuk pembayaran pesanan.
     */
    public function getSnapToken(Request $request, string $orderNumber, MidtransService $midtransService): JsonResponse
    {
        $order = Order::with(['listing', 'buyer.profile', 'payment'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        // Hanya pembeli yang dapat meminta token pembayaran
        if ($request->user()->id !== $order->buyer_id) {
            abort(403, 'Hanya pembeli yang dapat melakukan pembayaran.');
        }

        // Pesanan harus dalam status menunggu_bayar
        if ($order->status !== Order::STATUS_MENUNGGU_BAYAR) {
            return response()->json([
                'message' => 'Pesanan ini tidak dalam status menunggu pembayaran.',
            ], 422);
        }

        try {
            $snapData = $midtransService->createSnapToken($order);

            return response()->json([
                'token'        => $snapData['token'],
                'redirect_url' => $snapData['redirect_url'],
                'client_key'   => config('services.midtrans.client_key'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * 7.2.2 — Penjual menandai barang pesanan telah diserahkan (PPLTIMB-99).
     */
    public function markDiserahkan(Request $request, string $orderNumber): JsonResponse|RedirectResponse
    {
        $order = Order::with(['listing', 'seller.profile', 'buyer.profile', 'payment'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $user = $request->user();

        // Hanya penjual yang berhak menandai serah terima barang
        if ($user->id !== $order->seller_id) {
            abort(403, 'Hanya penjual yang dapat menandai barang telah diserahkan.');
        }

        // Pesanan harus dalam status sudah dibayar
        if ($order->status !== Order::STATUS_DIBAYAR) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Hanya pesanan yang sudah dibayar yang dapat diserahkan.',
                ], 422);
            }

            return back()->withErrors(['order' => 'Hanya pesanan yang sudah dibayar yang dapat diserahkan.']);
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status'              => Order::STATUS_DISERAHKAN,
                'seller_confirmed_at' => now(),
                'auto_complete_at'    => now()->addDays(3),
            ]);
        });

        $order->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Barang berhasil ditandai diserahkan. Menunggu konfirmasi pembeli.',
                'order'   => $order,
            ]);
        }

        return redirect()->route('order.show', $order->order_number)
            ->with('success', 'Barang berhasil ditandai diserahkan. Pembeli diberi waktu 3 hari untuk konfirmasi.');
    }

    /**
     * 7.2.2 — Pembeli mengonfirmasi penerimaan barang dan menyelesaikan pesanan (PPLTIMB-99).
     * Dana diteruskan ke penjual setelah komisi platform 3%.
     */
    public function markDiterima(Request $request, string $orderNumber): JsonResponse|RedirectResponse
    {
        $order = Order::with(['listing', 'seller.profile', 'buyer.profile', 'payment'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $user = $request->user();

        // Hanya pembeli yang berhak mengonfirmasi penerimaan barang
        if ($user->id !== $order->buyer_id) {
            abort(403, 'Hanya pembeli yang dapat mengonfirmasi penerimaan barang.');
        }

        // Pesanan harus dalam status diserahkan
        if ($order->status !== Order::STATUS_DISERAHKAN) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Pesanan belum diserahkan oleh penjual.',
                ], 422);
            }

            return back()->withErrors(['order' => 'Pesanan belum diserahkan oleh penjual.']);
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status'             => Order::STATUS_SELESAI,
                'buyer_confirmed_at' => now(),
            ]);

            // Status barang berubah menjadi terjual
            $order->listing?->update(['status' => Listing::TERJUAL]);

            // Tambah riwayat penjualan & streak pada penjual
            if ($order->seller?->profile) {
                $order->seller->profile->increment('total_sold');
                $order->seller->profile->increment('streak_count');
                $order->seller->profile->increment('total_points', 50);
                $order->seller->profile->update(['streak_last_at' => now()->toDateString()]);
            }

            // Tambah streak & poin pada pembeli
            if ($order->buyer?->profile) {
                $order->buyer->profile->increment('streak_count');
                $order->buyer->profile->increment('total_points', 30);
                $order->buyer->profile->update(['streak_last_at' => now()->toDateString()]);
            }
        });

        $order->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pesanan selesai! Dana telah dicairkan ke penjual.',
                'order'   => $order,
            ]);
        }

        return redirect()->route('order.show', $order->order_number)
            ->with('success', 'Pesanan selesai! Dana telah diteruskan ke penjual. Terima kasih telah bertransaksi di ReLoop.');
    }

    /**
     * 7.2.2 — Batalkan pesanan sebelum serah terima barang (PPLTIMB-99).
     * Dapat dilakukan oleh pembeli atau penjual jika barang belum diserahkan.
     */
    public function cancel(Request $request, string $orderNumber): JsonResponse|RedirectResponse
    {
        $order = Order::with(['listing', 'payment'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $user = $request->user();

        // Hanya pembeli atau penjual yang berhak membatalkan
        if ($user->id !== $order->buyer_id && $user->id !== $order->seller_id) {
            abort(403, 'Anda tidak memiliki akses untuk membatalkan pesanan ini.');
        }

        // Pesanan hanya bisa dibatalkan sebelum diserahkan (menunggu_bayar atau dibayar)
        if (! $order->canBeCancelled()) {
            $msg = 'Pesanan yang sudah diserahkan atau selesai tidak dapat dibatalkan.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['order' => $msg]);
        }

        DB::transaction(function () use ($request, $order, $user) {
            $defaultReason = $user->id === $order->buyer_id ? 'Dibatalkan oleh pembeli' : 'Dibatalkan oleh penjual';
            $reason = $request->input('reason', $defaultReason);

            $order->update([
                'status'        => Order::STATUS_DIBATALKAN,
                'cancelled_at'  => now(),
                'cancel_reason' => $reason,
            ]);

            // Kembalikan status barang menjadi aktif di marketplace
            $order->listing?->update(['status' => Listing::AKTIF]);

            // Jika ada pembayaran yang masih menunggu, tandai gagal
            if ($order->payment && $order->payment->status === Payment::STATUS_MENUNGGU) {
                $order->payment->update(['status' => Payment::STATUS_GAGAL]);
            }
        });

        $order->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pesanan berhasil dibatalkan.',
                'order'   => $order,
            ]);
        }

        return redirect()->route('order.show', $order->order_number)
            ->with('success', 'Pesanan berhasil dibatalkan.');
    }
}

