<?php

namespace Tests\Feature;

use App\Models\MitraPengepul;
use App\Models\User;
use App\Models\WilayahKecamatan;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 8.3.4 · Cari pengepul per daerah dan jenis sampah (PPLTIMB-150). Skenario: docs/DataDemoPengepul.md */
class CariPengepulTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
        $this->pembeli = User::where('email', 'pembeli@reloop.test')->firstOrFail(); // lokasi Beji, Depok
    }

    private function mitra(string $nama): MitraPengepul
    {
        return MitraPengepul::where('nama_usaha', $nama)->firstOrFail();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/pengepul')->assertRedirect('/masuk');
        $this->get('/pengepul/'.$this->mitra('CV Daur Jaya')->id)->assertRedirect('/masuk');
    }

    public function test_default_daerah_dari_profil_hanya_mitra_terverifikasi_yang_melayani(): void
    {
        $this->actingAs($this->pembeli)->get('/pengepul')->assertOk()
            ->assertSee('Kec. Beji, Kota Depok')
            ->assertSee('2 pengepul')
            // Lapak di kecamatan yang sama tampil lebih dulu.
            ->assertSeeInOrder(['Bank Sampah Hijau Lestari', 'CV Daur Jaya'])
            ->assertDontSee('Bank Sampah Demo Beji')   // melayani Beji tapi masih menunggu
            ->assertDontSee('Lapak Berkah Sukmajaya'); // terverifikasi tapi hanya melayani Sukmajaya
    }

    public function test_kartu_memuat_nama_daerah_jarak_harga_dan_rating(): void
    {
        $this->mitra('Bank Sampah Hijau Lestari')->user->profile()->updateOrCreate([], ['avg_rating' => 4.8]);

        $this->actingAs($this->pembeli)->get('/pengepul')->assertOk()
            ->assertSee('Lapak di Kukusan, Beji')
            ->assertSee('Satu kecamatan')
            ->assertSee('Rp 500–8.000')   // rentang harga semua jenis
            ->assertSee('★ 4.8')
            ->assertSee('Lapak di Depok Jaya, Pancoran Mas')
            ->assertSee('Satu kota/kabupaten')
            ->assertSee('Belum ada rating')
            ->assertSee('Jemput · Antar Sendiri');
    }

    public function test_filter_jenis_sampah(): void
    {
        $this->actingAs($this->pembeli);

        $this->get('/pengepul?jenis=elektronik-bekas')->assertOk()
            ->assertSee('CV Daur Jaya')->assertSee('Rp 15.000')
            ->assertDontSee('Bank Sampah Hijau Lestari');

        $this->get('/pengepul?jenis=kaca')->assertOk()
            ->assertSee('Bank Sampah Hijau Lestari')->assertSee('Rp 500')
            ->assertDontSee('CV Daur Jaya');

        $this->get('/pengepul?jenis=plastik')->assertOk()
            ->assertSee('Rp 3.500')->assertSee('Rp 3.800')
            ->assertSee('2 pengepul');

        // Jenis yang tidak dikenal diperlakukan sebagai "Semua".
        $this->get('/pengepul?jenis=batu')->assertOk()->assertSee('2 pengepul');
    }

    public function test_filter_daerah_lain_dan_empty_state(): void
    {
        $this->actingAs($this->pembeli);
        $bogorTengah = WilayahKecamatan::where('kode', '3271010')->value('id');
        $cibinong = WilayahKecamatan::where('kode', '3201010')->value('id');

        $this->get('/pengepul?kecamatan_id='.$bogorTengah)->assertOk()
            ->assertSee('Pengepul Kertas Bogor Tengah')
            ->assertDontSee('CV Daur Jaya');

        // Cibinong hanya punya mitra yang masih menunggu verifikasi.
        $this->get('/pengepul?kecamatan_id='.$cibinong)->assertOk()
            ->assertSee('Belum ada pengepul di daerah ini')
            ->assertDontSee('Bank Sampah Cibinong Bersih')
            ->assertSee('Pakai lokasi di profilku');

        $this->get('/pengepul?kecamatan_id=999999')->assertSessionHasErrors('kecamatan_id');
    }

    public function test_profil_mitra_memuat_jenis_harga_jam_dan_rating(): void
    {
        $hijau = $this->mitra('Bank Sampah Hijau Lestari');

        $this->actingAs($this->pembeli)->get('/pengepul/'.$hijau->id)->assertOk()
            ->assertSee('Bank Sampah Hijau Lestari')
            ->assertSee('Terverifikasi')
            ->assertSeeInOrder(['Plastik', 'Rp 3.500/kg', 'Kertas', 'Rp 2.000/kg', 'Logam', 'Rp 8.000/kg', 'Kaca', 'Rp 500/kg'])
            ->assertSeeInOrder(['Senin', '08.00–16.00', 'Sabtu', '08.00–16.00', 'Minggu', 'Libur'])
            ->assertSee('Jemput · Antar Sendiri')
            ->assertSee('Belum ada rating')
            ->assertSee('Setor ke pengepul ini');
    }

    public function test_profil_mitra_belum_terverifikasi_atau_ditolak_tidak_bisa_dibuka(): void
    {
        $this->actingAs($this->pembeli);
        $menunggu = $this->mitra('Bank Sampah Demo Beji');
        $this->get('/pengepul/'.$menunggu->id)->assertNotFound();

        $menunggu->verifikasi(MitraPengepul::DITOLAK, User::where('email', 'admin@reloop.test')->first(), 'Data tidak lengkap');
        $this->get('/pengepul/'.$menunggu->id)->assertNotFound();
        $this->get('/pengepul/999999')->assertNotFound();
    }

    public function test_pilih_kategori_daur_ulang_langsung_ke_cari_pengepul(): void
    {
        $this->actingAs($this->pembeli)
            ->post('/pilih-kategori', ['tipe' => 'daur_ulang'])
            ->assertRedirect(route('pengepul.index'));
    }

    public function test_beranda_menautkan_pencarian_dan_profil_pengepul(): void
    {
        $this->actingAs($this->pembeli)->get('/beranda')->assertOk()
            ->assertSee('href="'.route('pengepul.index').'"', false)
            ->assertSee('href="'.route('pengepul.show', $this->mitra('Bank Sampah Hijau Lestari')).'"', false);
    }
}
