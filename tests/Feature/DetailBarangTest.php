<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 5.2.4 — Testing detail barang dan profil penjual (PPLTIMB-61).
 */
class DetailBarangTest extends TestCase
{
    use RefreshDatabase;

    private User $penjual;
    private User $pembeli;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);

        $kelBeji = WilayahKelurahan::where('nama', 'Beji')->first() ?? WilayahKelurahan::first();

        // Penjual di Beji dengan koordinat
        $this->penjual = User::factory()->create(['name' => 'Budi Penjual']);
        $this->penjual->profile()->create([
            'kelurahan_id' => $kelBeji->id,
            'latitude'     => -6.3700,
            'longitude'    => 106.8200,
        ]);

        // Pembeli di Beji Timur dengan koordinat
        $kelBejiTimur = WilayahKelurahan::where('nama', 'Beji Timur')->first() ?? $kelBeji;
        $this->pembeli = User::factory()->create(['name' => 'Siti Pembeli']);
        $this->pembeli->profile()->create([
            'kelurahan_id' => $kelBejiTimur->id,
            'latitude'     => -6.3750,
            'longitude'    => 106.8250,
        ]);
    }

    private function buatBarang(array $ubah = []): Listing
    {
        $kategori = Category::where('type', 'bekas')->first();

        return Listing::create(array_merge([
            'user_id'         => $this->penjual->id,
            'category_id'     => $kategori->id,
            'kelurahan_id'    => $this->penjual->profile->kelurahan_id,
            'title'           => 'Sepeda Gunung Bekas',
            'description'     => 'Sepeda gunung kondisi mulus terawat, siap pakai.',
            'price'           => 750000,
            'is_negotiable'   => true,
            'condition'       => 'baik',
            'condition_note'  => 'Rem belakang baru diganti',
            'material'        => 'Aluminium',
            'size'            => '26 inch',
            'usage_duration'  => '6 bulan',
            'quantity'        => 1,
            'status'          => Listing::AKTIF,
            'published_at'    => now(),
        ], $ubah));
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $barang = $this->buatBarang();

        $this->get(route('barang.detail', $barang))
            ->assertRedirect(route('login'));
    }

    public function test_halaman_detail_barang_aktif_dapat_diakses(): void
    {
        $barang = $this->buatBarang();

        ListingPhoto::create([
            'listing_id' => $barang->id,
            'path'       => 'listings/test.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $barang));

        $response->assertOk()
            ->assertSee('Sepeda Gunung Bekas')
            ->assertSee('Rp 750.000')
            ->assertSee('Bisa nego')
            ->assertSee('Baik')
            ->assertSee('Wajar dipakai')
            ->assertSee('Rem belakang baru diganti')
            ->assertSee('Aluminium')
            ->assertSee('26 inch')
            ->assertSee('6 bulan')
            ->assertSee('1 buah')
            ->assertSee('Sepeda gunung kondisi mulus terawat')
            ->assertSee('Budi Penjual')
            ->assertSee('Belum ada ulasan')
            ->assertSee('Uang aman sampai barang diterima');
    }

    public function test_menampilkan_perkiraan_jarak_jika_lokasi_tersedia(): void
    {
        $barang = $this->buatBarang();

        $response = $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $barang));

        $response->assertOk()
            ->assertSee('km dari kamu');
    }

    public function test_barang_dipesan_tampil_penanda_dan_tombol_nonaktif(): void
    {
        $barang = $this->buatBarang(['status' => Listing::DIPESAN]);

        $response = $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $barang));

        $response->assertOk()
            ->assertSee('Barang ini sudah dipesan')
            ->assertSee('Pembeli lain sedang menyelesaikan pembayaran')
            ->assertSee('Sudah dipesan')
            ->assertDontSee('Beli sekarang');
    }

    public function test_barang_terjual_tampil_penanda_dan_tombol_nonaktif(): void
    {
        $barang = $this->buatBarang(['status' => Listing::TERJUAL]);

        $response = $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $barang));

        $response->assertOk()
            ->assertSee('Barang ini sudah terjual')
            ->assertSee('Sudah terjual')
            ->assertDontSee('Beli sekarang');
    }

    public function test_barang_draft_mengembalikan_404(): void
    {
        $barang = $this->buatBarang(['status' => Listing::DRAFT]);

        $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $barang))
            ->assertNotFound();
    }

    public function test_barang_dihapus_mengembalikan_404(): void
    {
        $barang = $this->buatBarang(['status' => Listing::DIHAPUS]);

        $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $barang))
            ->assertNotFound();
    }

    public function test_id_barang_tidak_ada_mengembalikan_404(): void
    {
        $this->actingAs($this->pembeli)
            ->get('/barang/999999')
            ->assertNotFound();
    }

    public function test_pemilik_melihat_tombol_ubah_dan_barang_saya(): void
    {
        $barang = $this->buatBarang();

        $response = $this->actingAs($this->penjual)
            ->get(route('barang.detail', $barang));

        $response->assertOk()
            ->assertSee('Ubah barang')
            ->assertSee('Barang saya')
            ->assertDontSee('Beli sekarang')
            ->assertDontSee('Chat penjual');
    }

    public function test_bukan_pemilik_melihat_tombol_chat_tawar_dan_beli(): void
    {
        $barang = $this->buatBarang(['is_negotiable' => true]);

        $response = $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $barang));

        $response->assertOk()
            ->assertSee('Chat penjual')
            ->assertSee('Tawar')
            ->assertSee('Beli sekarang')
            ->assertDontSee('Ubah barang');
    }

    public function test_barang_tidak_bisa_nego_tidak_menampilkan_tombol_tawar(): void
    {
        $barang = $this->buatBarang(['is_negotiable' => false]);

        $response = $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $barang));

        $response->assertOk()
            ->assertSee('Beli sekarang')
            ->assertDontSee('Bisa nego')
            ->assertDontSee('Tawar');
    }

    public function test_tombol_profil_mengarahkan_ke_halaman_profil_penjual(): void
    {
        $barang = $this->buatBarang();

        $response = $this->actingAs($this->pembeli)
            ->get(route('barang.detail', $barang));

        $response->assertOk()
            ->assertSee(route('penjual.show', $this->penjual));

        $profilPenjualResponse = $this->actingAs($this->pembeli)
            ->get(route('penjual.show', $this->penjual));

        $profilPenjualResponse->assertOk()
            ->assertSee('Profil Penjual')
            ->assertSee($this->penjual->name)
            ->assertSee('Barang')
            ->assertSee('Ulasan')
            ->assertSee($barang->title);
    }
}
