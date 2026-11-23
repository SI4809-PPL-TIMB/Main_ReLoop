<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MitraPengepul;
use App\Models\User;
use App\Models\WilayahKecamatan;
use App\Models\WilayahKelurahan;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** 8.1.4 · Data pengepul mitra: peran, CRUD admin, verifikasi, pengaturan pengepul (PPLTIMB-147). */
class MitraPengepulTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $pengepul;
    private User $pengguna;
    private MitraPengepul $mitra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);

        $this->admin = $this->akun(User::ADMIN);
        $this->pengguna = User::factory()->create();
        [$this->pengepul, $this->mitra] = $this->mitraBaru('Bank Sampah Melati');
    }

    private function akun(string $peran): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->forceFill(['role' => $peran])->save());
    }

    private function mitraBaru(string $nama, string $status = MitraPengepul::MENUNGGU): array
    {
        $user = $this->akun(User::PENGEPUL);
        $mitra = $user->mitra()->create([
            'nama_usaha' => $nama, 'telepon' => '081234567890', 'alamat' => 'Jl. Melati 1',
            'kelurahan_id' => WilayahKelurahan::first()->id,
        ]);
        $mitra->forceFill(['status' => $status])->save();

        return [$user, $mitra];
    }

    private function dataMitra(array $ubah = []): array
    {
        return array_merge([
            'nama_usaha' => 'Pengepul Jaya Abadi', 'name' => 'Budi Santoso', 'email' => 'budi@pengepul.test',
            'password' => 'rahasia123', 'telepon' => '0812-3456-7890', 'alamat' => 'Jl. Kenanga 5',
            'kelurahan_id' => WilayahKelurahan::first()->id,
        ], $ubah);
    }

    private function kategori(string $slug): int
    {
        return Category::where('slug', $slug)->value('id');
    }

    private function dataPengaturan(array $ubah = []): array
    {
        return array_replace([
            'layanan_jemput' => '1',
            'layanan_antar'  => '1',
            'area'           => WilayahKecamatan::orderBy('id')->limit(2)->pluck('id')->all(),
            'harga'          => [$this->kategori('plastik') => '3.500', $this->kategori('kertas') => '2000', $this->kategori('logam') => ''],
            'jam'            => [
                1 => ['aktif' => '1', 'buka' => '08:00', 'tutup' => '16:00'],
                6 => ['aktif' => '1', 'buka' => '09:00', 'tutup' => '12:00'],
                7 => ['buka' => '08:00', 'tutup' => '16:00'], // tidak dicentang = libur
            ],
        ], $ubah);
    }

    // --- Peran & pembatasan halaman -------------------------------------------------

    public function test_akun_baru_berperan_pengguna_dan_peran_tidak_bisa_diisi_massal(): void
    {
        $user = User::create(['name' => 'Coba', 'email' => 'coba@reloop.test', 'password' => 'password', 'role' => User::ADMIN]);

        $this->assertSame(User::PENGGUNA, $user->fresh()->role);
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/mitra')->assertRedirect('/masuk');
        $this->get('/mitra/pengaturan')->assertRedirect('/masuk');
        $this->post('/admin/mitra')->assertRedirect('/masuk');
    }

    public function test_halaman_dibatasi_sesuai_peran(): void
    {
        $halamanAdmin = ['/admin/mitra', '/admin/mitra/tambah', "/admin/mitra/{$this->mitra->id}", "/admin/mitra/{$this->mitra->id}/ubah"];

        foreach ($halamanAdmin as $url) {
            $this->actingAs($this->pengguna)->get($url)->assertForbidden();
            $this->actingAs($this->pengepul)->get($url)->assertForbidden();
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        $this->actingAs($this->pengguna)->get('/mitra/pengaturan')->assertForbidden();
        $this->actingAs($this->admin)->get('/mitra/pengaturan')->assertForbidden();
        $this->actingAs($this->pengepul)->get('/mitra/pengaturan')->assertOk();

        $this->actingAs($this->pengepul)->post("/admin/mitra/{$this->mitra->id}/verifikasi", ['status' => 'terverifikasi'])->assertForbidden();
        $this->actingAs($this->pengguna)->delete("/admin/mitra/{$this->mitra->id}")->assertForbidden();
        $this->assertSame(MitraPengepul::MENUNGGU, $this->mitra->fresh()->status);
    }

    public function test_login_diarahkan_sesuai_peran(): void
    {
        foreach ([[$this->admin, '/admin/mitra'], [$this->pengepul, '/mitra/pengaturan'], [$this->pengguna, '/profil']] as [$user, $tujuan]) {
            $this->post('/masuk', ['identifier' => $user->email, 'password' => 'password'])->assertRedirect($tujuan);
            $this->post('/keluar');
        }
    }

    public function test_menu_akun_sesuai_peran(): void
    {
        $this->actingAs($this->admin)->get('/profil')->assertOk()->assertSee('Kelola mitra pengepul')->assertSee(route('admin.mitra.index'));
        $this->actingAs($this->pengepul)->get('/profil')->assertOk()->assertSee('Pengaturan mitra')->assertDontSee('Kelola mitra pengepul');
        $this->actingAs($this->pengguna)->get('/profil')->assertOk()->assertSee('Jadi pengepul mitra')->assertDontSee('Kelola mitra pengepul');
    }

    // --- Admin: daftar, tambah, ubah -----------------------------------------------

    public function test_admin_melihat_daftar_filter_status_dan_cari(): void
    {
        $this->mitraBaru('Lapak Kenari', MitraPengepul::TERVERIFIKASI);
        $this->mitraBaru('Pengepul Ditolak', MitraPengepul::DITOLAK);

        $this->actingAs($this->admin)->get('/admin/mitra')->assertOk()
            ->assertSee('Bank Sampah Melati')->assertSee('Lapak Kenari')->assertSee('Pengepul Ditolak');

        $this->get('/admin/mitra?status=terverifikasi')->assertOk()
            ->assertSee('Lapak Kenari')->assertDontSee('Bank Sampah Melati')->assertDontSee('Pengepul Ditolak');

        $this->get('/admin/mitra?q=melati')->assertOk()
            ->assertSee('Bank Sampah Melati')->assertDontSee('Lapak Kenari');

        $this->get('/admin/mitra?q=tidak-ada-yang-cocok')->assertOk()->assertSee('Mitra tidak ditemukan');
    }

    public function test_admin_menambah_mitra_beserta_akun_pengepul(): void
    {
        $res = $this->actingAs($this->admin)->post('/admin/mitra', $this->dataMitra());

        $mitra = MitraPengepul::where('nama_usaha', 'Pengepul Jaya Abadi')->firstOrFail();
        $res->assertRedirect(route('admin.mitra.show', $mitra));

        $this->assertSame(MitraPengepul::MENUNGGU, $mitra->status);
        $this->assertSame('081234567890', $mitra->telepon);
        $this->assertSame(User::PENGEPUL, $mitra->user->role);
        $this->assertSame('budi@pengepul.test', $mitra->user->email);
        $this->assertNotNull($mitra->user->email_verified_at);
        $this->assertTrue(Hash::check('rahasia123', $mitra->user->password));

        // Akun baru langsung bisa masuk ke halaman pengaturan mitra
        $this->post('/keluar');
        $this->post('/masuk', ['identifier' => 'budi@pengepul.test', 'password' => 'rahasia123'])->assertRedirect('/mitra/pengaturan');
    }

    public function test_validasi_tambah_mitra(): void
    {
        $this->actingAs($this->admin)->from('/admin/mitra/tambah')
            ->post('/admin/mitra', $this->dataMitra(['email' => $this->pengguna->email, 'telepon' => '12345', 'password' => 'pendek', 'nama_usaha' => '']))
            ->assertRedirect('/admin/mitra/tambah')
            ->assertSessionHasErrors(['email', 'telepon', 'password', 'nama_usaha']);

        $this->post('/admin/mitra', $this->dataMitra(['kelurahan_id' => 999999]))->assertSessionHasErrors('kelurahan_id');
        $this->assertSame(1, MitraPengepul::count());
    }

    public function test_admin_mengubah_mitra_dan_kata_sandi_opsional(): void
    {
        $hashLama = $this->pengepul->password;

        $this->actingAs($this->admin)->put("/admin/mitra/{$this->mitra->id}", $this->dataMitra([
            'nama_usaha' => 'Bank Sampah Melati Baru', 'email' => $this->pengepul->email, 'password' => '',
        ]))->assertRedirect(route('admin.mitra.show', $this->mitra))->assertSessionHasNoErrors();

        $this->assertSame('Bank Sampah Melati Baru', $this->mitra->fresh()->nama_usaha);
        $this->assertSame('Budi Santoso', $this->pengepul->fresh()->name);
        $this->assertSame($hashLama, $this->pengepul->fresh()->password);

        $this->put("/admin/mitra/{$this->mitra->id}", $this->dataMitra(['email' => $this->pengepul->email, 'password' => 'sandibaru99']));
        $this->assertTrue(Hash::check('sandibaru99', $this->pengepul->fresh()->password));
    }

    // --- Verifikasi ----------------------------------------------------------------

    public function test_admin_memverifikasi_menolak_dan_mengembalikan_status(): void
    {
        $url = "/admin/mitra/{$this->mitra->id}/verifikasi";
        $this->actingAs($this->admin);

        $this->post($url, ['status' => 'terverifikasi'])->assertRedirect(route('admin.mitra.show', $this->mitra));
        $mitra = $this->mitra->fresh();
        $this->assertSame(MitraPengepul::TERVERIFIKASI, $mitra->status);
        $this->assertNotNull($mitra->diverifikasi_pada);
        $this->assertSame($this->admin->id, $mitra->diverifikasi_oleh);

        $this->post($url, ['status' => 'ditolak'])->assertSessionHasErrors('catatan');
        $this->assertSame(MitraPengepul::TERVERIFIKASI, $this->mitra->fresh()->status);

        $this->post($url, ['status' => 'ditolak', 'catatan' => 'Alamat lapak tidak ditemukan'])->assertSessionHasNoErrors();
        $mitra = $this->mitra->fresh();
        $this->assertSame(MitraPengepul::DITOLAK, $mitra->status);
        $this->assertSame('Alamat lapak tidak ditemukan', $mitra->catatan_verifikasi);

        $this->post($url, ['status' => 'menunggu'])->assertSessionHasNoErrors();
        $mitra = $this->mitra->fresh();
        $this->assertSame(MitraPengepul::MENUNGGU, $mitra->status);
        $this->assertNull($mitra->catatan_verifikasi);
        $this->assertNull($mitra->diverifikasi_pada);

        $this->post($url, ['status' => 'aktif'])->assertSessionHasErrors('status');
    }

    public function test_status_dan_alasan_tolak_tampil_ke_pengepul(): void
    {
        $this->actingAs($this->pengepul)->get('/mitra/pengaturan')->assertSee('Menunggu verifikasi admin');

        $this->mitra->verifikasi(MitraPengepul::DITOLAK, $this->admin, 'Foto lapak belum jelas');
        $this->get('/mitra/pengaturan')->assertSee('Verifikasi ditolak admin')->assertSee('Foto lapak belum jelas');

        $this->mitra->verifikasi(MitraPengepul::TERVERIFIKASI, $this->admin);
        $this->get('/mitra/pengaturan')->assertSee('Akunmu terverifikasi')->assertDontSee('Foto lapak belum jelas');
    }

    public function test_hanya_mitra_terverifikasi_yang_bisa_dicari(): void
    {
        [, $terverifikasi] = $this->mitraBaru('Lapak Kenari', MitraPengepul::TERVERIFIKASI);
        $this->mitraBaru('Pengepul Ditolak', MitraPengepul::DITOLAK);

        $this->assertSame([$terverifikasi->id], MitraPengepul::terverifikasi()->pluck('id')->all());
    }

    public function test_admin_menghapus_mitra_dan_akun_kembali_jadi_pengguna(): void
    {
        $this->actingAs($this->pengepul)->put('/mitra/pengaturan', $this->dataPengaturan());
        $this->assertDatabaseCount('mitra_harga', 2);

        $this->actingAs($this->admin)->delete("/admin/mitra/{$this->mitra->id}")->assertRedirect('/admin/mitra');

        $this->assertDatabaseMissing('mitra_pengepul', ['id' => $this->mitra->id]);
        $this->assertDatabaseCount('mitra_harga', 0);
        $this->assertDatabaseCount('mitra_jam', 0);
        $this->assertDatabaseCount('mitra_area', 0);
        $this->assertSame(User::PENGGUNA, $this->pengepul->fresh()->role);
        $this->actingAs($this->pengepul->fresh())->get('/mitra/pengaturan')->assertForbidden();
    }

    // --- Pengaturan pengepul -------------------------------------------------------

    public function test_pengepul_menyimpan_area_harga_jam_dan_layanan(): void
    {
        $this->actingAs($this->pengepul)->put('/mitra/pengaturan', $this->dataPengaturan())
            ->assertRedirect('/mitra/pengaturan')->assertSessionHasNoErrors();

        $mitra = $this->mitra->fresh(['area', 'harga', 'jam']);
        $this->assertTrue($mitra->layanan_jemput);
        $this->assertTrue($mitra->layanan_antar);
        $this->assertEqualsCanonicalizing(WilayahKecamatan::orderBy('id')->limit(2)->pluck('id')->all(), $mitra->area->pluck('id')->all());
        $this->assertSame([$this->kategori('plastik') => 3500, $this->kategori('kertas') => 2000],
            $mitra->harga->sortBy('category_id')->pluck('harga_per_kg', 'category_id')->all());
        $this->assertSame([1, 6], $mitra->jam->pluck('hari')->all());
        $this->assertSame('09.00–12.00', $mitra->jam->firstWhere('hari', 6)->rentang());

        // Halaman menampilkan kembali isian tersimpan, dan admin bisa melihatnya
        $this->get('/mitra/pengaturan')->assertOk()->assertSee('value="3.500"', false);
        $this->actingAs($this->admin)->get("/admin/mitra/{$this->mitra->id}")->assertOk()
            ->assertSee('Rp 3.500/kg')->assertSee('09.00–12.00')->assertSee('Jemput · Antar Sendiri');
    }

    public function test_pengaturan_ulang_mengganti_data_lama(): void
    {
        $this->actingAs($this->pengepul)->put('/mitra/pengaturan', $this->dataPengaturan());

        $area = [WilayahKecamatan::orderByDesc('id')->value('id')];
        $this->put('/mitra/pengaturan', $this->dataPengaturan([
            'layanan_jemput' => '0',
            'area'  => $area,
            'harga' => [$this->kategori('kaca') => '500'],
            'jam'   => [3 => ['aktif' => '1', 'buka' => '07:30', 'tutup' => '15:00']],
        ]))->assertSessionHasNoErrors();

        $mitra = $this->mitra->fresh(['area', 'harga', 'jam']);
        $this->assertFalse($mitra->layanan_jemput);
        $this->assertSame($area, $mitra->area->pluck('id')->all());
        $this->assertSame([$this->kategori('kaca')], $mitra->harga->pluck('category_id')->all());
        $this->assertSame([3], $mitra->jam->pluck('hari')->all());
        $this->assertDatabaseCount('mitra_harga', 1);
        $this->assertDatabaseCount('mitra_jam', 1);
    }

    public function test_validasi_pengaturan(): void
    {
        $this->actingAs($this->pengepul)->from('/mitra/pengaturan');
        $kirim = fn (array $ubah) => $this->put('/mitra/pengaturan', $this->dataPengaturan($ubah));

        $kirim(['area' => []])->assertSessionHasErrors('area');
        $kirim(['area' => [999999]])->assertSessionHasErrors('area.0');
        $kirim(['harga' => [$this->kategori('plastik') => '', $this->kategori('kertas') => '']])->assertSessionHasErrors('harga');
        $kirim(['harga' => [$this->kategori('plastik') => '50']])->assertSessionHasErrors('harga.'.$this->kategori('plastik'));
        $kirim(['harga' => [$this->kategori('plastik') => 'murah']])->assertSessionHasErrors('harga.'.$this->kategori('plastik'));
        $kirim(['harga' => [$this->kategori('perabot') => '5000']])->assertSessionHasErrors('harga');
        $kirim(['jam' => [1 => ['buka' => '08:00', 'tutup' => '16:00']]])->assertSessionHasErrors('jam');
        $kirim(['jam' => [2 => ['aktif' => '1', 'buka' => '16:00', 'tutup' => '08:00']]])->assertSessionHasErrors('jam.2.tutup');
        $kirim(['jam' => [2 => ['aktif' => '1', 'buka' => '', 'tutup' => '08:00']]])->assertSessionHasErrors('jam.2.buka');
        $kirim(['layanan_jemput' => '0', 'layanan_antar' => '0'])->assertSessionHasErrors('layanan');

        $this->assertDatabaseCount('mitra_harga', 0);
        $this->assertDatabaseCount('mitra_jam', 0);
    }

    public function test_isian_dipulihkan_saat_validasi_gagal(): void
    {
        $this->actingAs($this->pengepul)->from('/mitra/pengaturan')
            ->put('/mitra/pengaturan', $this->dataPengaturan(['area' => []]));

        $html = $this->get('/mitra/pengaturan')->assertOk()
            ->assertSee('Pilih minimal satu kecamatan')
            ->assertSee('value="3.500"', false) // bukan "3" (3.500 tidak dibaca sebagai desimal)
            ->assertSee('value="09:00"', false)
            ->getContent();

        // Senin dicentang, Minggu tidak dicentang walau jamnya ikut terkirim
        $this->assertMatchesRegularExpression('/name="jam\[1\]\[aktif\]" value="1" checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="jam\[7\]\[aktif\]" value="1" checked/', $html);
        $this->assertMatchesRegularExpression('/name="jam\[7\]\[buka\]"[^>]*disabled/', $html);
    }

    public function test_pengepul_tanpa_data_mitra_mendapat_404(): void
    {
        $this->actingAs($this->akun(User::PENGEPUL))->get('/mitra/pengaturan')->assertNotFound();
    }

    // --- Seeder --------------------------------------------------------------------

    public function test_seeder_menyediakan_akun_demo_admin_dan_pengepul(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@reloop.test')->firstOrFail();
        $pengepul = User::where('email', 'pengepul@reloop.test')->firstOrFail();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($pengepul->isPengepul());
        $this->assertNotNull($pengepul->mitra);
        $this->assertTrue(Hash::check('password', $admin->password));

        $this->post('/masuk', ['identifier' => 'admin@reloop.test', 'password' => 'password'])->assertRedirect('/admin/mitra');
        $this->get('/admin/mitra')->assertOk()->assertSee($pengepul->mitra->nama_usaha);
    }
}
