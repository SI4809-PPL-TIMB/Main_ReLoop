<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransService
{
    protected string $serverKey;
    protected string $clientKey;
    protected bool $isProduction;
    protected string $snapBaseUrl;

    public function __construct()
    {
        $this->serverKey = (string) config('services.midtrans.server_key');
        $this->clientKey = (string) config('services.midtrans.client_key');
        $this->isProduction = (bool) config('services.midtrans.is_production', false);

        $this->snapBaseUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    /**
     * Dapatkan atau buat Snap Token baru untuk order.
     */
    public function createSnapToken(Order $order): array
    {
        // 1. Jika token sudah tersimpan di database, gunakan token yang ada (idempoten / cached)
        if ($order->payment && $order->payment->snap_token) {
            return [
                'token'        => $order->payment->snap_token,
                'redirect_url' => $order->payment->snap_redirect_url,
            ];
        }

        // 2. Siapkan data transaksi
        $buyer = $order->buyer;
        $listing = $order->listing;

        $payload = [
            'transaction_details' => [
                'order_id'     => $order->order_number,
                'gross_amount' => (int) $order->agreed_price,
            ],
            'item_details' => [
                [
                    'id'       => (string) $listing->id,
                    'price'    => (int) $order->agreed_price,
                    'quantity' => 1,
                    'name'     => mb_strimwidth($listing->title, 0, 50, '...'),
                ],
            ],
            'customer_details' => [
                'first_name' => $buyer->name,
                'email'      => $buyer->email ?? 'buyer@reloop.test',
                'phone'      => $buyer->phone ?? $buyer->profile?->phone ?? '081234567890',
            ],
        ];

        // 3. Request ke API Snap Midtrans
        $response = Http::withHeaders([
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->serverKey . ':'),
        ])->post($this->snapBaseUrl . '/transactions', $payload);

        if (! $response->successful()) {
            Log::error('Midtrans Snap Error', [
                'order_number' => $order->order_number,
                'status'       => $response->status(),
                'response'     => $response->body(),
            ]);

            throw new RuntimeException('Gagal membuat token pembayaran Midtrans: ' . ($response->json('error_messages.0') ?? 'Terjadi kesalahan sistem.'));
        }

        $data = $response->json();
        $token = $data['token'] ?? null;
        $redirectUrl = $data['redirect_url'] ?? null;

        // 4. Simpan ke database payments
        if ($order->payment) {
            $order->payment->update([
                'snap_token'        => $token,
                'snap_redirect_url' => $redirectUrl,
            ]);
        }

        return [
            'token'        => $token,
            'redirect_url' => $redirectUrl,
        ];
    }

    /**
     * Verifikasi keaslian signature webhook dari Midtrans.
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signature): bool
    {
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Cek status transaksi langsung ke Midtrans API (Core API v2).
     */
    public function getTransactionStatus(string $orderNumber): ?array
    {
        $baseUrl = $this->isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';

        try {
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->serverKey . ':'),
            ])->timeout(5)->get($baseUrl . '/' . $orderNumber . '/status');

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::warning('Midtrans Status Check Error: ' . $e->getMessage(), ['order_number' => $orderNumber]);
        }

        return null;
    }

    /**
     * Sinkronkan status pesanan langsung dengan data terbaru dari Midtrans API.
     */
    public function syncOrderWithMidtrans(Order $order): Order
    {
        if ($order->status !== Order::STATUS_MENUNGGU_BAYAR) {
            return $order;
        }

        $payload = $this->getTransactionStatus($order->order_number);
        if ($payload && isset($payload['transaction_status'])) {
            return $this->applyTransactionPayload($order, $payload);
        }

        return $order;
    }

    /**
     * Terapkan update status transaksi Midtrans ke pesanan dan pembayaran secara atomik.
     */
    public function applyTransactionPayload(Order $order, array $payload): Order
    {
        return DB::transaction(function () use ($order, $payload) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();
            if (! $lockedOrder) {
                return $order;
            }

            $transactionStatus = (string) ($payload['transaction_status'] ?? '');
            $fraudStatus = (string) ($payload['fraud_status'] ?? '');
            $paymentType = (string) ($payload['payment_type'] ?? '');
            $transactionId = (string) ($payload['transaction_id'] ?? '');

            // 1. Idempotensi: Jika pesanan sudah lunas (dibayar), jangan ubah status lagi
            if ($lockedOrder->status === Order::STATUS_DIBAYAR) {
                return $lockedOrder;
            }

            // 2. Ekstrak data pembayaran
            $mappedMethod = $this->mapPaymentMethod($paymentType);
            $vaNumber = null;
            $bank = null;

            if (! empty($payload['va_numbers']) && is_array($payload['va_numbers'])) {
                $vaNumber = $payload['va_numbers'][0]['va_number'] ?? null;
                $bank = $payload['va_numbers'][0]['bank'] ?? null;
            } elseif (! empty($payload['permata_va_number'])) {
                $vaNumber = $payload['permata_va_number'];
                $bank = 'permata';
            } elseif (! empty($payload['bill_key'])) {
                $vaNumber = $payload['bill_key'];
                $bank = 'mandiri';
            }

            $isPaid = $transactionStatus === 'settlement'
                || ($transactionStatus === 'capture' && ($fraudStatus === 'accept' || $fraudStatus === ''));

            // 3. Pembayaran Sukses (settlement / capture accept)
            if ($isPaid) {
                $lockedOrder->update(['status' => Order::STATUS_DIBAYAR]);

                if ($lockedOrder->payment) {
                    $lockedOrder->payment->update([
                        'status'      => Payment::STATUS_BERHASIL,
                        'midtrans_id' => $transactionId ?: $lockedOrder->payment->midtrans_id,
                        'method'      => $mappedMethod ?: $lockedOrder->payment->method,
                        'va_number'   => $vaNumber ?: $lockedOrder->payment->va_number,
                        'bank'        => $bank ?: $lockedOrder->payment->bank,
                        'paid_at'     => isset($payload['settlement_time']) ? Carbon::parse($payload['settlement_time']) : now(),
                    ]);
                }

                Log::info('Midtrans: Pesanan berhasil dibayar.', [
                    'order_id'       => $lockedOrder->order_number,
                    'transaction_id' => $transactionId,
                ]);

                return $lockedOrder;
            }

            // 4. Pembayaran Kedaluwarsa (expire) -> batalkan pesanan & buka kunci barang
            if ($transactionStatus === 'expire') {
                if ($lockedOrder->status === Order::STATUS_MENUNGGU_BAYAR) {
                    $lockedOrder->update([
                        'status'        => Order::STATUS_DIBATALKAN,
                        'cancelled_at'  => now(),
                        'cancel_reason' => 'Batas waktu pembayaran kedaluwarsa.',
                    ]);

                    if ($lockedOrder->payment) {
                        $lockedOrder->payment->update([
                            'status' => Payment::STATUS_KEDALUWARSA,
                        ]);
                    }

                    // Buka kembali barang menjadi aktif agar bisa dibeli pengguna lain
                    $lockedOrder->listing()->update(['status' => Listing::AKTIF]);

                    Log::info('Midtrans: Pesanan kedaluwarsa, barang dibuka kembali.', [
                        'order_id'   => $lockedOrder->order_number,
                        'listing_id' => $lockedOrder->listing_id,
                    ]);
                }

                return $lockedOrder;
            }

            // 5. Pembayaran Dibatalkan atau Ditolak (cancel / deny)
            if (in_array($transactionStatus, ['cancel', 'deny'], true)) {
                if ($lockedOrder->status === Order::STATUS_MENUNGGU_BAYAR) {
                    $lockedOrder->update([
                        'status'        => Order::STATUS_DIBATALKAN,
                        'cancelled_at'  => now(),
                        'cancel_reason' => 'Pembayaran ditolak atau dibatalkan.',
                    ]);

                    if ($lockedOrder->payment) {
                        $lockedOrder->payment->update([
                            'status' => Payment::STATUS_GAGAL,
                        ]);
                    }

                    // Buka kembali barang menjadi aktif
                    $lockedOrder->listing()->update(['status' => Listing::AKTIF]);

                    Log::info('Midtrans: Pesanan dibatalkan/ditolak, barang dibuka kembali.', [
                        'order_id'   => $lockedOrder->order_number,
                        'listing_id' => $lockedOrder->listing_id,
                    ]);
                }

                return $lockedOrder;
            }

            // 6. Pembayaran Masih Pending (misal VA baru di-generate oleh customer)
            if ($transactionStatus === 'pending') {
                if ($lockedOrder->payment && ($transactionId || $mappedMethod || $vaNumber || $bank)) {
                    $lockedOrder->payment->update([
                        'midtrans_id' => $transactionId ?: $lockedOrder->payment->midtrans_id,
                        'method'      => $mappedMethod ?: $lockedOrder->payment->method,
                        'va_number'   => $vaNumber ?: $lockedOrder->payment->va_number,
                        'bank'        => $bank ?: $lockedOrder->payment->bank,
                    ]);
                }
            }

            return $lockedOrder;
        });
    }

    /**
     * Map Midtrans payment_type ke enum method payments table: qris, va, ewallet.
     */
    public function mapPaymentMethod(?string $paymentType): ?string
    {
        if (! $paymentType) {
            return null;
        }

        return match ($paymentType) {
            'qris'                      => Payment::METHOD_QRIS,
            'bank_transfer', 'echannel' => Payment::METHOD_VA,
            'gopay', 'shopeepay'        => Payment::METHOD_EWALLET,
            default                     => in_array($paymentType, ['qris', 'va', 'ewallet'], true) ? $paymentType : null,
        };
    }
}
