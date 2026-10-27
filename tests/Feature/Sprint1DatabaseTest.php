<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Models\Message;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Review;
use App\Models\User;
use App\Models\WilayahKabupaten;
use App\Models\WilayahKecamatan;
use App\Models\WilayahKelurahan;
use App\Models\WilayahProvinsi;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Sprint1DatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji 1: Semua tabel Sprint 1 (16 tabel) harus terbuat oleh migrasi.
     */
    public function test_all_sprint1_tables_are_created_by_migrations(): void
    {
        $expectedTables = [
            'users',
            'profiles',
            'wilayah_provinsi',
            'wilayah_kabupaten',
            'wilayah_kecamatan',
            'wilayah_kelurahan',
            'categories',
            'listings',
            'listing_photos',
            'conversations',
            'messages',
            'offers',
            'orders',
            'payments',
            'reviews',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Tabel '{$table}' belum terbuat oleh migrasi."
            );
        }

        // Cek kolom phone di users
        $this->assertTrue(Schema::hasColumn('users', 'phone'));
    }

    /**
     * Uji 2: WilayahSeeder mengisi data Jawa Barat, Kab/Kota Bogor, Depok, dan kelurahan.
     */
    public function test_wilayah_seeder_populates_regions_correctly(): void
    {
        $this->seed(WilayahSeeder::class);

        // Provinsi Jawa Barat
        $jabar = WilayahProvinsi::where('nama', 'Jawa Barat')->first();
        $this->assertNotNull($jabar);
        $this->assertSame('32', $jabar->kode);

        // Kabupaten/Kota
        $this->assertDatabaseHas('wilayah_kabupaten', [
            'provinsi_id' => $jabar->id,
            'nama'        => 'Kabupaten Bogor',
        ]);
        $this->assertDatabaseHas('wilayah_kabupaten', [
            'provinsi_id' => $jabar->id,
            'nama'        => 'Kota Bogor',
        ]);
        $this->assertDatabaseHas('wilayah_kabupaten', [
            'provinsi_id' => $jabar->id,
            'nama'        => 'Kota Depok',
        ]);

        // Kecamatan & Kelurahan
        $kecBeji = WilayahKecamatan::where('nama', 'Beji')->first();
        $this->assertNotNull($kecBeji);
        $this->assertGreaterThan(0, $kecBeji->kelurahan()->count());

        $this->assertDatabaseHas('wilayah_kelurahan', [
            'kecamatan_id' => $kecBeji->id,
            'nama'         => 'Pondok Cina',
        ]);
    }

    /**
     * Uji 3: CategorySeeder mengisi tepat 7 kategori barang bekas sesuai kriteria penerimaan.
     */
    public function test_category_seeder_populates_seven_categories(): void
    {
        $this->seed(CategorySeeder::class);

        $expectedCategories = [
            'perabot'    => 'Perabot',
            'pakaian'    => 'Pakaian',
            'buku'       => 'Buku',
            'elektronik' => 'Elektronik',
            'mainan'     => 'Mainan',
            'sepeda'     => 'Sepeda',
            'gadget'     => 'Gadget',
        ];

        $this->assertSame(7, Category::count());

        foreach ($expectedCategories as $slug => $name) {
            $this->assertDatabaseHas('categories', [
                'slug' => $slug,
                'name' => $name,
            ]);
        }
    }

    /**
     * Uji 4: DatabaseSeeder membuat akun demo pembeli dan penjual dengan profil & listing.
     */
    public function test_database_seeder_creates_demo_accounts_with_profiles(): void
    {
        $this->seed();

        // 1. Akun Pembeli
        $buyer = User::where('email', 'pembeli@reloop.test')->first();
        $this->assertNotNull($buyer, 'Akun demo pembeli tidak ditemukan.');
        $this->assertTrue(Hash::check('password', $buyer->password));
        $this->assertNotNull($buyer->profile, 'Profil pembeli tidak terbuat.');

        // 2. Akun Penjual
        $seller = User::where('email', 'penjual@reloop.test')->first();
        $this->assertNotNull($seller, 'Akun demo penjual tidak ditemukan.');
        $this->assertTrue(Hash::check('password', $seller->password));
        $this->assertNotNull($seller->profile, 'Profil penjual tidak terbuat.');
        $this->assertEquals('4.80', $seller->profile->avg_rating);

        // 3. Listing contoh milik penjual
        $this->assertGreaterThanOrEqual(1, $seller->listings()->count());
    }

    /**
     * Uji 5: Relasi antar Model Eloquent berfungsi semestinya.
     */
    public function test_eloquent_relationships_work(): void
    {
        $this->seed();

        $seller = User::where('email', 'penjual@reloop.test')->first();
        $buyer  = User::where('email', 'pembeli@reloop.test')->first();
        $listing = $seller->listings()->first();

        // Listing -> User & Category
        $this->assertSame($seller->id, $listing->user->id);
        $this->assertNotNull($listing->category);

        // ListingPhoto
        $photo = ListingPhoto::create([
            'listing_id' => $listing->id,
            'path'       => 'listings/sample-1.jpg',
            'sort_order' => 0,
        ]);
        $this->assertSame($listing->id, $photo->listing->id);
        $this->assertCount(1, $listing->photos);

        // Conversation & Message
        $conversation = Conversation::create([
            'listing_id' => $listing->id,
            'buyer_id'   => $buyer->id,
            'seller_id'  => $seller->id,
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $buyer->id,
            'body'            => 'Halo, apakah barang ini masih ada?',
        ]);
        $this->assertSame($conversation->id, $message->conversation->id);
        $this->assertCount(1, $conversation->messages);

        // Offer
        $offer = Offer::create([
            'conversation_id' => $conversation->id,
            'offerer_id'      => $buyer->id,
            'amount'          => 3200000,
            'status'          => 'menunggu',
        ]);
        $this->assertSame($conversation->id, $offer->conversation->id);

        // Order & Payment (Escrow)
        $order = Order::create([
            'order_number'    => 'RL-20261027-0001',
            'listing_id'      => $listing->id,
            'buyer_id'        => $buyer->id,
            'seller_id'       => $seller->id,
            'agreed_price'    => 3200000,
            'delivery_method' => 'cod',
            'status'          => 'menunggu_bayar',
        ]);
        $payment = Payment::create([
            'order_id'     => $order->id,
            'method'       => 'qris',
            'amount'       => 3296000,
            'platform_fee' => 96000, // 3% escrow fee
            'status'       => 'menunggu',
        ]);
        $this->assertSame($order->id, $payment->order->id);
        $this->assertSame($payment->id, $order->payment->id);

        // Review
        $review = Review::create([
            'order_id'    => $order->id,
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'listing_id'  => $listing->id,
            'rating'      => 5,
            'body'        => 'Barang mulus sesuai deskripsi, penjual ramah!',
        ]);
        $this->assertSame($order->id, $review->order->id);
        $this->assertSame($seller->id, $review->reviewee->id);
    }
}
