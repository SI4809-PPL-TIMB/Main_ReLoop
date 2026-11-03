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

/** 5.1.2 · Query barang aktif urut jarak dari lokasi pembeli. */
class BarangTerdekatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);
    }

    private function kel(string $nama): int
    {
        return WilayahKelurahan::where('nama', $nama)->value('id');
    }

    /** Penjual baru dengan lokasi (desa + titik opsional) dan satu barang. */
    private function barang(string $judul, string $kelurahan, ?float $lat = null, ?float $lng = null, array $ubah = []): Listing
    {
        $penjual = User::factory()->create();
        $penjual->profile()->create(['kelurahan_id' => $this->kel($kelurahan), 'latitude' => $lat, 'longitude' => $lng]);

        return Listing::create(array_merge([
            'user_id' => $penjual->id, 'category_id' => Category::where('type', 'bekas')->value('id'),
            'kelurahan_id' => $this->kel($kelurahan), 'title' => $judul, 'price' => 100000,
            'condition' => 'baik', 'status' => Listing::AKTIF, 'published_at' => now(),
        ], $ubah));
    }

    private function pembeli(string $kelurahan, ?float $lat = null, ?float $lng = null): User
    {
        $u = User::factory()->create();
        $u->profile()->create(['kelurahan_id' => $this->kel($kelurahan), 'latitude' => $lat, 'longitude' => $lng]);

        return $u;
    }

    public function test_hanya_barang_aktif(): void
    {
        $this->barang('Aktif', 'Beji');
        foreach ([Listing::DRAFT, Listing::DIPESAN, Listing::TERJUAL, Listing::DIHAPUS] as $s) {
            $this->barang(ucfirst($s), 'Beji', null, null, ['status' => $s]);
        }

        $this->assertSame(['Aktif'], Listing::aktif()->terdekat(null)->pluck('title')->all());
    }

    public function test_urut_jarak_dari_titik_pembeli(): void
    {
        // pembeli di Beji, Depok
        $pembeli = $this->pembeli('Beji', -6.3700, 106.8200);
        $this->barang('Jauh (Bogor)', 'Paledang', -6.5950, 106.7970);
        $this->barang('Dekat', 'Beji Timur', -6.3750, 106.8250);
        $this->barang('Sedang', 'Mekarjaya', -6.4000, 106.8400);

        $hasil = Listing::aktif()->terdekat($pembeli->profile)->get();

        $this->assertSame(['Dekat', 'Sedang', 'Jauh (Bogor)'], $hasil->pluck('title')->all());
        $this->assertEqualsWithDelta(0.78, $hasil[0]->jarak_km, 0.05);
        $this->assertEqualsWithDelta(25.1, $hasil[2]->jarak_km, 0.5);
    }

    public function test_barang_tanpa_titik_di_belakang_dan_urut_wilayah(): void
    {
        $pembeli = $this->pembeli('Beji', -6.3700, 106.8200);
        $this->barang('Tanpa titik, kota lain', 'Paledang');
        $this->barang('Tanpa titik, desa sama', 'Beji');
        $this->barang('Bertitik jauh', 'Katulampa', -6.6200, 106.8200);

        $hasil = Listing::aktif()->terdekat($pembeli->profile)->get();

        $this->assertSame(['Bertitik jauh', 'Tanpa titik, desa sama', 'Tanpa titik, kota lain'], $hasil->pluck('title')->all());
        $this->assertNull($hasil[1]->jarak_km);
    }

    public function test_pembeli_tanpa_titik_urut_kedekatan_wilayah(): void
    {
        $pembeli = $this->pembeli('Beji');
        $this->barang('Kab lain', 'Paledang', -6.5950, 106.7970);
        $this->barang('Kota sama', 'Depok');
        $this->barang('Kecamatan sama', 'Kukusan');
        $this->barang('Desa sama', 'Beji');

        $hasil = Listing::aktif()->terdekat($pembeli->profile)->get();

        $this->assertSame(['Desa sama', 'Kecamatan sama', 'Kota sama', 'Kab lain'], $hasil->pluck('title')->all());
        $this->assertNull($hasil[0]->jarak_km);
    }

    public function test_tanpa_lokasi_urut_terbaru(): void
    {
        $this->barang('Lama', 'Beji', null, null, ['published_at' => now()->subDay()]);
        $this->barang('Baru', 'Paledang');

        $this->assertSame(['Baru', 'Lama'], Listing::aktif()->terdekat(null)->pluck('title')->all());
    }

    public function test_bisa_ditambah_filter_untuk_pencarian(): void
    {
        $pembeli = $this->pembeli('Beji', -6.3700, 106.8200);
        $this->barang('Kursi Jauh', 'Paledang', -6.5950, 106.7970);
        $this->barang('Meja Dekat', 'Beji', -6.3710, 106.8210);
        $this->barang('Kursi Dekat', 'Beji Timur', -6.3750, 106.8250);

        $hasil = Listing::aktif()->terdekat($pembeli->profile)->where('listings.title', 'like', '%Kursi%')->pluck('title')->all();

        $this->assertSame(['Kursi Dekat', 'Kursi Jauh'], $hasil);
    }

    public function test_beranda_menampilkan_barang_terdekat_dengan_jarak(): void
    {
        $pembeli = $this->pembeli('Beji', -6.3700, 106.8200);
        $this->barang('Sepeda Jauh', 'Paledang', -6.5950, 106.7970);
        $this->barang('Kursi Dekat', 'Beji Timur', -6.3750, 106.8250);

        $this->actingAs($pembeli)->get('/beranda')->assertOk()
            ->assertSeeInOrder(['Kursi Dekat', 'Sepeda Jauh'])
            ->assertSee('0,8 km');
    }

    public function test_bisa_menerima_objek_user_atau_profile(): void
    {
        $pembeli = $this->pembeli('Beji', -6.3700, 106.8200);
        $this->barang('Dekat', 'Beji Timur', -6.3750, 106.8250);

        $dariProfile = Listing::aktif()->terdekat($pembeli->profile)->pluck('title')->all();
        $dariUser = Listing::aktif()->terdekat($pembeli)->pluck('title')->all();

        $this->assertSame($dariProfile, $dariUser);
    }
}

