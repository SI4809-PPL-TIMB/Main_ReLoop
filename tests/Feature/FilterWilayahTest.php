<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Models\WilayahKabupaten;
use App\Models\WilayahKecamatan;
use App\Models\WilayahKelurahan;
use App\Models\WilayahProvinsi;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 5.1.5 · Filter wilayah sampai desa/kelurahan, kombinasi dengan filter lain. */
class FilterWilayahTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $katPerabot;
    private Category $katPakaian;

    private WilayahProvinsi $jabar;
    private WilayahKabupaten $kabBogor;
    private WilayahKabupaten $kotaDepok;
    private WilayahKecamatan $kecCibinong;
    private WilayahKecamatan $kecBeji;
    private WilayahKelurahan $kelKatulampa;
    private WilayahKelurahan $kelCibinong;
    private WilayahKelurahan $kelBeji;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);

        $this->katPerabot = Category::where('slug', 'perabot')->firstOrFail();
        $this->katPakaian = Category::where('slug', 'pakaian')->firstOrFail();

        $this->jabar = WilayahProvinsi::where('nama', 'Jawa Barat')->firstOrFail();
        $this->kabBogor = WilayahKabupaten::where('nama', 'Kabupaten Bogor')->firstOrFail();
        $this->kotaDepok = WilayahKabupaten::where('nama', 'Kota Depok')->firstOrFail();

        $this->kecCibinong = WilayahKecamatan::where('nama', 'Cibinong')->firstOrFail();
        $this->kecBeji = WilayahKecamatan::where('nama', 'Beji')->firstOrFail();

        $this->kelKatulampa = WilayahKelurahan::where('nama', 'Katulampa')->firstOrFail();
        $this->kelCibinong = WilayahKelurahan::where('nama', 'Cibinong')->firstOrFail();
        $this->kelBeji = WilayahKelurahan::where('nama', 'Beji')->firstOrFail();

        $this->user = User::factory()->create();
        $this->user->profile()->create([
            'kelurahan_id' => $this->kelBeji->id,
            'latitude'     => -6.3700,
            'longitude'    => 106.8200,
        ]);
    }

    private function buatBarang(string $judul, WilayahKelurahan $kelurahan, ?Category $kategori = null, int $harga = 100000, array $extra = []): Listing
    {
        $penjual = User::factory()->create();
        $penjual->profile()->create([
            'kelurahan_id' => $kelurahan->id,
            'latitude'     => $extra['lat'] ?? -6.3750,
            'longitude'    => $extra['lng'] ?? 106.8250,
        ]);

        return Listing::create(array_merge([
            'user_id'      => $penjual->id,
            'category_id'  => ($kategori ?? $this->katPerabot)->id,
            'kelurahan_id' => $kelurahan->id,
            'title'        => $judul,
            'description'  => $extra['desc'] ?? 'Deskripsi untuk '.$judul,
            'price'        => $harga,
            'condition'    => 'baik',
            'status'       => $extra['status'] ?? Listing::AKTIF,
            'published_at' => now(),
        ], $extra));
    }

    public function test_filter_berdasarkan_kelurahan_id(): void
    {
        $this->buatBarang('Meja Katulampa', $this->kelKatulampa);
        $this->buatBarang('Meja Cibinong', $this->kelCibinong);

        $response = $this->actingAs($this->user)->get('/cari?kelurahan_id='.$this->kelKatulampa->id);

        $response->assertOk()
            ->assertSee('Meja Katulampa')
            ->assertDontSee('Meja Cibinong');
    }

    public function test_filter_berdasarkan_kecamatan_id(): void
    {
        $kelNanggewer = WilayahKelurahan::where('nama', 'Nanggewer')->firstOrFail(); // Kecamatan Cibinong
        $this->buatBarang('Kursi Cibinong', $this->kelCibinong);
        $this->buatBarang('Kursi Nanggewer', $kelNanggewer);
        $this->buatBarang('Kursi Beji', $this->kelBeji);

        $response = $this->actingAs($this->user)->get('/cari?kecamatan_id='.$this->kecCibinong->id);

        $response->assertOk()
            ->assertSee('Kursi Cibinong')
            ->assertSee('Kursi Nanggewer')
            ->assertDontSee('Kursi Beji');
    }

    public function test_filter_berdasarkan_kabupaten_id(): void
    {
        $this->buatBarang('Barang Kab Bogor 1', $this->kelKatulampa);
        $this->buatBarang('Barang Kab Bogor 2', $this->kelCibinong);
        $this->buatBarang('Barang Kota Depok', $this->kelBeji);

        $response = $this->actingAs($this->user)->get('/cari?kabupaten_id='.$this->kabBogor->id);

        $response->assertOk()
            ->assertSee('Barang Kab Bogor 1')
            ->assertSee('Barang Kab Bogor 2')
            ->assertDontSee('Barang Kota Depok');
    }

    public function test_filter_berdasarkan_provinsi_id(): void
    {
        $this->buatBarang('Barang Jawa Barat', $this->kelBeji);

        $response = $this->actingAs($this->user)->get('/cari?provinsi_id='.$this->jabar->id);

        $response->assertOk()
            ->assertSee('Barang Jawa Barat');
    }

    public function test_filter_berdasarkan_nama_wilayah(): void
    {
        $this->buatBarang('Lemari Katulampa', $this->kelKatulampa);
        $this->buatBarang('Lemari Beji', $this->kelBeji);

        $response = $this->actingAs($this->user)->get('/cari?desa=Katulampa');

        $response->assertOk()
            ->assertSee('Lemari Katulampa')
            ->assertDontSee('Lemari Beji');
    }

    public function test_kombinasi_wilayah_dengan_kata_kunci(): void
    {
        $this->buatBarang('Sepeda Lipat Beji', $this->kelBeji);
        $this->buatBarang('Sepeda Lipat Katulampa', $this->kelKatulampa);
        $this->buatBarang('Meja Kayu Beji', $this->kelBeji);

        $response = $this->actingAs($this->user)->get('/cari?q=Sepeda&kelurahan_id='.$this->kelBeji->id);

        $response->assertOk()
            ->assertSee('Sepeda Lipat Beji')
            ->assertDontSee('Sepeda Lipat Katulampa')
            ->assertDontSee('Meja Kayu Beji');
    }

    public function test_kombinasi_wilayah_dengan_kategori(): void
    {
        $this->buatBarang('Meja Belajar', $this->kelBeji, $this->katPerabot);
        $this->buatBarang('Jaket Denim', $this->kelBeji, $this->katPakaian);
        $this->buatBarang('Meja Tamu', $this->kelKatulampa, $this->katPerabot);

        $response = $this->actingAs($this->user)->get('/cari?kategori=perabot&kabupaten_id='.$this->kotaDepok->id);

        $response->assertOk()
            ->assertSee('Meja Belajar')
            ->assertDontSee('Jaket Denim')
            ->assertDontSee('Meja Tamu');
    }

    public function test_kombinasi_wilayah_dengan_rentang_harga(): void
    {
        $this->buatBarang('Kursi Murah Beji', $this->kelBeji, $this->katPerabot, 50000);
        $this->buatBarang('Kursi Mahal Beji', $this->kelBeji, $this->katPerabot, 500000);
        $this->buatBarang('Kursi Murah Bogor', $this->kelKatulampa, $this->katPerabot, 50000);

        $response = $this->actingAs($this->user)->get('/cari?kabupaten_id='.$this->kotaDepok->id.'&max_harga=100000');

        $response->assertOk()
            ->assertSee('Kursi Murah Beji')
            ->assertDontSee('Kursi Mahal Beji')
            ->assertDontSee('Kursi Murah Bogor');
    }

    public function test_kombinasi_lengkap_semua_filter(): void
    {
        // 1. Cocok SEMUA: keyword "Kayu", kategori "perabot", harga 150rb (100rb-200rb), wilayah Beji
        $this->buatBarang('Kursi Kayu Jati', $this->kelBeji, $this->katPerabot, 150000);

        // 2. Beda wilayah (Katulampa)
        $this->buatBarang('Kursi Kayu Mahoni', $this->kelKatulampa, $this->katPerabot, 150000);

        // 3. Beda harga (terlalu mahal)
        $this->buatBarang('Meja Kayu Mewah', $this->kelBeji, $this->katPerabot, 700000);

        // 4. Beda kategori (pakaian)
        $this->buatBarang('Kancing Kayu Antik', $this->kelBeji, $this->katPakaian, 150000);

        // 5. Beda keyword (tidak ada "Kayu")
        $this->buatBarang('Kursi Besi Lipat', $this->kelBeji, $this->katPerabot, 150000);

        $url = '/cari?q=Kayu&kategori=perabot&min_harga=100000&max_harga=200000&kelurahan_id='.$this->kelBeji->id;
        $response = $this->actingAs($this->user)->get($url);

        $response->assertOk()
            ->assertSee('Kursi Kayu Jati')
            ->assertDontSee('Kursi Kayu Mahoni')
            ->assertDontSee('Meja Kayu Mewah')
            ->assertDontSee('Kancing Kayu Antik')
            ->assertDontSee('Kursi Besi Lipat');
    }

    public function test_kombinasi_wilayah_tidak_ada_barang_menampilkan_pesan_kosong(): void
    {
        $this->buatBarang('Barang di Beji', $this->kelBeji);

        $response = $this->actingAs($this->user)->get('/cari?kelurahan_id='.$this->kelKatulampa->id);

        $response->assertOk()
            ->assertSee('Barang tidak ditemukan')
            ->assertSee('Reset Semua Filter');
    }
}

