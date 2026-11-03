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

/**
 * 5.1.7 · Tampilan beranda barang terdekat dan hasil cari
 * Memastikan tampilan halaman cari dan beranda sesuai mockup ui/cari.html:
 * - Struktur dialog (#urut, #filter, #lokasi)
 * - State loading, error, empty, ok
 * - Urutan (dekat, baru, murah, mahal)
 * - Filter kondisi (seperti_baru, baik, ada_minus)
 * - Breadcrumb wilayah dan badge counter filter
 */
class TampilanCariTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private int $kategoriId;
    private WilayahKelurahan $kelurahan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);

        $this->kelurahan = WilayahKelurahan::with('kecamatan.kabupaten.provinsi')->firstOrFail();
        $this->kategoriId = Category::where('type', Category::TYPE_BEKAS)->value('id');

        $this->user = User::factory()->create();
        $this->user->profile()->create([
            'kelurahan_id' => $this->kelurahan->id,
            'latitude'     => -6.3700,
            'longitude'    => 106.8200,
            'avg_rating'   => 4.8,
        ]);
    }

    private function buatBarang(string $judul, int $harga, string $kondisi = 'baik', array $extra = []): Listing
    {
        $penjual = User::factory()->create();
        $penjual->profile()->create([
            'kelurahan_id' => $this->kelurahan->id,
            'latitude'     => $extra['lat'] ?? -6.3750,
            'longitude'    => $extra['lng'] ?? 106.8250,
            'avg_rating'   => $extra['rating'] ?? 4.5,
        ]);

        return Listing::create(array_merge([
            'user_id'      => $penjual->id,
            'category_id'  => $this->kategoriId,
            'kelurahan_id' => $this->kelurahan->id,
            'title'        => $judul,
            'description'  => 'Deskripsi barang ' . $judul,
            'price'        => $harga,
            'condition'    => $kondisi,
            'status'       => Listing::AKTIF,
            'published_at' => $extra['published_at'] ?? now(),
        ], $extra));
    }

    public function test_elemen_tampilan_cari_sesuai_mockup(): void
    {
        $this->buatBarang('Kursi Kayu Jati Ukir', 350000, 'baik');

        $response = $this->actingAs($this->user)->get('/cari');

        $response->assertOk();

        // State containers
        $response->assertSee('data-state="loading"', false);
        $response->assertSee('data-state="error"', false);
        $response->assertSee('data-state="empty"', false);
        $response->assertSee('data-state="ok"', false);

        // Grid hasil dan card
        $response->assertSee('id="hasil"', false);
        $response->assertSee('Kursi Kayu Jati Ukir');
        $response->assertSee('Rp 350.000');
        $response->assertSee('Baik');

        // Breadcrumb wilayah & tombol buka lokasi
        $response->assertSee('id="wilayah"', false);
        $response->assertSee('data-open="lokasi"', false);

        // Dialog-dialog sesuai mockup
        $response->assertSee('id="urut"', false);
        $response->assertSee('id="filter"', false);
        $response->assertSee('id="lokasi"', false);

        // Elemen pencarian lokasi
        $response->assertSee('id="lok-q"', false);
        $response->assertSee('id="lok-gps"', false);
        $response->assertSee('id="lok-list"', false);
        $response->assertSee('id="lok-kosong"', false);
    }

    public function test_dialog_filter_memiliki_rentang_harga_dan_opsi_kondisi(): void
    {
        $response = $this->actingAs($this->user)->get('/cari');

        $response->assertOk();
        $response->assertSee('name="min_harga"', false);
        $response->assertSee('name="max_harga"', false);
        $response->assertSee('name="kondisi[]"', false);
        $response->assertSee('value="seperti_baru"', false);
        $response->assertSee('value="baik"', false);
        $response->assertSee('value="ada_minus"', false);
    }

    public function test_badge_counter_filter_aktif_muncul_saat_filter_diterapkan(): void
    {
        // 3 filter aktif: kategori, min_harga, dan kondisi
        $response = $this->actingAs($this->user)->get('/cari?kategori=perabot&min_harga=50000&kondisi[]=baik');

        $response->assertOk();
        // Badge counter dengan angka 3
        $response->assertSee('rounded-full bg-ink text-[10px] font-bold text-white">3</span>', false);
    }

    public function test_filter_berdasarkan_kondisi_barang(): void
    {
        $this->buatBarang('Barang Mulus', 100000, 'seperti_baru');
        $this->buatBarang('Barang Cukup', 80000, 'baik');
        $this->buatBarang('Barang Lecet', 50000, 'ada_minus');

        // Filter hanya 'seperti_baru'
        $response = $this->actingAs($this->user)->get('/cari?kondisi[]=seperti_baru');
        $response->assertOk()
            ->assertSee('Barang Mulus')
            ->assertDontSee('Barang Cukup')
            ->assertDontSee('Barang Lecet');

        // Filter kombinasi 'baik' dan 'ada_minus'
        $response2 = $this->actingAs($this->user)->get('/cari?kondisi[]=baik&kondisi[]=ada_minus');
        $response2->assertOk()
            ->assertDontSee('Barang Mulus')
            ->assertSee('Barang Cukup')
            ->assertSee('Barang Lecet');
    }

    public function test_pengurutan_harga_termurah_dan_termahal(): void
    {
        $this->buatBarang('Barang Murah', 25000);
        $this->buatBarang('Barang Sedang', 75000);
        $this->buatBarang('Barang Mahal', 200000);

        // Urut termurah
        $resMurah = $this->actingAs($this->user)->get('/cari?urut=murah');
        $resMurah->assertOk()
            ->assertSeeInOrder(['Barang Murah', 'Barang Sedang', 'Barang Mahal']);

        // Urut termahal
        $resMahal = $this->actingAs($this->user)->get('/cari?urut=mahal');
        $resMahal->assertOk()
            ->assertSeeInOrder(['Barang Mahal', 'Barang Sedang', 'Barang Murah']);
    }

    public function test_pengurutan_terbaru(): void
    {
        $this->buatBarang('Barang Kemarin', 50000, 'baik', ['published_at' => now()->subDay()]);
        $this->buatBarang('Barang Baru Saja', 50000, 'baik', ['published_at' => now()]);

        $response = $this->actingAs($this->user)->get('/cari?urut=baru');
        $response->assertOk()
            ->assertSeeInOrder(['Barang Baru Saja', 'Barang Kemarin']);
    }

    public function test_state_empty_muncul_ketika_tidak_ada_barang(): void
    {
        $response = $this->actingAs($this->user)->get('/cari?q=BarangYangPastiTidakAda12345');

        $response->assertOk();
        $response->assertSee('data-state="empty"', false);
        $response->assertSee('Barang tidak ditemukan');
        $response->assertSee('Coba kata kunci lain, longgarkan filter, atau perluas daerah pencarian.');
        $response->assertSee('Ubah filter');
    }
}

