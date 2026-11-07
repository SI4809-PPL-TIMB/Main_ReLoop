<?php

namespace App\Http\Controllers;

use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        Log::info('Midtrans Webhook: Signature berhasil diverifikasi.', [
            'order_id'    => $orderId,
            'status_code' => $statusCode,
        ]);

        return response()->json([
            'message'  => 'Signature verified successfully.',
            'order_id' => $orderId,
        ], 200);
    }
}
