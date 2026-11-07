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
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 7.1.3 — Testing backend order menunggu bayar dan kunci barang (PPLTIMB-95).
 */
class OrderBackendTest extends TestCase
{
    use RefreshDatabase;

    private User $penjual;
    private User $pembeli;
    private User $userLain;
    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);

        $this->penjual = User::factory()->create(['name' => 'Penjual ReLoop']);
        $this->penjual->profile()->create();

        $this->pembeli = User::factory()->create(['name' => 'Pembeli ReLoop']);
        $this->pembeli->profile()->create();

        $this->userLain = User::factory()->create(['name' => 'User Orang Lain']);
        $this->userLain->profile()->create();

        $kategori = Category::where('type', 'bekas')->first();

        $this->listing = Listing::create([
            'user_id'       => $this->penjual->id,
            'category_id'   => $kategori->id,
            'title'         => 'Kursi Kayu Jati Ukir',
            'description'   => 'Kondisi mulus terawat',
            'price'         => 350000,
            'condition'     => 'baik',
            'status'        => Listing::AKTIF,
            'is_negotiable' => true,
        ]);
    }

    public function test_guest_tidak_bisa_membuat_order(): void
    {
        $response = $this->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Pandu Raya No. 12',
        ]);

        $response->assertUnauthorized();
    }

    public function test_pembeli_berhasil_membuat_order_dan_barang_langsung_terkunci_dipesan(): void
    {
        $response = $this->actingAs($this->pembeli)->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Pandu Raya No. 12, Ciomas',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Pesanan berhasil dibuat, barang telah dikunci.');

        // 1. Verifikasi Order tersimpan di database dengan status menunggu_bayar
        $this->assertDatabaseHas('orders', [
            'listing_id'       => $this->listing->id,
            'buyer_id'         => $this->pembeli->id,
            'seller_id'        => $this->penjual->id,
            'agreed_price'     => 350000,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Pandu Raya No. 12, Ciomas',
            'status'           => Order::STATUS_MENUNGGU_BAYAR,
        ]);

        $order = Order::where('listing_id', $this->listing->id)->first();
        $this->assertNotNull($order);
        $this->assertMatchesRegularExpression('/^RL-\d{8}-[A-Z0-9]{4}$/', $order->order_number);

        // 2. Verifikasi listing terkunci: status berubah dari aktif menjadi dipesan
        $this->assertEquals(Listing::DIPESAN, $this->listing->fresh()->status);

        // 3. Verifikasi record payment terinisialisasi dengan komisi 3%
        $this->assertDatabaseHas('payments', [
            'order_id'     => $order->id,
            'amount'       => 350000,
            'platform_fee' => 10500, // 3% dari 350.000
            'status'       => Payment::STATUS_MENUNGGU,
        ]);
    }

    public function test_penjual_tidak_bisa_membeli_barangnya_sendiri(): void
    {
        $response = $this->actingAs($this->penjual)->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_AMBIL,
        ]);

        $response->assertForbidden();
        $this->assertEquals(Listing::AKTIF, $this->listing->fresh()->status);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_tidak_bisa_membuat_order_pada_barang_yang_sudah_dipesan(): void
    {
        $this->listing->update(['status' => Listing::DIPESAN]);

        $response = $this->actingAs($this->pembeli)->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_COD,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Barang ini sudah tidak tersedia atau sedang dipesan.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_tidak_bisa_membuat_order_pada_barang_yang_sudah_terjual(): void
    {
        $this->listing->update(['status' => Listing::TERJUAL]);

        $response = $this->actingAs($this->pembeli)->postJson(route('order.store', $this->listing), [
            'delivery_method'  => Order::DELIVERY_AMBIL,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_menggunakan_harga_tawaran_jika_ada_offer_yang_diterima(): void
    {
        // Siapkan percakapan dan penawaran yang sudah diterima penjual
        $conv = Conversation::create([
            'listing_id' => $this->listing->id,
            'buyer_id'   => $this->pembeli->id,
            'seller_id'  => $this->penjual->id,
        ]);

        $offer = Offer::create([
            'conversation_id' => $conv->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 320000,
            'status'          => 'diterima',
        ]);

        $response = $this->actingAs($this->pembeli)->postJson(route('order.store', $this->listing), [
            'delivery_method' => Order::DELIVERY_COD,
            'offer_id'        => $offer->id,
        ]);

        $response->assertCreated();

        // Harga yang disepakati adalah 320.000, bukan harga listing 350.000
        $this->assertDatabaseHas('orders', [
            'listing_id'   => $this->listing->id,
            'agreed_price' => 320000,
        ]);

        // Komisi 3% dari 320.000 = 9.600
        $this->assertDatabaseHas('payments', [
            'amount'       => 320000,
            'platform_fee' => 9600,
        ]);
    }

    public function test_pembeli_dan_penjual_bisa_melihat_detail_pesanan_sedangkan_user_lain_ditolak(): void
    {
        $order = Order::create([
            'order_number'    => Order::generateOrderNumber(),
            'listing_id'      => $this->listing->id,
            'buyer_id'        => $this->pembeli->id,
            'seller_id'       => $this->penjual->id,
            'agreed_price'    => 350000,
            'delivery_method' => Order::DELIVERY_COD,
            'status'          => Order::STATUS_MENUNGGU_BAYAR,
        ]);

        Payment::create([
            'order_id'     => $order->id,
            'amount'       => 350000,
            'platform_fee' => 10500,
            'status'       => Payment::STATUS_MENUNGGU,
        ]);

        // 1. Pembeli bisa akses
        $resBuyer = $this->actingAs($this->pembeli)->getJson(route('order.show', $order->order_number));
        $resBuyer->assertOk();
        $resBuyer->assertJsonPath('order.order_number', $order->order_number);

        // 2. Penjual bisa akses
        $resSeller = $this->actingAs($this->penjual)->getJson(route('order.show', $order->order_number));
        $resSeller->assertOk();

        // 3. User lain ditolak 403 Forbidden
        $resLain = $this->actingAs($this->userLain)->getJson(route('order.show', $order->order_number));
        $resLain->assertForbidden();
    }
}
