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

class MidtransWebhookIdempotentTest extends TestCase
{
    use RefreshDatabase;

    private string $serverKey = 'SB-Mid-server-test-key';
    private User $buyer;
    private User $seller;
    private Listing $listing;
    private Order $order;
    private Payment $payment;

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

        $this->seller = User::factory()->create([
            'email_verified_at' => now(),
            'name'              => 'Penjual ReLoop',
        ]);
        $this->seller->profile()->create();

        $this->buyer = User::factory()->create([
            'email_verified_at' => now(),
            'name'              => 'Pembeli ReLoop',
        ]);
        $this->buyer->profile()->create();

        $category = Category::where('type', 'bekas')->first();

        $this->listing = Listing::create([
            'user_id'       => $this->seller->id,
            'category_id'   => $category->id,
            'title'         => 'Sepeda Lipat Polygon',
            'slug'          => 'sepeda-lipat-polygon',
            'description'   => 'Kondisi normal siap pakai.',
            'price'         => 2000000,
            'condition'     => 'baik',
            'status'        => Listing::DIPESAN,
            'is_negotiable' => true,
        ]);

        $this->order = Order::create([
            'order_number'     => Order::generateOrderNumber(),
            'listing_id'       => $this->listing->id,
            'buyer_id'         => $this->buyer->id,
            'seller_id'        => $this->seller->id,
            'agreed_price'     => 2000000,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Sudirman No. 45',
            'status'           => Order::STATUS_MENUNGGU_BAYAR,
        ]);

        $this->payment = Payment::create([
            'order_id'     => $this->order->id,
            'method'       => 'qris',
            'amount'       => 2000000,
            'platform_fee' => 60000,
            'status'       => Payment::STATUS_MENUNGGU,
            'expired_at'   => now()->addHours(24),
        ]);
    }

    private function generateSignature(string $orderId, string $statusCode, string $grossAmount): string
    {
        return hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
    }

    public function test_settlement_mengubah_status_pesanan_menjadi_dibayar_dan_pembayaran_berhasil(): void
    {
        $orderId = $this->order->order_number;
        $statusCode = '200';
        $grossAmount = '2000000';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'transaction_id'     => 'trx-midtrans-999',
            'settlement_time'    => '2026-11-07 10:30:00',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payload);

        $response->assertOk()
            ->assertJson([
                'status'   => Order::STATUS_DIBAYAR,
                'order_id' => $orderId,
            ]);

        $this->assertDatabaseHas('orders', [
            'id'     => $this->order->id,
            'status' => Order::STATUS_DIBAYAR,
        ]);

        $this->assertDatabaseHas('payments', [
            'id'          => $this->payment->id,
            'status'      => Payment::STATUS_BERHASIL,
            'midtrans_id' => 'trx-midtrans-999',
            'method'      => Payment::METHOD_QRIS,
        ]);

        // Listing tetap berstatus dipesan
        $this->assertDatabaseHas('listings', [
            'id'     => $this->listing->id,
            'status' => Listing::DIPESAN,
        ]);
    }

    public function test_idempotensi_webhook_settlement_berulang_tidak_mengubah_status(): void
    {
        // Set awal sudah dibayar
        $this->order->update(['status' => Order::STATUS_DIBAYAR]);
        $this->payment->update([
            'status'      => Payment::STATUS_BERHASIL,
            'midtrans_id' => 'trx-existing-1',
            'paid_at'     => now(),
        ]);

        $orderId = $this->order->order_number;
        $statusCode = '200';
        $grossAmount = '2000000';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payload);

        $response->assertOk()
            ->assertJson([
                'message'  => 'Pesanan sudah berstatus dibayar (idempoten).',
                'order_id' => $orderId,
            ]);

        // Status tetap dibayar dan berhasil
        $this->assertEquals(Order::STATUS_DIBAYAR, $this->order->fresh()->status);
        $this->assertEquals(Payment::STATUS_BERHASIL, $this->payment->fresh()->status);
    }

    public function test_idempotensi_order_yang_sudah_dibayar_kebal_terhadap_webhook_expire_atau_cancel(): void
    {
        $this->order->update(['status' => Order::STATUS_DIBAYAR]);
        $this->payment->update(['status' => Payment::STATUS_BERHASIL]);

        $orderId = $this->order->order_number;
        $statusCode = '200';
        $grossAmount = '2000000';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'expire',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payload);

        $response->assertOk()
            ->assertJson([
                'message' => 'Pesanan sudah berstatus dibayar (idempoten).',
            ]);

        $this->assertEquals(Order::STATUS_DIBAYAR, $this->order->fresh()->status);
        $this->assertEquals(Payment::STATUS_BERHASIL, $this->payment->fresh()->status);
        $this->assertEquals(Listing::DIPESAN, $this->listing->fresh()->status);
    }

    public function test_expire_membatalkan_pesanan_dan_membuka_kembali_kunci_barang(): void
    {
        $orderId = $this->order->order_number;
        $statusCode = '200';
        $grossAmount = '2000000';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'expire',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payload);

        $response->assertOk()
            ->assertJson([
                'status' => Order::STATUS_DIBATALKAN,
            ]);

        $this->assertDatabaseHas('orders', [
            'id'     => $this->order->id,
            'status' => Order::STATUS_DIBATALKAN,
        ]);

        $this->assertDatabaseHas('payments', [
            'id'     => $this->payment->id,
            'status' => Payment::STATUS_KEDALUWARSA,
        ]);

        // Barang dibuka kembali jadi aktif
        $this->assertDatabaseHas('listings', [
            'id'     => $this->listing->id,
            'status' => Listing::AKTIF,
        ]);
    }

    public function test_cancel_atau_deny_membatalkan_pesanan_dan_membuka_kembali_kunci_barang(): void
    {
        $orderId = $this->order->order_number;
        $statusCode = '200';
        $grossAmount = '2000000';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'deny',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payload);

        $response->assertOk()
            ->assertJson([
                'status' => Order::STATUS_DIBATALKAN,
            ]);

        $this->assertDatabaseHas('orders', [
            'id'     => $this->order->id,
            'status' => Order::STATUS_DIBATALKAN,
        ]);

        $this->assertDatabaseHas('payments', [
            'id'     => $this->payment->id,
            'status' => Payment::STATUS_GAGAL,
        ]);

        $this->assertDatabaseHas('listings', [
            'id'     => $this->listing->id,
            'status' => Listing::AKTIF,
        ]);
    }

    public function test_pending_memperbarui_info_va_pembayaran(): void
    {
        $orderId = $this->order->order_number;
        $statusCode = '201';
        $grossAmount = '2000000';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $payload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'pending',
            'payment_type'       => 'bank_transfer',
            'transaction_id'     => 'trx-pending-888',
            'va_numbers'         => [
                [
                    'bank'      => 'bca',
                    'va_number' => '98765432101',
                ],
            ],
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payload);

        $response->assertOk()
            ->assertJson([
                'status' => Order::STATUS_MENUNGGU_BAYAR,
            ]);

        $this->assertDatabaseHas('payments', [
            'id'          => $this->payment->id,
            'status'      => Payment::STATUS_MENUNGGU,
            'method'      => Payment::METHOD_VA,
            'bank'        => 'bca',
            'va_number'   => '98765432101',
            'midtrans_id' => 'trx-pending-888',
        ]);
    }

    public function test_order_tidak_ditemukan_mengembalikan_404(): void
    {
        $unknownOrderId = 'RL-99999999-XXXX';
        $statusCode = '200';
        $grossAmount = '2000000';
        $signature = $this->generateSignature($unknownOrderId, $statusCode, $grossAmount);

        $payload = [
            'order_id'           => $unknownOrderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $payload);

        $response->assertStatus(404);
    }
}
