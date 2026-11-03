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

/** 5.1.3 · Pencarian barang dengan kata kunci. */
class CariBarangTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private int $kategoriId;
    private int $kelurahanId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);

        $this->kelurahanId = WilayahKelurahan::first()->id;
        $this->kategoriId = Category::where('type', Category::TYPE_BEKAS)->value('id');

        $this->user = User::factory()->create();
        $this->user->profile()->create([
            'kelurahan_id' => $this->kelurahanId,
            'latitude'     => -6.3700,
            'longitude'    => 106.8200,
        ]);
    }

    private function buatBarang(string $judul, ?string $deskripsi = null, string $status = Listing::AKTIF, array $extra = []): Listing
    {
        $penjual = User::factory()->create();
        $penjual->profile()->create([
            'kelurahan_id' => $this->kelurahanId,
            'latitude'     => $extra['lat'] ?? -6.3750,
            'longitude'    => $extra['lng'] ?? 106.8250,
        ]);

        return Listing::create(array_merge([
            'user_id'      => $penjual->id,
            'category_id'  => $this->kategoriId,
            'kelurahan_id' => $this->kelurahanId,
            'title'        => $judul,
            'description'  => $deskripsi ?? 'Deskripsi untuk ' . $judul,
            'price'        => 75000,
            'condition'    => 'baik',
            'status'       => $status,
            'published_at' => now(),
        ], $extra));
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/cari')->assertRedirect('/masuk');
    }

    public function test_halaman_cari_dapat_diakses_oleh_user(): void
    {
        $this->actingAs($this->user)->get('/cari')->assertOk()
            ->assertSee('Pencarian Barang')
            ->assertSee('Cari kursi, buku, sepeda…')
            ->assertSee('Cari');
    }

    public function test_cari_di_judul_barang_aktif(): void
    {
        $this->buatBarang('Sepeda Gunung Polygon', 'Kondisi mulus terawat');
        $this->buatBarang('Meja Belajar Kayu', 'Bahan kayu jati');

        $response = $this->actingAs($this->user)->get('/cari?q=Sepeda');

        $response->assertOk()
            ->assertSee('Sepeda Gunung Polygon')
            ->assertDontSee('Meja Belajar Kayu');
    }

    public function test_cari_di_deskripsi_barang_aktif(): void
    {
        // Judul tidak mengandung kata "vintage", tapi deskripsi mengandung "vintage"
        $this->buatBarang('Kamera Analog', 'Koleksi antik bergaya vintage klasik');
        $this->buatBarang('Kamera Digital Sony', 'Model terbaru beresolusi tinggi');

        $response = $this->actingAs($this->user)->get('/cari?q=vintage');

        $response->assertOk()
            ->assertSee('Kamera Analog')
            ->assertDontSee('Kamera Digital Sony');
    }

    public function test_pencarian_case_insensitive(): void
    {
        $this->buatBarang('Jaket Denim Levi', 'Bahan tebal warna biru');

        $this->actingAs($this->user)->get('/cari?q=jaket')->assertOk()->assertSee('Jaket Denim Levi');
        $this->actingAs($this->user)->get('/cari?q=JAKET')->assertOk()->assertSee('Jaket Denim Levi');
        $this->actingAs($this->user)->get('/cari?q=JaKeT')->assertOk()->assertSee('Jaket Denim Levi');
    }

    public function test_barang_tidak_aktif_tidak_muncul_dalam_pencarian(): void
    {
        $this->buatBarang('Sepeda Lipat Aktif', 'Tersedia', Listing::AKTIF);
        $this->buatBarang('Sepeda Lipat Draft', 'Belum tayang', Listing::DRAFT);
        $this->buatBarang('Sepeda Lipat Dipesan', 'Sedang proses COD', Listing::DIPESAN);
        $this->buatBarang('Sepeda Lipat Terjual', 'Sudah laku', Listing::TERJUAL);
        $this->buatBarang('Sepeda Lipat Dihapus', 'Sudah dihapus pemilik', Listing::DIHAPUS);

        $response = $this->actingAs($this->user)->get('/cari?q=Sepeda+Lipat');

        $response->assertOk()
            ->assertSee('Sepeda Lipat Aktif')
            ->assertDontSee('Sepeda Lipat Draft')
            ->assertDontSee('Sepeda Lipat Dipesan')
            ->assertDontSee('Sepeda Lipat Terjual')
            ->assertDontSee('Sepeda Lipat Dihapus');
    }

    public function test_hasil_kosong_ditangani_dengan_pesan_ramah(): void
    {
        $this->buatBarang('Kursi Kayu', 'Kayu mahoni');

        $response = $this->actingAs($this->user)->get('/cari?q=Pesawat+Terbang');

        $response->assertOk()
            ->assertSee('Barang tidak ditemukan')
            ->assertSee('Pesawat Terbang')
            ->assertSee('Hapus kata kunci')
            ->assertSee('Kembali ke Beranda');
    }

    public function test_pencarian_tanpa_kata_kunci_menampilkan_semua_barang_aktif(): void
    {
        $this->buatBarang('Buku Ensiklopedia');
        $this->buatBarang('Gitar Akustik');

        $response = $this->actingAs($this->user)->get('/cari');

        $response->assertOk()
            ->assertSee('Buku Ensiklopedia')
            ->assertSee('Gitar Akustik');
    }

    public function test_pencarian_mempertahankan_urutan_jarak_terdekat(): void
    {
        // Pembeli ada di Beji (-6.3700, 106.8200)
        // Barang A Dekat (-6.3720, 106.8210)
        // Barang B Jauh di Bogor (-6.5950, 106.7970)
        $this->buatBarang('Meja Belajar Jauh', 'Kayu kuat', Listing::AKTIF, ['lat' => -6.5950, 'lng' => 106.7970]);
        $this->buatBarang('Meja Belajar Dekat', 'Kayu kokoh', Listing::AKTIF, ['lat' => -6.3720, 'lng' => 106.8210]);

        $response = $this->actingAs($this->user)->get('/cari?q=Meja+Belajar');

        $response->assertOk()
            ->assertSeeInOrder(['Meja Belajar Dekat', 'Meja Belajar Jauh']);
    }

    public function test_beranda_menautkan_ke_halaman_cari(): void
    {
        $response = $this->actingAs($this->user)->get('/beranda');

        $response->assertOk()
            ->assertSee(route('cari'), false);
    }
}

