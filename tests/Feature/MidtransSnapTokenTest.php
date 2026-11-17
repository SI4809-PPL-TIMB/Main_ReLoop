<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MidtransSnapTokenTest extends TestCase
{
    use RefreshDatabase;

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
            'services.midtrans.server_key'    => 'SB-Mid-server-test-key',
            'services.midtrans.client_key'    => 'SB-Mid-client-test-key',
            'services.midtrans.is_production' => false,
        ]);

        $this->seller = User::factory()->create([
            'email_verified_at' => now(),
            'name'              => 'Penjual ReLoop',
            'email'             => 'penjual@reloop.test',
        ]);
        $this->seller->profile()->create();

        $this->buyer = User::factory()->create([
            'email_verified_at' => now(),
            'name'              => 'Pembeli ReLoop',
            'email'             => 'pembeli@reloop.test',
        ]);
        $this->buyer->profile()->create();

        $category = Category::where('type', 'bekas')->first();

        $this->listing = Listing::create([
            'user_id'       => $this->seller->id,
            'category_id'   => $category->id,
            'title'         => 'Monitor Dell 24 Inch Bekas',
            'slug'          => 'monitor-dell-24-inch-bekas',
            'description'   => 'Kondisi mulus normal no minus.',
            'price'         => 1500000,
            'condition'     => 'baik',
            'status'        => Listing::DIPESAN,
            'is_negotiable' => true,
        ]);

        $this->order = Order::create([
            'order_number'     => Order::generateOrderNumber(),
            'listing_id'       => $this->listing->id,
            'buyer_id'         => $this->buyer->id,
            'seller_id'        => $this->seller->id,
            'agreed_price'     => 1500000,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Kebon Jeruk No. 10',
            'status'           => Order::STATUS_MENUNGGU_BAYAR,
        ]);

        $this->payment = Payment::create([
            'order_id'     => $this->order->id,
            'method'       => 'qris',
            'amount'       => 1500000,
            'platform_fee' => 45000,
            'status'       => Payment::STATUS_MENUNGGU,
            'expired_at'   => now()->addHours(24),
        ]);
    }

    public function test_pembeli_dapat_membuat_snap_token_midtrans(): void
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token'        => 'mock-snap-token-abc-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-abc-123',
            ], 201),
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson(route('order.snap_token', $this->order->order_number));

        $response->assertOk()
            ->assertJson([
                'token'        => 'mock-snap-token-abc-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-abc-123',
                'client_key'   => 'SB-Mid-client-test-key',
            ]);

        $this->assertDatabaseHas('payments', [
            'id'                => $this->payment->id,
            'snap_token'        => 'mock-snap-token-abc-123',
            'snap_redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-abc-123',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
                && $request['transaction_details']['order_id'] === $this->order->order_number
                && $request['transaction_details']['gross_amount'] === 1500000;
        });
    }

    public function test_token_yang_sudah_ada_digunakan_kembali_tanpa_request_ke_midtrans(): void
    {
        $this->payment->update([
            'snap_token'        => 'cached-snap-token-777',
            'snap_redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/cached-snap-token-777',
        ]);

        Http::fake();

        $response = $this->actingAs($this->buyer)
            ->postJson(route('order.snap_token', $this->order->order_number));

        $response->assertOk()
            ->assertJson([
                'token'        => 'cached-snap-token-777',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/cached-snap-token-777',
                'client_key'   => 'SB-Mid-client-test-key',
            ]);

        Http::assertNothingSent();
    }

    public function test_bukan_pembeli_dilarang_meminta_snap_token(): void
    {
        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $resPenjual = $this->actingAs($this->seller)
            ->postJson(route('order.snap_token', $this->order->order_number));
        $resPenjual->assertForbidden();

        $resOther = $this->actingAs($otherUser)
            ->postJson(route('order.snap_token', $this->order->order_number));
        $resOther->assertForbidden();
    }

    public function test_pesanan_yang_sudah_bukan_menunggu_bayar_ditolak(): void
    {
        $this->order->update(['status' => Order::STATUS_DIBAYAR]);

        $response = $this->actingAs($this->buyer)
            ->postJson(route('order.snap_token', $this->order->order_number));

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Pesanan ini tidak dalam status menunggu pembayaran.',
            ]);
    }

    public function test_penanganan_error_ketika_midtrans_gagal(): void
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'error_messages' => ['Server key is invalid'],
            ], 401),
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson(route('order.snap_token', $this->order->order_number));

        $response->assertStatus(502)
            ->assertJsonStructure(['message']);
    }
}
