<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MitraPengepul;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MitraPengepulSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** 8.2.4 · Data simulasi pengepul (PPLTIMB-149). Rancangan: docs/DataDemoPengepul.md */
class MitraPengepulSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    private function mitra(string $nama): MitraPengepul
    {
        return MitraPengepul::where('nama_usaha', $nama)->firstOrFail();
    }

    /** Mitra terverifikasi yang melayani kecamatan pembeli demo, opsional untuk satu jenis sampah. */
    private function dicariDariLokasiPembeli(?string $sampah = null): array
    {
        $kecamatan = User::where('email', 'pembeli@reloop.test')->firstOrFail()->profile->kelurahan->kecamatan_id;

        return MitraPengepul::terverifikasi()
            ->whereHas('area', fn ($q) => $q->whereKey($kecamatan))
            ->when($sampah, fn ($q) => $q->whereHas('harga', fn ($h) => $h->where('category_id', Category::where('slug', $sampah)->value('id'))))
            ->orderBy('nama_usaha')->pluck('nama_usaha')->all();
    }

    public function test_pengepul_contoh_lengkap_sebagian_terverifikasi_sebagian_menunggu(): void
    {
        $this->assertSame(count(MitraPengepulSeeder::DATA), MitraPengepul::count());
        $this->assertSame(4, MitraPengepul::terverifikasi()->count());
        $this->assertSame(2, MitraPengepul::where('status', MitraPengepul::MENUNGGU)->count());

        $admin = User::where('email', 'admin@reloop.test')->firstOrFail();
        foreach (MitraPengepul::with(['user', 'area', 'harga', 'jam', 'kelurahan'])->get() as $m) {
            $this->assertTrue($m->user->isPengepul(), $m->nama_usaha);
            $this->assertNotNull($m->user->email_verified_at, $m->nama_usaha);
            $this->assertNotNull($m->kelurahan, $m->nama_usaha);
            $this->assertNotEmpty($m->area, $m->nama_usaha);
            $this->assertNotEmpty($m->harga, $m->nama_usaha);
            $this->assertNotEmpty($m->jam, $m->nama_usaha);
            $this->assertTrue($m->layanan_jemput || $m->layanan_antar, $m->nama_usaha);

            if ($m->status === MitraPengepul::TERVERIFIKASI) {
                $this->assertSame($admin->id, $m->diverifikasi_oleh);
                $this->assertNotNull($m->diverifikasi_pada);
            }
        }

        // Harga hanya untuk kategori daur ulang
        $this->assertSame(0, DB::table('mitra_harga')->join('categories', 'categories.id', '=', 'mitra_harga.category_id')
            ->where('categories.type', '!=', Category::TYPE_DAUR_ULANG)->count());
    }

    public function test_data_sesuai_rancangan(): void
    {
        $hijau = $this->mitra('Bank Sampah Hijau Lestari')->load(['area', 'harga.category', 'jam', 'kelurahan']);

        $this->assertSame('Kukusan', $hijau->kelurahan->nama);
        $this->assertEqualsCanonicalizing(['Beji', 'Pancoran Mas', 'Sukmajaya'], $hijau->area->pluck('nama')->all());
        $this->assertSame(3500, $hijau->harga->firstWhere('category.slug', 'plastik')->harga_per_kg);
        $this->assertSame([1, 2, 3, 4, 5, 6], $hijau->jam->pluck('hari')->all());
        $this->assertSame('08.00–16.00', $hijau->jam->first()->rentang());

        $daurJaya = $this->mitra('CV Daur Jaya')->load('jam');
        $this->assertSame('08.00–12.00', $daurJaya->jam->firstWhere('hari', 6)->rentang());
        $this->assertNull($daurJaya->jam->firstWhere('hari', 7));

        $this->assertSame('Jemput', $this->mitra('Pengepul Kertas Bogor Tengah')->layananLabel());
        $this->assertSame('Antar Sendiri', $this->mitra('Lapak Berkah Sukmajaya')->layananLabel());
    }

    public function test_pengepul_contoh_muncul_di_pencarian_dari_lokasi_pembeli(): void
    {
        // Bank Sampah Demo Beji juga melayani Beji tapi masih menunggu, jadi tidak boleh muncul.
        // Lapak Berkah (Sukmajaya, hanya Antar Sendiri) hanya melayani Sukmajaya, jadi tidak muncul.
        $this->assertSame(['Bank Sampah Hijau Lestari', 'CV Daur Jaya'], $this->dicariDariLokasiPembeli());
        $this->assertSame(['Bank Sampah Hijau Lestari', 'CV Daur Jaya'], $this->dicariDariLokasiPembeli('logam'));
        $this->assertSame(['CV Daur Jaya'], $this->dicariDariLokasiPembeli('elektronik-bekas'));
        $this->assertSame(['Bank Sampah Hijau Lestari'], $this->dicariDariLokasiPembeli('kaca'));
    }

    public function test_daerah_tanpa_mitra_terverifikasi_kosong(): void
    {
        $cibinong = $this->mitra('Bank Sampah Cibinong Bersih')->area->first();

        $this->assertSame(0, MitraPengepul::terverifikasi()->whereHas('area', fn ($q) => $q->whereKey($cibinong->id))->count());
    }

    public function test_seeder_ulang_tidak_menimpa_perubahan_demo(): void
    {
        $hijau = $this->mitra('Bank Sampah Hijau Lestari');
        $plastik = Category::where('slug', 'plastik')->value('id');
        $hijau->harga()->where('category_id', $plastik)->update(['harga_per_kg' => 4200]);
        $hijau->jam()->where('hari', 6)->delete();
        $hijau->verifikasi(MitraPengepul::DITOLAK, User::where('email', 'admin@reloop.test')->first(), 'Uji demo');
        $demo = $this->mitra('Bank Sampah Demo Beji');
        $demo->verifikasi(MitraPengepul::TERVERIFIKASI, User::where('email', 'admin@reloop.test')->first());

        $this->seed(DatabaseSeeder::class);

        $hijau->refresh();
        $this->assertSame(MitraPengepul::DITOLAK, $hijau->status);
        $this->assertSame(4200, $hijau->harga()->where('category_id', $plastik)->value('harga_per_kg'));
        $this->assertSame([1, 2, 3, 4, 5], $hijau->jam()->pluck('hari')->all());
        $this->assertSame(MitraPengepul::TERVERIFIKASI, $demo->fresh()->status);
        $this->assertSame(6, MitraPengepul::count());
    }

    public function test_seeder_melengkapi_mitra_lama_yang_pengaturannya_kosong(): void
    {
        // Kondisi database dari 8.1: pengepul demo sudah ada tapi belum punya harga/area/jam.
        $demo = $this->mitra('Bank Sampah Demo Beji');
        $demo->harga()->delete();
        $demo->area()->detach();
        $demo->jam()->delete();

        $this->seed(MitraPengepulSeeder::class);

        $demo->refresh();
        $this->assertSame(3, $demo->harga()->count());
        $this->assertSame(['Beji'], $demo->area()->pluck('nama')->all());
        $this->assertSame(5, $demo->jam()->count());
    }

    public function test_akun_pengepul_contoh_bisa_masuk_dan_melihat_pengaturannya(): void
    {
        $this->post('/masuk', ['identifier' => 'daurjaya@reloop.test', 'password' => 'password'])->assertRedirect('/mitra/pengaturan');
        $this->get('/mitra/pengaturan')->assertOk()
            ->assertSee('CV Daur Jaya')->assertSee('Akunmu terverifikasi')
            ->assertSee('value="15.000"', false);
    }

    public function test_admin_melihat_semua_pengepul_contoh(): void
    {
        $this->actingAs(User::where('email', 'admin@reloop.test')->firstOrFail());

        $res = $this->get('/admin/mitra')->assertOk();
        foreach (MitraPengepulSeeder::DATA as $d) {
            $res->assertSee($d['nama_usaha']);
        }
        $this->get('/admin/mitra?status=menunggu')->assertOk()
            ->assertSee('Bank Sampah Demo Beji')->assertSee('Bank Sampah Cibinong Bersih')->assertDontSee('CV Daur Jaya');
    }

    public function test_beranda_menampilkan_semua_pengepul_terverifikasi_di_daerah_pengguna(): void
    {
        $pembeli = User::where('email', 'pembeli@reloop.test')->firstOrFail();
        $nama = fn () => $this->actingAs($pembeli)->get('/beranda')->assertOk()->viewData('pengepul')->pluck('nama')->sort()->values()->all();

        $this->assertSame(['Bank Sampah Hijau Lestari', 'CV Daur Jaya'], $nama());
        $this->assertNotContains('Lapak Berkah Sukmajaya', $nama());
        $this->get('/beranda')->assertSee('Lapak di Kukusan, Beji')->assertSee('Lapak di Depok Jaya, Pancoran Mas');

        // Admin memverifikasi pengepul yang tadinya menunggu → langsung ikut tampil
        $this->mitra('Bank Sampah Demo Beji')->verifikasi(MitraPengepul::TERVERIFIKASI, User::where('email', 'admin@reloop.test')->first());
        $this->assertContains('Bank Sampah Demo Beji', $nama());

        // Ditolak → hilang lagi
        $this->mitra('CV Daur Jaya')->verifikasi(MitraPengepul::DITOLAK, User::where('email', 'admin@reloop.test')->first(), 'Uji');
        $this->assertNotContains('CV Daur Jaya', $nama());

        $this->get('/beranda')->assertSee('melayani Kec. Beji')->assertSee('Lapak di Kukusan, Beji')->assertSee('/kg plastik')
            ->assertDontSee('0,8 km')->assertSee('assets/illust/collectors/hijau-lestari.png', false);
    }

    public function test_beranda_tanpa_pengepul_di_daerah_menampilkan_pesan_kosong(): void
    {
        $warga = User::factory()->create();
        $warga->profile()->create(['kelurahan_id' => $this->mitra('Bank Sampah Cibinong Bersih')->kelurahan_id]);

        $this->actingAs($warga)->get('/beranda')->assertOk()
            ->assertViewHas('pengepul', fn ($p) => $p->isEmpty())
            ->assertSee('Belum ada pengepul di daerahmu');
    }

    public function test_barang_contoh_muncul_di_beranda(): void
    {
        $pembeli = User::where('email', 'pembeli@reloop.test')->firstOrFail();

        // Data "barang" berisi id hanya jika diambil dari database; contoh cadangan beranda tidak punya id.
        $this->actingAs($pembeli)->get('/beranda')->assertOk()
            ->assertViewHas('barang', fn ($barang) => $barang->count() === 8 && $barang->every(fn ($b) => isset($b['id'], $b['foto'])));
    }
}
