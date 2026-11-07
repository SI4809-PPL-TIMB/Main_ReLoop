<?php

namespace App\Services;

use App\Models\Order;
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
}
