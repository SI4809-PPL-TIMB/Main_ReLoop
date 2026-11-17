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

class OrderStatusViewTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $seller;
    private User $otherUser;
    private Listing $listing;
    private Order $order;
    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CategorySeeder::class);

        $this->seller = User::factory()->create([
            'name'  => 'Siti Rahma',
            'email' => 'siti@reloop.test',
        ]);
        $this->seller->profile()->create();

        $this->buyer = User::factory()->create([
            'name'  => 'Ahmad Pembeli',
            'email' => 'ahmad@reloop.test',
        ]);
        $this->buyer->profile()->create();

        $this->otherUser = User::factory()->create([
            'name'  => 'Orang Lain',
            'email' => 'lain@reloop.test',
        ]);
        $this->otherUser->profile()->create();

        $category = Category::where('type', 'bekas')->first();

        $this->listing = Listing::create([
            'user_id'       => $this->seller->id,
            'category_id'   => $category->id,
            'title'         => 'Kursi Kayu Jati Ukir',
            'slug'          => 'kursi-kayu-jati-ukir',
            'description'   => 'Kondisi mulus terawat',
            'price'         => 350000,
            'condition'     => 'baik',
            'status'        => Listing::DIPESAN,
            'is_negotiable' => true,
        ]);

        $this->order = Order::create([
            'order_number'     => Order::generateOrderNumber(),
            'listing_id'       => $this->listing->id,
            'buyer_id'         => $this->buyer->id,
            'seller_id'        => $this->seller->id,
            'agreed_price'     => 350000,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Pandu Raya No. 12',
            'status'           => Order::STATUS_MENUNGGU_BAYAR,
        ]);

        $this->payment = Payment::create([
            'order_id'     => $this->order->id,
            'method'       => 'va',
            'bank'         => 'bca',
            'va_number'    => '8077081234567890',
            'amount'       => 350000,
            'platform_fee' => 10500,
            'status'       => Payment::STATUS_MENUNGGU,
            'expired_at'   => now()->addHours(24),
        ]);
    }

    public function test_halaman_status_menampilkan_state_sukses_ketika_order_dibayar(): void
    {
        $this->order->update(['status' => Order::STATUS_DIBAYAR]);
        $this->payment->update([
            'status'  => Payment::STATUS_BERHASIL,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->buyer)
            ->get(route('order.status', $this->order->order_number));

        $response->assertOk()
            ->assertSee('Pembayaran berhasil')
            ->assertSee('Dana ditahan Rekber Reloop')
            ->assertSee($this->order->order_number)
            ->assertSee('Chat Penjual untuk Janjian')
            ->assertSee('Lihat Detail Pesanan');
    }

    public function test_halaman_status_menampilkan_state_menunggu_ketika_order_menunggu_bayar(): void
    {
        $response = $this->actingAs($this->buyer)
            ->get(route('order.status', $this->order->order_number));

        $response->assertOk()
            ->assertSee('Menunggu pembayaran')
            ->assertSee($this->order->order_number)
            ->assertSee('Lanjut bayar')
            ->assertSee('Cek status pembayaran');
    }

    public function test_halaman_status_menampilkan_state_gagal_ketika_order_dibatalkan(): void
    {
        $this->order->update([
            'status'       => Order::STATUS_DIBATALKAN,
            'cancelled_at' => now(),
        ]);
        $this->payment->update(['status' => Payment::STATUS_KEDALUWARSA]);

        $response = $this->actingAs($this->buyer)
            ->get(route('order.status', $this->order->order_number));

        $response->assertOk()
            ->assertSee('Pesanan dibatalkan')
            ->assertSee('Batas bayar 24 jam terlewat')
            ->assertSee('Cari barang lain')
            ->assertSee('Lihat barang ini lagi');
    }

    public function test_user_lain_dilarang_mengakses_halaman_status_pesanan(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->get(route('order.status', $this->order->order_number));

        $response->assertForbidden();
    }

    public function test_halaman_status_bisa_merespons_json_untuk_polling(): void
    {
        $response = $this->actingAs($this->buyer)
            ->getJson(route('order.status', $this->order->order_number));

        $response->assertOk()
            ->assertJson([
                'order_number' => $this->order->order_number,
                'status'       => Order::STATUS_MENUNGGU_BAYAR,
                'is_paid'      => false,
            ]);
    }

    public function test_halaman_status_sinkronisasi_otomatis_dengan_midtrans_dan_mengembalikan_is_paid(): void
    {
        $this->payment->update(['snap_token' => 'mock-token']);

        $midtransMock = $this->mock(\App\Services\MidtransService::class);
        $midtransMock->shouldReceive('syncOrderWithMidtrans')
            ->once()
            ->withArgs(function ($order) {
                return $order->id === $this->order->id;
            })
            ->andReturnUsing(function ($order) {
                $order->update(['status' => Order::STATUS_DIBAYAR]);
                $this->payment->update(['status' => Payment::STATUS_BERHASIL]);
                return $order;
            });

        $response = $this->actingAs($this->buyer)
            ->getJson(route('order.status', $this->order->order_number));

        $response->assertOk()
            ->assertJson([
                'order_number' => $this->order->order_number,
                'status'       => Order::STATUS_DIBAYAR,
                'is_paid'      => true,
            ]);
    }
}
