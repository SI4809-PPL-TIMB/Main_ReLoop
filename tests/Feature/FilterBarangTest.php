<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 5.1.4 · Filter kategori dan rentang harga, kombinasi dengan kata kunci. */
class FilterBarangTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private int $kelurahanId;
    private Category $katPerabot;
    private Category $katPakaian;
    private Category $katElektronik;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);

        $this->kelurahanId = WilayahKelurahan::first()->id;

        $this->katPerabot = Category::where('slug', 'perabot')->firstOrFail();
        $this->katPakaian = Category::where('slug', 'pakaian')->firstOrFail();
        $this->katElektronik = Category::where('slug', 'elektronik')->firstOrFail();

        $this->user = User::factory()->create();
        $this->user->profile()->create([
            'kelurahan_id' => $this->kelurahanId,
            'latitude'     => -6.3700,
            'longitude'    => 106.8200,
        ]);
    }

    private function buatBarang(string $judul, Category $kategori, int $harga, array $extra = []): Listing
    {
        $penjual = User::factory()->create();
        $penjual->profile()->create([
            'kelurahan_id' => $this->kelurahanId,
            'latitude'     => $extra['lat'] ?? -6.3750,
            'longitude'    => $extra['lng'] ?? 106.8250,
        ]);

        return Listing::create(array_merge([
            'user_id'      => $penjual->id,
            'category_id'  => $kategori->id,
            'kelurahan_id' => $this->kelurahanId,
            'title'        => $judul,
            'description'  => $extra['desc'] ?? 'Barang berkualitas '.$judul,
            'price'        => $harga,
            'condition'    => 'baik',
            'status'       => $extra['status'] ?? Listing::AKTIF,
            'published_at' => now(),
        ], $extra));
    }

    public function test_filter_kategori_berdasarkan_slug(): void
    {
        $this->buatBarang('Meja Belajar Kayu', $this->katPerabot, 150000);
        $this->buatBarang('Jaket Denim Pria', $this->katPakaian, 120000);

        $response = $this->actingAs($this->user)->get('/cari?kategori=perabot');

        $response->assertOk()
            ->assertSee('Meja Belajar Kayu')
            ->assertDontSee('Jaket Denim Pria');
    }

    public function test_filter_kategori_berdasarkan_id(): void
    {
        $this->buatBarang('Setrika Listrik Philips', $this->katElektronik, 90000);
        $this->buatBarang('Kaos Katun Polos', $this->katPakaian, 45000);

        $response = $this->actingAs($this->user)->get('/cari?category_id='.$this->katElektronik->id);

        $response->assertOk()
            ->assertSee('Setrika Listrik Philips')
            ->assertDontSee('Kaos Katun Polos');
    }

    public function test_filter_harga_minimum(): void
    {
        $this->buatBarang('Barang Murah', $this->katPerabot, 30000);
        $this->buatBarang('Barang Sedang', $this->katPerabot, 100000);
        $this->buatBarang('Barang Mahal', $this->katPerabot, 500000);

        $response = $this->actingAs($this->user)->get('/cari?min_harga=100000');

        $response->assertOk()
            ->assertSee('Barang Sedang')
            ->assertSee('Barang Mahal')
            ->assertDontSee('Barang Murah');
    }

    public function test_filter_harga_maksimum(): void
    {
        $this->buatBarang('Barang Murah', $this->katPerabot, 30000);
        $this->buatBarang('Barang Sedang', $this->katPerabot, 100000);
        $this->buatBarang('Barang Mahal', $this->katPerabot, 500000);

        $response = $this->actingAs($this->user)->get('/cari?max_harga=100000');

        $response->assertOk()
            ->assertSee('Barang Murah')
            ->assertSee('Barang Sedang')
            ->assertDontSee('Barang Mahal');
    }

    public function test_filter_rentang_harga_min_dan_maks(): void
    {
        $this->buatBarang('Barang A (25rb)', $this->katPerabot, 25000);
        $this->buatBarang('Barang B (150rb)', $this->katPerabot, 150000);
        $this->buatBarang('Barang C (300rb)', $this->katPerabot, 300000);
        $this->buatBarang('Barang D (750rb)', $this->katPerabot, 750000);

        $response = $this->actingAs($this->user)->get('/cari?min_harga=100000&max_harga=500000');

        $response->assertOk()
            ->assertSee('Barang B (150rb)')
            ->assertSee('Barang C (300rb)')
            ->assertDontSee('Barang A (25rb)')
            ->assertDontSee('Barang D (750rb)');
    }

    public function test_kombinasi_kata_kunci_dengan_filter_kategori(): void
    {
        // Ada 2 barang dengan kata "kayu", tapi beda kategori
        $this->buatBarang('Kursi Kayu Jati', $this->katPerabot, 250000);
        $this->buatBarang('Jam Tangan Kayu', $this->katPakaian, 150000);

        $response = $this->actingAs($this->user)->get('/cari?q=kayu&kategori=perabot');

        $response->assertOk()
            ->assertSee('Kursi Kayu Jati')
            ->assertDontSee('Jam Tangan Kayu');
    }

    public function test_kombinasi_kata_kunci_dengan_rentang_harga(): void
    {
        // Ada 2 kursi, tapi harganya berbeda
        $this->buatBarang('Kursi Murah', $this->katPerabot, 50000);
        $this->buatBarang('Kursi Mewah', $this->katPerabot, 800000);

        $response = $this->actingAs($this->user)->get('/cari?q=Kursi&max_harga=200000');

        $response->assertOk()
            ->assertSee('Kursi Murah')
            ->assertDontSee('Kursi Mewah');
    }

    public function test_kombinasi_lengkap_kata_kunci_kategori_dan_rentang_harga(): void
    {
        // 1. Cocok semua kriteria (Keyword: "Jati", Kategori: Perabot, Harga: 200rb)
        $this->buatBarang('Meja Jati Minimalis', $this->katPerabot, 200000);

        // 2. Keyword cocok, Kategori cocok, tapi Harga terlalu mahal
        $this->buatBarang('Lemari Jati Ukir', $this->katPerabot, 1500000);

        // 3. Keyword cocok, Harga cocok, tapi Kategori salah
        $this->buatBarang('Hiasan Dinding Jati', $this->katPakaian, 180000);

        // 4. Kategori cocok, Harga cocok, tapi Keyword tidak cocok
        $this->buatBarang('Sofa Empuk', $this->katPerabot, 250000);

        $response = $this->actingAs($this->user)->get('/cari?q=Jati&kategori=perabot&min_harga=100000&max_harga=500000');

        $response->assertOk()
            ->assertSee('Meja Jati Minimalis')
            ->assertDontSee('Lemari Jati Ukir')
            ->assertDontSee('Hiasan Dinding Jati')
            ->assertDontSee('Sofa Empuk');
    }

    public function test_kombinasi_tidak_ada_yang_cocok_menampilkan_pesan_kosong(): void
    {
        $this->buatBarang('Kursi Kantor', $this->katPerabot, 350000);

        $response = $this->actingAs($this->user)->get('/cari?q=Kursi&kategori=perabot&min_harga=500000');

        $response->assertOk()
            ->assertSee('Barang tidak ditemukan')
            ->assertSee('Reset Semua Filter');
    }

    public function test_format_nominal_rupiah_dengan_titik_dinormalisasi(): void
    {
        $this->buatBarang('Barang Uji', $this->katPerabot, 75000);

        // Pengguna mengetik atau mengirim format 50.000 dan 100.000
        $response = $this->actingAs($this->user)->get('/cari?min_harga=50.000&max_harga=100.000');

        $response->assertOk()
            ->assertSee('Barang Uji');
    }

    public function test_beranda_menautkan_kategori_ke_pencarian_kategori(): void
    {
        $response = $this->actingAs($this->user)->get('/beranda');

        $response->assertOk()
            ->assertSee(route('cari', ['kategori' => 'perabot']), false)
            ->assertSee(route('cari', ['kategori' => 'pakaian']), false);
    }
}

