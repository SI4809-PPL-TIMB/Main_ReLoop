<?php

namespace Tests\Feature;

use Tests\TestCase;

class MidtransWebhookSignatureTest extends TestCase
{
    private string $serverKey = 'SB-Mid-server-test-key';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key'    => $this->serverKey,
            'services.midtrans.client_key'    => 'SB-Mid-client-test-key',
            'services.midtrans.is_production' => false,
        ]);
    }

    public function test_webhook_berhasil_jika_signature_valid(): void
    {
        $orderId = 'RL-20261107-1001';
        $statusCode = '200';
        $grossAmount = '1500000';
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payload);

        $response->assertOk()
            ->assertJson([
                'message'  => 'Signature verified successfully.',
                'order_id' => $orderId,
            ]);
    }

    public function test_webhook_gagal_403_jika_signature_tidak_valid(): void
    {
        $orderId = 'RL-20261107-1002';
        $statusCode = '200';
        $grossAmount = '1500000';
        $invalidSignature = 'tampered-signature-invalid-12345';

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $invalidSignature,
            'transaction_status' => 'settlement',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payload);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Invalid signature.',
            ]);
    }

    public function test_webhook_gagal_400_jika_field_wajib_tidak_lengkap(): void
    {
        $payloadWithoutSignature = [
            'order_id'     => 'RL-20261107-1003',
            'status_code'  => '200',
            'gross_amount' => '1500000',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payloadWithoutSignature);

        $response->assertStatus(400)
            ->assertJsonStructure(['message']);
    }

    public function test_webhook_berhasil_dengan_gross_amount_format_desimal(): void
    {
        $orderId = 'RL-20261107-1004';
        $statusCode = '200';
        $grossAmount = '1500000.00';
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
        ];

        $response = $this->postJson('/api/midtrans/webhook', $payload);

        $response->assertOk()
            ->assertJson([
                'message'  => 'Signature verified successfully.',
                'order_id' => $orderId,
            ]);
    }

    public function test_webhook_dapat_diakses_tanpa_csrf_token(): void
    {
        $orderId = 'RL-20261107-1005';
        $statusCode = '200';
        $grossAmount = '500000';
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
        ];

        // Memanggil endpoint via form POST biasa tanpa csrf token
        $response = $this->post('/midtrans/webhook', $payload);

        // Harus 200 OK, bukan 419 CSRF token mismatch
        $response->assertOk();
    }
}
