<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    /**
     * Handle incoming Midtrans HTTP Webhook Notification.
     */
    public function handle(Request $request, MidtransService $midtransService): JsonResponse
    {
        $payload = $request->all();

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            return response()->json([
                'message' => 'Payload tidak lengkap. order_id, status_code, gross_amount, dan signature_key wajib ada.',
            ], 400);
        }

        if (! $midtransService->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('Midtrans Webhook: Signature tidak valid.', [
                'order_id'      => $orderId,
                'status_code'   => $statusCode,
                'gross_amount'  => $grossAmount,
                'signature_key' => $signatureKey,
            ]);

            return response()->json([
                'message' => 'Invalid signature.',
            ], 403);
        }

        return $this->processTransaction($orderId, $payload);
    }

    /**
     * Proses transisi status pesanan dan pembayaran secara idempoten dan atomik.
     */
    protected function processTransaction(string $orderId, array $payload): JsonResponse
    {
        return DB::transaction(function () use ($orderId, $payload) {
            $order = Order::where('order_number', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                Log::warning('Midtrans Webhook: Pesanan tidak ditemukan.', ['order_id' => $orderId]);

                return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
            }

            $transactionStatus = (string) ($payload['transaction_status'] ?? '');
            $fraudStatus = (string) ($payload['fraud_status'] ?? '');
            $paymentType = (string) ($payload['payment_type'] ?? '');
            $transactionId = (string) ($payload['transaction_id'] ?? '');

            // 1. Idempotensi: Jika pesanan sudah lunas (dibayar), jangan ubah status lagi
            if ($order->status === Order::STATUS_DIBAYAR) {
                Log::info('Midtrans Webhook: Pesanan sudah dibayar sebelumnya (idempoten).', [
                    'order_id'           => $orderId,
                    'transaction_status' => $transactionStatus,
                ]);

                return response()->json([
                    'message'  => 'Pesanan sudah berstatus dibayar (idempoten).',
                    'order_id' => $orderId,
                ], 200);
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
                $order->update(['status' => Order::STATUS_DIBAYAR]);

                if ($order->payment) {
                    $order->payment->update([
                        'status'      => Payment::STATUS_BERHASIL,
                        'midtrans_id' => $transactionId ?: $order->payment->midtrans_id,
                        'method'      => $mappedMethod ?: $order->payment->method,
                        'va_number'   => $vaNumber ?: $order->payment->va_number,
                        'bank'        => $bank ?: $order->payment->bank,
                        'paid_at'     => isset($payload['settlement_time']) ? Carbon::parse($payload['settlement_time']) : now(),
                    ]);
                }

                Log::info('Midtrans Webhook: Pesanan berhasil dibayar.', [
                    'order_id'       => $orderId,
                    'transaction_id' => $transactionId,
                ]);

                return response()->json([
                    'message'  => 'Pembayaran pesanan berhasil dikonfirmasi.',
                    'order_id' => $orderId,
                    'status'   => Order::STATUS_DIBAYAR,
                ], 200);
            }

            // 4. Pembayaran Kedaluwarsa (expire) -> batalkan pesanan & buka kunci barang
            if ($transactionStatus === 'expire') {
                if ($order->status === Order::STATUS_MENUNGGU_BAYAR) {
                    $order->update([
                        'status'        => Order::STATUS_DIBATALKAN,
                        'cancelled_at'  => now(),
                        'cancel_reason' => 'Batas waktu pembayaran kedaluwarsa.',
                    ]);

                    if ($order->payment) {
                        $order->payment->update([
                            'status' => Payment::STATUS_KEDALUWARSA,
                        ]);
                    }

                    // Buka kembali barang menjadi aktif agar bisa dibeli pengguna lain
                    $order->listing()->update(['status' => Listing::AKTIF]);

                    Log::info('Midtrans Webhook: Pesanan kedaluwarsa, barang dibuka kembali.', [
                        'order_id'   => $orderId,
                        'listing_id' => $order->listing_id,
                    ]);
                }

                return response()->json([
                    'message'  => 'Pesanan kedaluwarsa dan telah dibatalkan.',
                    'order_id' => $orderId,
                    'status'   => Order::STATUS_DIBATALKAN,
                ], 200);
            }

            // 5. Pembayaran Dibatalkan atau Ditolak (cancel / deny)
            if (in_array($transactionStatus, ['cancel', 'deny'], true)) {
                if ($order->status === Order::STATUS_MENUNGGU_BAYAR) {
                    $order->update([
                        'status'        => Order::STATUS_DIBATALKAN,
                        'cancelled_at'  => now(),
                        'cancel_reason' => 'Pembayaran ditolak atau dibatalkan.',
                    ]);

                    if ($order->payment) {
                        $order->payment->update([
                            'status' => Payment::STATUS_GAGAL,
                        ]);
                    }

                    // Buka kembali barang menjadi aktif
                    $order->listing()->update(['status' => Listing::AKTIF]);

                    Log::info('Midtrans Webhook: Pesanan dibatalkan/ditolak, barang dibuka kembali.', [
                        'order_id'   => $orderId,
                        'listing_id' => $order->listing_id,
                    ]);
                }

                return response()->json([
                    'message'  => 'Pesanan dibatalkan atau ditolak.',
                    'order_id' => $orderId,
                    'status'   => Order::STATUS_DIBATALKAN,
                ], 200);
            }

            // 6. Pembayaran Masih Pending (misal VA baru di-generate oleh customer di simulator)
            if ($transactionStatus === 'pending') {
                if ($order->payment && ($transactionId || $mappedMethod || $vaNumber || $bank)) {
                    $order->payment->update([
                        'midtrans_id' => $transactionId ?: $order->payment->midtrans_id,
                        'method'      => $mappedMethod ?: $order->payment->method,
                        'va_number'   => $vaNumber ?: $order->payment->va_number,
                        'bank'        => $bank ?: $order->payment->bank,
                    ]);
                }

                return response()->json([
                    'message'  => 'Pesanan menunggu pembayaran.',
                    'order_id' => $orderId,
                    'status'   => Order::STATUS_MENUNGGU_BAYAR,
                ], 200);
            }

            return response()->json([
                'message'  => 'Notifikasi webhook diproses.',
                'order_id' => $orderId,
            ], 200);
        });
    }

    /**
     * Map Midtrans payment_type ke enum method payments table: qris, va, ewallet.
     */
    protected function mapPaymentMethod(?string $paymentType): ?string
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
