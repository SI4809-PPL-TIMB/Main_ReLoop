<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutPembayaranEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private string $serverKey = 'SB-Mid-server-test-key';
    private User $buyer;
    private User $buyer2;
    private User $seller;
    private Listing $listing;

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
            'name'  => 'Penjual ReLoop',
            'email' => 'seller@reloop.test',
        ]);
        $this->seller->profile()->create();

        $this->buyer = User::factory()->create([
            'name'  => 'Pembeli ReLoop',
            'email' => 'buyer1@reloop.test',
        ]);
        $this->buyer->profile()->create(['patokan' => 'Jl. Merdeka No. 10']);

        $this->buyer2 = User::factory()->create([
            'name'  => 'Pembeli Kedua ReLoop',
            'email' => 'buyer2@reloop.test',
        ]);
        $this->buyer2->profile()->create(['patokan' => 'Jl. Sudirman No. 20']);

        $category = Category::where('type', 'bekas')->first();

        $this->listing = Listing::create([
            'user_id'       => $this->seller->id,
            'category_id'   => $category->id,
            'title'         => 'Meja Belajar Kayu Solid',
            'slug'          => 'meja-belajar-kayu-solid',
            'description'   => 'Kayu jati solid kondisi sangat mulus.',
            'price'         => 500000,
            'condition'     => 'baik',
            'status'        => Listing::AKTIF,
            'is_negotiable' => true,
        ]);
    }

    private function generateSignature(string $orderId, string $statusCode, string $grossAmount): string
    {
        return hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
    }

    public function test_end_to_end_alur_lengkap_checkout_snap_dan_webhook_lunas(): void
    {
        // 1. Pembeli melihat halaman checkout barang
        $resCheckout = $this->actingAs($this->buyer)
            ->get(route('order.checkout', $this->listing));
        $resCheckout->assertOk()
            ->assertSee('Meja Belajar Kayu Solid')
            ->assertSee('Rp 500.000');

        // 2. Pembeli submit pesanan (COD)
        $resOrder = $this->actingAs($this->buyer)->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Merdeka No. 10, Sukamaju',
            'payment_method'   => 'qris',
        ]);
        $resOrder->assertCreated();

        $order = Order::where('listing_id', $this->listing->id)->firstOrFail();
        $this->assertEquals(Order::STATUS_MENUNGGU_BAYAR, $order->status);
        $this->assertEquals(Listing::DIPESAN, $this->listing->fresh()->status);

        // 3. Request Midtrans Snap Token
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token'        => 'snap-token-e2e-12345',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-e2e-12345',
            ], 201),
        ]);

        $resSnap = $this->actingAs($this->buyer)
            ->postJson(route('order.snap_token', $order->order_number));
        $resSnap->assertOk()
            ->assertJson([
                'token' => 'snap-token-e2e-12345',
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id'   => $order->id,
            'snap_token' => 'snap-token-e2e-12345',
            'status'     => Payment::STATUS_MENUNGGU,
        ]);

        // 4. Midtrans mengirim Webhook settlement (pembayaran lunas)
        $orderId = $order->order_number;
        $statusCode = '200';
        $grossAmount = '500000';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $resWebhook = $this->postJson(route('midtrans.webhook'), [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'transaction_id'     => 'trx-e2e-777',
            'settlement_time'    => '2026-11-07 11:00:00',
        ]);
        $resWebhook->assertOk();

        // 5. Verifikasi status di database
        $this->assertEquals(Order::STATUS_DIBAYAR, $order->fresh()->status);
        $this->assertEquals(Payment::STATUS_BERHASIL, $order->payment->fresh()->status);
        $this->assertEquals('trx-e2e-777', $order->payment->fresh()->midtrans_id);
        $this->assertEquals(Listing::DIPESAN, $this->listing->fresh()->status);

        // 6. Pembeli membuka halaman status pembayaran
        $resStatus = $this->actingAs($this->buyer)
            ->get(route('order.status', $order->order_number));
        $resStatus->assertOk()
            ->assertSee('Pembayaran Berhasil!')
            ->assertSee('Dana Ditahan Rekber Reloop')
            ->assertSee('Chat Penjual untuk Janjian');
    }

    public function test_end_to_end_alur_checkout_dengan_harga_tawaran_disepakati(): void
    {
        // Penawaran disepakati di chat Rp 400.000
        $conv = Conversation::create([
            'listing_id' => $this->listing->id,
            'buyer_id'   => $this->buyer->id,
            'seller_id'  => $this->seller->id,
        ]);

        $offer = Offer::create([
            'conversation_id' => $conv->id,
            'offerer_id'      => $this->buyer->id,
            'amount'          => 400000,
            'status'          => 'diterima',
        ]);

        // Halaman checkout menampilkan harga tawaran
        $resCheckout = $this->actingAs($this->buyer)
            ->get(route('order.checkout', $this->listing));
        $resCheckout->assertOk()
            ->assertSee('Rp 400.000')
            ->assertSee('Harga tawaran disepakati');

        // Order dibuat dengan harga tawaran
        $resOrder = $this->actingAs($this->buyer)->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_AMBIL,
            'delivery_address' => 'Diambil langsung ke tempat penjual',
            'payment_method'   => 'va',
            'offer_id'         => $offer->id,
        ]);
        $resOrder->assertCreated();

        $order = Order::where('listing_id', $this->listing->id)->firstOrFail();
        $this->assertEquals(400000, (int) $order->agreed_price);
        // Komisi platform 3% dari 400.000 = 12.000
        $this->assertEquals(12000, (int) $order->payment->platform_fee);

        // Webhook settlement
        $orderId = $order->order_number;
        $statusCode = '200';
        $grossAmount = '400000';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $resWebhook = $this->postJson(route('midtrans.webhook'), [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'payment_type'       => 'bank_transfer',
            'transaction_id'     => 'trx-va-444',
        ]);
        $resWebhook->assertOk();

        $this->assertEquals(Order::STATUS_DIBAYAR, $order->fresh()->status);
        $this->assertEquals(Payment::STATUS_BERHASIL, $order->payment->fresh()->status);
    }

    public function test_end_to_end_pesanan_kedaluwarsa_melepas_kunci_barang_dan_bisa_dibeli_user_lain(): void
    {
        // 1. Pembeli 1 memesan barang
        $this->actingAs($this->buyer)->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Merdeka No. 10',
        ])->assertCreated();

        $orderBuyer1 = Order::where('listing_id', $this->listing->id)->firstOrFail();
        $this->assertEquals(Listing::DIPESAN, $this->listing->fresh()->status);

        // 2. Pembeli 2 mencoba checkout barang yang sedang dipesan -> ditolak
        $resBuyer2Gagal = $this->actingAs($this->buyer2)
            ->get(route('order.checkout', $this->listing));
        $resBuyer2Gagal->assertRedirect(route('barang.detail', $this->listing))
            ->assertSessionHas('error');

        // 3. Webhook menerima notifikasi expire dari Midtrans
        $orderId = $orderBuyer1->order_number;
        $statusCode = '200';
        $grossAmount = '500000';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $resExpire = $this->postJson(route('midtrans.webhook'), [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'expire',
        ]);
        $resExpire->assertOk();

        // Status order pembeli 1 jadi dibatalkan, barang kembali aktif
        $this->assertEquals(Order::STATUS_DIBATALKAN, $orderBuyer1->fresh()->status);
        $this->assertEquals(Listing::AKTIF, $this->listing->fresh()->status);

        // 4. Pembeli 2 sekarang berhasil checkout dan memesan barang tersebut
        $resBuyer2Sukses = $this->actingAs($this->buyer2)
            ->get(route('order.checkout', $this->listing));
        $resBuyer2Sukses->assertOk();

        $resOrderBuyer2 = $this->actingAs($this->buyer2)->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Sudirman No. 20',
        ]);
        $resOrderBuyer2->assertCreated();

        // Barang terkunci kembali untuk pembeli 2
        $this->assertEquals(Listing::DIPESAN, $this->listing->fresh()->status);
        $orderBuyer2 = Order::where('buyer_id', $this->buyer2->id)->firstOrFail();
        $this->assertEquals(Order::STATUS_MENUNGGU_BAYAR, $orderBuyer2->status);
    }

    public function test_keamanan_webhook_signature_palsu_tidak_dapat_mengubah_status_pesanan(): void
    {
        $this->actingAs($this->buyer)->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Merdeka No. 10',
        ])->assertCreated();

        $order = Order::where('listing_id', $this->listing->id)->firstOrFail();

        // Hacker mengirim notifikasi settlement dengan signature palsu
        $resAttack = $this->postJson(route('midtrans.webhook'), [
            'order_id'           => $order->order_number,
            'status_code'        => '200',
            'gross_amount'       => '500000',
            'signature_key'      => 'fake-signature-hacker',
            'transaction_status' => 'settlement',
        ]);

        $resAttack->assertStatus(403);

        // Status pesanan dan pembayaran tidak tersentuh
        $this->assertEquals(Order::STATUS_MENUNGGU_BAYAR, $order->fresh()->status);
        $this->assertEquals(Payment::STATUS_MENUNGGU, $order->payment->fresh()->status);
    }

    public function test_keamanan_pembayaran_yang_sudah_lunas_tidak_dapat_dibatalkan_oleh_webhook_expire_susulan(): void
    {
        $this->actingAs($this->buyer)->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Merdeka No. 10',
        ])->assertCreated();

        $order = Order::where('listing_id', $this->listing->id)->firstOrFail();

        // 1. Settlement sukses
        $orderId = $order->order_number;
        $statusCode = '200';
        $grossAmount = '500000';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $this->postJson(route('midtrans.webhook'), [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
        ])->assertOk();

        $this->assertEquals(Order::STATUS_DIBAYAR, $order->fresh()->status);

        // 2. Webhook expire susulan datang terlambat
        $resLateExpire = $this->postJson(route('midtrans.webhook'), [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'expire',
        ]);
        $resLateExpire->assertOk();

        // Idempotensi: Order tetap dibayar, payment tetap berhasil, listing tetap dipesan
        $this->assertEquals(Order::STATUS_DIBAYAR, $order->fresh()->status);
        $this->assertEquals(Payment::STATUS_BERHASIL, $order->payment->fresh()->status);
        $this->assertEquals(Listing::DIPESAN, $this->listing->fresh()->status);
    }
}
