<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ListingSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** 5.1.6 · Pagination dan index database (target < 3 detik / NFR-01). */
class PencarianPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;
    private WilayahKelurahan $kelurahan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Seed data lengkap sesuai lingkungan produksi/demo
        $this->seed([
            WilayahSeeder::class,
            CategorySeeder::class,
        ]);

        $this->kelurahan = WilayahKelurahan::where('nama', 'Beji')->firstOrFail();

        $this->pembeli = User::factory()->create();
        $this->pembeli->profile()->create([
            'kelurahan_id' => $this->kelurahan->id,
            'latitude'     => -6.3700,
            'longitude'    => 106.8200,
        ]);

        $penjual = User::factory()->create([
            'email' => 'penjual@reloop.test',
        ]);
        $penjual->profile()->create([
            'kelurahan_id' => $this->kelurahan->id,
            'latitude'     => -6.3750,
            'longitude'    => 106.8250,
        ]);

        $this->seed(ListingSeeder::class);
    }

    public function test_index_pencarian_tersedia_di_tabel_listings(): void
    {
        $this->assertTrue(Schema::hasTable('listings'));

        // Ambil daftar index yang terdaftar di tabel listings
        $indexes = collect(Schema::getIndexes('listings'))->pluck('name')->all();

        $this->assertTrue(
            in_array('listings_status_category_id_index', $indexes, true) ||
            in_array('listings_category_id_index', $indexes, true)
        );
        $this->assertContains('listings_status_price_index', $indexes);
        $this->assertContains('listings_status_kelurahan_id_index', $indexes);
        $this->assertContains('listings_status_published_at_index', $indexes);
        $this->assertContains('listings_title_index', $indexes);
    }

    public function test_pagination_hasil_pencarian_dan_preservasi_filter(): void
    {
        $kat = Category::where('type', Category::TYPE_BEKAS)->firstOrFail();

        // Buat 25 barang tambahan untuk menguji paginasi 12 item per halaman
        for ($i = 1; $i <= 25; $i++) {
            Listing::create([
                'user_id'      => $this->pembeli->id,
                'category_id'  => $kat->id,
                'kelurahan_id' => $this->kelurahan->id,
                'title'        => "Barang Paginasi Ke-{$i}",
                'description'  => "Deskripsi barang paginasi {$i}",
                'price'        => 50000 + ($i * 1000),
                'condition'    => 'baik',
                'status'       => Listing::AKTIF,
                'published_at' => now()->subMinutes($i),
            ]);
        }

        // Halaman 1
        $resPage1 = $this->actingAs($this->pembeli)->get('/cari?q=Paginasi&kategori='.$kat->slug);
        $resPage1->assertOk()
            ->assertSee('Barang Paginasi Ke-1')
            ->assertSee('page=2'); // link ke halaman 2 ada

        // Halaman 2
        $resPage2 = $this->actingAs($this->pembeli)->get('/cari?q=Paginasi&kategori='.$kat->slug.'&page=2');
        $resPage2->assertOk()
            ->assertSee('Barang Paginasi Ke-13')
            ->assertSee('page=3');

        // Parameter pencarian & filter harus tetap dipertahankan pada tautan paginasi
        $content = $resPage2->getContent();
        $this->assertStringContainsString('q=Paginasi', $content);
        $this->assertStringContainsString('kategori='.$kat->slug, $content);
    }

    public function test_nfr01_waktu_muat_beranda_kurang_dari_3_detik(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->pembeli)->get('/beranda');

        $durasi = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(3.0, $durasi, "Waktu muat Beranda ({$durasi} detik) melebihi batas 3 detik (NFR-01)");
    }

    public function test_nfr01_waktu_muat_pencarian_dasar_kurang_dari_3_detik(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->pembeli)->get('/cari');

        $durasi = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(3.0, $durasi, "Waktu muat pencarian ({$durasi} detik) melebihi batas 3 detik (NFR-01)");
    }

    public function test_nfr01_waktu_muat_pencarian_kata_kunci_kurang_dari_3_detik(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->pembeli)->get('/cari?q=Kursi');

        $durasi = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(3.0, $durasi, "Waktu muat pencarian kata kunci ({$durasi} detik) melebihi batas 3 detik (NFR-01)");
    }

    public function test_nfr01_waktu_muat_filter_kategori_dan_harga_kurang_dari_3_detik(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->pembeli)->get('/cari?kategori=perabot&min_harga=100000&max_harga=500000');

        $durasi = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(3.0, $durasi, "Waktu muat filter kategori & harga ({$durasi} detik) melebihi batas 3 detik (NFR-01)");
    }

    public function test_nfr01_waktu_muat_filter_wilayah_kurang_dari_3_detik(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->pembeli)->get('/cari?kelurahan_id='.$this->kelurahan->id);

        $durasi = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(3.0, $durasi, "Waktu muat filter wilayah ({$durasi} detik) melebihi batas 3 detik (NFR-01)");
    }

    public function test_nfr01_waktu_muat_kombinasi_seluruh_filter_kurang_dari_3_detik(): void
    {
        $start = microtime(true);

        $url = '/cari?q=Kayu&kategori=perabot&min_harga=50000&max_harga=500000&kelurahan_id='.$this->kelurahan->id;
        $response = $this->actingAs($this->pembeli)->get($url);

        $durasi = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(3.0, $durasi, "Waktu muat kombinasi seluruh filter ({$durasi} detik) melebihi batas 3 detik (NFR-01)");
    }
}

