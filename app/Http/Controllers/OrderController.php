<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderCreateRequest;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Payment;
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
}

