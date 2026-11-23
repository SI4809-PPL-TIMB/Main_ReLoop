<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Profile;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 10.1.4 — Testing fitur Rating dan Ulasan Penjual (PPLTIMB-148).
 *
 * Kriteria Penerimaan:
 * 1. Rating 1–5 dan ulasan bisa diberikan satu kali per pesanan selesai.
 * 2. Rating rata-rata dan jumlah ulasan tampil di kartu penjual (detail barang) dan profil penjual.
 * 3. Pesanan yang belum selesai tidak bisa diulas.
 */
class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $penjual;
    private User $pembeli;
    private User $penggunaLain;
    private Listing $listing;
    private Order $orderSelesai;
    private Order $orderBelumSelesai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CategorySeeder::class);

        $this->penjual = User::factory()->create(['name' => 'Budi Penjual']);
        Profile::create([
            'user_id'    => $this->penjual->id,
            'avg_rating' => 0,
            'total_sold' => 1,
        ]);

        $this->pembeli = User::factory()->create(['name' => 'Siti Pembeli']);
        Profile::create([
            'user_id' => $this->pembeli->id,
        ]);

        $this->penggunaLain = User::factory()->create(['name' => 'Agus Lain']);
        Profile::create([
            'user_id' => $this->penggunaLain->id,
        ]);

        $kategori = Category::where('type', Category::TYPE_BEKAS)->first() ?? Category::first();

        $this->listing = Listing::create([
            'user_id'       => $this->penjual->id,
            'category_id'   => $kategori->id,
            'title'         => 'Sepeda Lipat Evergreen',
            'description'   => 'Kondisi mulus terawat.',
            'price'         => 850000,
            'is_negotiable' => true,
            'condition'     => 'baik',
            'status'        => Listing::AKTIF,
            'published_at'  => now(),
        ]);

        // Pesanan yang sudah selesai (bisa diulas)
        $this->orderSelesai = Order::create([
            'order_number'       => Order::generateOrderNumber(),
            'listing_id'         => $this->listing->id,
            'buyer_id'           => $this->pembeli->id,
            'seller_id'          => $this->penjual->id,
            'agreed_price'       => 850000,
            'delivery_method'    => Order::DELIVERY_COD,
            'status'             => Order::STATUS_SELESAI,
            'buyer_confirmed_at' => now(),
        ]);

        // Pesanan yang belum selesai (menunggu bayar atau diserahkan)
        $this->orderBelumSelesai = Order::create([
            'order_number'    => Order::generateOrderNumber(),
            'listing_id'      => $this->listing->id,
            'buyer_id'        => $this->pembeli->id,
            'seller_id'       => $this->penjual->id,
            'agreed_price'    => 850000,
            'delivery_method' => Order::DELIVERY_COD,
            'status'          => Order::STATUS_DIBAYAR,
        ]);
    }

    public function test_guest_diarahkan_ke_login_saat_mengakses_form_ulasan(): void
    {
        $response = $this->get(route('ulasan.create', $this->orderSelesai->order_number));
        $response->assertRedirect('/masuk');

        $responsePost = $this->post(route('ulasan.store', $this->orderSelesai->order_number), [
            'rating' => 5,
        ]);
        $responsePost->assertRedirect('/masuk');
    }

    public function test_pembeli_dapat_melihat_halaman_form_ulasan_untuk_pesanan_selesai(): void
    {
        $response = $this->actingAs($this->pembeli)
            ->get(route('ulasan.create', $this->orderSelesai->order_number));

        $response->assertOk();
        $response->assertSee('Beri Ulasan');
        $response->assertSee('Sepeda Lipat Evergreen');
        $response->assertSee('Budi Penjual');
    }

    public function test_pengguna_lain_tidak_dapat_mengakses_form_ulasan(): void
    {
        $response = $this->actingAs($this->penggunaLain)
            ->get(route('ulasan.create', $this->orderSelesai->order_number));

        $response->assertForbidden();
    }

    public function test_penjual_tidak_dapat_mengulas_pesanan_miliknya_sendiri(): void
    {
        $response = $this->actingAs($this->penjual)
            ->get(route('ulasan.create', $this->orderSelesai->order_number));

        $response->assertForbidden();
    }

    public function test_pesanan_yang_belum_selesai_tidak_bisa_diulas(): void
    {
        // 1. Akses halaman formulir ulasan untuk pesanan belum selesai
        $responseGet = $this->actingAs($this->pembeli)
            ->get(route('ulasan.create', $this->orderBelumSelesai->order_number));

        $responseGet->assertRedirect(route('order.show', $this->orderBelumSelesai->order_number));
        $responseGet->assertSessionHas('error', 'Ulasan hanya dapat diberikan setelah pesanan selesai.');

        // 2. Submit form ulasan untuk pesanan belum selesai
        $responsePost = $this->actingAs($this->pembeli)
            ->post(route('ulasan.store', $this->orderBelumSelesai->order_number), [
                'rating' => 5,
                'body'   => 'Barang bagus sekali',
            ]);

        $responsePost->assertRedirect(route('order.show', $this->orderBelumSelesai->order_number));
        $responsePost->assertSessionHas('error', 'Pesanan belum selesai. Ulasan belum dapat dikirim.');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_pembeli_berhasil_mengirim_rating_dan_ulasan(): void
    {
        $response = $this->actingAs($this->pembeli)
            ->post(route('ulasan.store', $this->orderSelesai->order_number), [
                'rating' => 5,
                'body'   => 'Pelayanan ramah dan sepeda sangat mulus!',
            ]);

        $response->assertRedirect(route('order.show', $this->orderSelesai->order_number));
        $response->assertSessionHas('success', 'Terima kasih! Ulasanmu berhasil dikirim.');

        $this->assertDatabaseHas('reviews', [
            'order_id'    => $this->orderSelesai->id,
            'reviewer_id' => $this->pembeli->id,
            'reviewee_id' => $this->penjual->id,
            'listing_id'  => $this->listing->id,
            'rating'      => 5,
            'body'        => 'Pelayanan ramah dan sepeda sangat mulus!',
        ]);

        // Cek update profil penjual avg_rating menjadi 5.0
        $this->assertEquals(5.0, (float) $this->penjual->profile->fresh()->avg_rating);
    }

    public function test_ulasan_hanya_bisa_diberikan_satu_kali_per_pesanan_selesai(): void
    {
        // Ulasan pertama
        $this->actingAs($this->pembeli)
            ->post(route('ulasan.store', $this->orderSelesai->order_number), [
                'rating' => 4,
                'body'   => 'Ulasan pertama',
            ]);

        $this->assertDatabaseCount('reviews', 1);

        // Akses create lagi ditolak
        $responseGet = $this->actingAs($this->pembeli)
            ->get(route('ulasan.create', $this->orderSelesai->order_number));
        $responseGet->assertRedirect(route('order.show', $this->orderSelesai->order_number));
        $responseGet->assertSessionHas('info');

        // Ulasan kedua pada pesanan yang sama ditolak
        $responsePost = $this->actingAs($this->pembeli)
            ->post(route('ulasan.store', $this->orderSelesai->order_number), [
                'rating' => 5,
                'body'   => 'Ulasan kedua coba-coba',
            ]);

        $responsePost->assertRedirect(route('order.show', $this->orderSelesai->order_number));
        $responsePost->assertSessionHas('error');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_validasi_rating_harus_antara_1_sampai_5(): void
    {
        $this->actingAs($this->pembeli);

        // Rating kosong
        $this->post(route('ulasan.store', $this->orderSelesai->order_number), [
            'rating' => '',
        ])->assertSessionHasErrors(['rating']);

        // Rating 0
        $this->post(route('ulasan.store', $this->orderSelesai->order_number), [
            'rating' => 0,
        ])->assertSessionHasErrors(['rating']);

        // Rating lebih dari 5
        $this->post(route('ulasan.store', $this->orderSelesai->order_number), [
            'rating' => 6,
        ])->assertSessionHasErrors(['rating']);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_validasi_body_ulasan_maksimal_500_karakter(): void
    {
        $this->actingAs($this->pembeli);

        $teksPanjang = str_repeat('a', 501);

        $this->post(route('ulasan.store', $this->orderSelesai->order_number), [
            'rating' => 5,
            'body'   => $teksPanjang,
        ])->assertSessionHasErrors(['body']);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_rating_rata_rata_dihitung_secara_tepat_dari_beberapa_ulasan(): void
    {
        // Pembeli 1 mengulas rating 5
        Review::create([
            'order_id'    => $this->orderSelesai->id,
            'reviewer_id' => $this->pembeli->id,
            'reviewee_id' => $this->penjual->id,
            'rating'      => 5,
        ]);

        // Buat pesanan selesai kedua oleh pembeli lain
        $order2 = Order::create([
            'order_number'       => Order::generateOrderNumber(),
            'listing_id'         => $this->listing->id,
            'buyer_id'           => $this->penggunaLain->id,
            'seller_id'          => $this->penjual->id,
            'agreed_price'       => 850000,
            'delivery_method'    => Order::DELIVERY_COD,
            'status'             => Order::STATUS_SELESAI,
            'buyer_confirmed_at' => now(),
        ]);

        // Pembeli 2 mengulas rating 4 lewat endpoint
        $this->actingAs($this->penggunaLain)
            ->post(route('ulasan.store', $order2->order_number), [
                'rating' => 4,
            ]);

        // Rata-rata: (5 + 4) / 2 = 4.5
        $this->assertEquals(4.5, (float) $this->penjual->profile->fresh()->avg_rating);
    }

    public function test_rating_dan_jumlah_ulasan_tampil_di_profil_penjual(): void
    {
        // Buat ulasan untuk penjual
        Review::create([
            'order_id'    => $this->orderSelesai->id,
            'reviewer_id' => $this->pembeli->id,
            'reviewee_id' => $this->penjual->id,
            'rating'      => 5,
            'body'        => 'Penjual terbaik se-kabupaten!',
        ]);

        $this->penjual->profile->update(['avg_rating' => 5.0]);

        $response = $this->actingAs($this->pembeli)
            ->get(route('penjual.show', $this->penjual));

        $response->assertOk();
        $response->assertSee('5,0');
        $response->assertSee('1 ulasan');
        $response->assertSee('Penjual terbaik se-kabupaten!');
    }

    public function test_rating_rata_rata_tampil_di_kartu_penjual_detail_barang(): void
    {
        // Kondisi 1: Belum ada ulasan
        $this->penjual->profile->update(['avg_rating' => 0]);
        $responseBelum = $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $this->listing));
        $responseBelum->assertOk();
        $responseBelum->assertSee('Belum ada ulasan');

        // Kondisi 2: Sudah ada ulasan rating 4.8
        $this->penjual->profile->update(['avg_rating' => 4.8]);
        $responseSudah = $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $this->listing));
        $responseSudah->assertOk();
        $responseSudah->assertSee('4,8');
        $responseSudah->assertSee('Rating');
    }
}
