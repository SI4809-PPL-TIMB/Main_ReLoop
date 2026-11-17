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
use Tests\TestCase;

class CheckoutSnapViewTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $seller;
    private Listing $listing;

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
        $this->buyer->profile()->create([
            'patokan' => 'Jl. Pandu Raya No. 12',
        ]);

        $category = Category::where('type', 'bekas')->first();

        $this->listing = Listing::create([
            'user_id'       => $this->seller->id,
            'category_id'   => $category->id,
            'title'         => 'Kursi Kayu Jati Ukir',
            'slug'          => 'kursi-kayu-jati-ukir',
            'description'   => 'Kondisi mulus terawat',
            'price'         => 350000,
            'condition'     => 'baik',
            'status'        => Listing::AKTIF,
            'is_negotiable' => true,
        ]);
    }

    public function test_guest_diarahkan_ke_login_saat_akses_checkout(): void
    {
        $response = $this->get(route('order.checkout', $this->listing));
        $response->assertRedirect('/masuk');
    }

    public function test_penjual_tidak_bisa_mengakses_checkout_barangnya_sendiri(): void
    {
        $response = $this->actingAs($this->seller)
            ->get(route('order.checkout', $this->listing));

        $response->assertRedirect(route('barang.detail', $this->listing))
            ->assertSessionHas('error');
    }

    public function test_pembeli_bisa_mengakses_checkout_dengan_harga_normal(): void
    {
        $response = $this->actingAs($this->buyer)
            ->get(route('order.checkout', $this->listing));

        $response->assertOk()
            ->assertSee('Checkout')
            ->assertSee('Kursi Kayu Jati Ukir')
            ->assertSee('Siti Rahma')
            ->assertSee('Rp 350.000')
            ->assertSee('Bayar sekarang')
            ->assertSee('Cara serah terima');
    }

    public function test_checkout_menggunakan_harga_tawaran_disepakati(): void
    {
        $conv = Conversation::create([
            'listing_id' => $this->listing->id,
            'buyer_id'   => $this->buyer->id,
            'seller_id'  => $this->seller->id,
        ]);

        Offer::create([
            'conversation_id' => $conv->id,
            'offerer_id'      => $this->buyer->id,
            'amount'          => 320000,
            'status'          => 'diterima',
        ]);

        $response = $this->actingAs($this->buyer)
            ->get(route('order.checkout', $this->listing));

        $response->assertOk()
            ->assertSee('Rp 320.000')
            ->assertSee('Harga tawaran disepakati');
    }

    public function test_barang_yang_sudah_dipesan_tidak_bisa_dicheckout(): void
    {
        $this->listing->update(['status' => Listing::DIPESAN]);

        $response = $this->actingAs($this->buyer)
            ->get(route('order.checkout', $this->listing));

        $response->assertRedirect(route('barang.detail', $this->listing))
            ->assertSessionHas('error');
    }

    public function test_tombol_beli_sekarang_di_detail_barang_mengarah_ke_checkout(): void
    {
        $response = $this->actingAs($this->buyer)
            ->get(route('barang.detail', $this->listing));

        $response->assertOk()
            ->assertSee(route('order.checkout', $this->listing));
    }

    public function test_detail_pesanan_menampilkan_tombol_bayar_sekarang_untuk_pembeli(): void
    {
        $order = Order::create([
            'order_number'     => Order::generateOrderNumber(),
            'listing_id'       => $this->listing->id,
            'buyer_id'         => $this->buyer->id,
            'seller_id'        => $this->seller->id,
            'agreed_price'     => 350000,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Pandu Raya',
            'status'           => Order::STATUS_MENUNGGU_BAYAR,
        ]);

        Payment::create([
            'order_id'     => $order->id,
            'method'       => 'qris',
            'amount'       => 350000,
            'platform_fee' => 10500,
            'status'       => Payment::STATUS_MENUNGGU,
            'expired_at'   => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->buyer)
            ->get(route('order.show', $order->order_number));

        $response->assertOk()
            ->assertSee('Bayar Sekarang')
            ->assertSee('snap.js');
    }
}
