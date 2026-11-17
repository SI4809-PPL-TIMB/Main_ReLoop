<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransWebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    private string $serverKey = 'SB-Mid-server-test-key';
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CategorySeeder::class);

        config([
            'services.midtrans.server_key'    => $this->serverKey,
            'services.midtrans.client_key'    => 'SB-Mid-client-test-key',
            'services.midtrans.is_production' => false,
        ]);

        $seller = User::factory()->create(['email_verified_at' => now()]);
        $seller->profile()->create();

        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $buyer->profile()->create();

        $category = Category::where('type', 'bekas')->first();

        $listing = Listing::create([
            'user_id'       => $seller->id,
            'category_id'   => $category->id,
            'title'         => 'Helm KYT Bekas',
            'slug'          => 'helm-kyt-bekas',
            'description'   => 'Kondisi bagus',
            'price'         => 1500000,
            'condition'     => 'baik',
            'status'        => Listing::DIPESAN,
            'is_negotiable' => true,
        ]);

        $this->order = Order::create([
            'order_number'     => 'RL-20261107-1001',
            'listing_id'       => $listing->id,
            'buyer_id'         => $buyer->id,
            'seller_id'        => $seller->id,
            'agreed_price'     => 1500000,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Merdeka',
            'status'           => Order::STATUS_MENUNGGU_BAYAR,
        ]);

        Payment::create([
            'order_id'     => $this->order->id,
            'method'       => 'qris',
            'amount'       => 1500000,
            'platform_fee' => 45000,
            'status'       => Payment::STATUS_MENUNGGU,
            'expired_at'   => now()->addHours(24),
        ]);
    }

    public function test_webhook_berhasil_jika_signature_valid(): void
    {
        $orderId = $this->order->order_number;
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
                'status'   => Order::STATUS_DIBAYAR,
                'order_id' => $orderId,
            ]);
    }

    public function test_webhook_gagal_403_jika_signature_tidak_valid(): void
    {
        $orderId = $this->order->order_number;
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
            'order_id'     => $this->order->order_number,
            'status_code'  => '200',
            'gross_amount' => '1500000',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payloadWithoutSignature);

        $response->assertStatus(400)
            ->assertJsonStructure(['message']);
    }

    public function test_webhook_berhasil_dengan_gross_amount_format_desimal(): void
    {
        $orderId = $this->order->order_number;
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
                'status'   => Order::STATUS_DIBAYAR,
                'order_id' => $orderId,
            ]);
    }

    public function test_webhook_dapat_diakses_tanpa_csrf_token(): void
    {
        $orderId = $this->order->order_number;
        $statusCode = '200';
        $grossAmount = '1500000';
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
