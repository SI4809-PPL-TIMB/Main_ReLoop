<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WilayahKelurahan;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LokasiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WilayahSeeder::class);
        $this->user = User::factory()->create();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/lokasi')->assertRedirect('/masuk');
        $this->post('/lokasi')->assertRedirect('/masuk');
    }

    public function test_halaman_lokasi_tampil(): void
    {
        $this->actingAs($this->user)->get('/lokasi')->assertOk()->assertSee('Kamu tinggal di mana?');
        $this->actingAs($this->user)->get('/lokasi?from=profil')->assertSee('Ubah lokasi');
    }

    public function test_kaskade_wilayah(): void
    {
        $prov = $this->actingAs($this->user)->getJson('/wilayah/provinsi')->assertOk()->json();
        $kab = $this->getJson('/wilayah/kabupaten?parent='.$prov[0]['id'])->assertOk()->json();
        $this->assertNotEmpty($kab);
        $this->getJson('/wilayah/bukan-level')->assertNotFound();
    }

    public function test_cari_wilayah_berdasar_desa_kecamatan_atau_kota(): void
    {
        $this->actingAs($this->user);
        $this->getJson('/wilayah/cari?q=Katulampa')->assertJsonPath('0.kelurahan.nama', 'Katulampa');
        $this->getJson('/wilayah/cari?q=Beji')->assertJsonFragment(['nama' => 'Beji']);
        $this->getJson('/wilayah/cari?q=Depok')->assertJsonCount(18);
        $this->getJson('/wilayah/cari?q=zzzz')->assertExactJson([]);
        $this->getJson('/wilayah/cari?q=a')->assertExactJson([]);
    }

    public function test_simpan_lokasi_dengan_titik_peta_dan_breadcrumb(): void
    {
        $kel = WilayahKelurahan::where('nama', 'Katulampa')->first();

        $this->actingAs($this->user)->post('/lokasi', [
            'kelurahan_id' => $kel->id, 'latitude' => -6.6, 'longitude' => 106.82, 'patokan' => 'Gg. Melati',
        ])->assertRedirect('/profil');

        $this->assertDatabaseHas('profiles', ['user_id' => $this->user->id, 'kelurahan_id' => $kel->id, 'latitude' => -6.6, 'patokan' => 'Gg. Melati']);
        $this->get('/profil')->assertSee('Katulampa, Bogor Timur, Kabupaten Bogor, Jawa Barat');
    }

    public function test_simpan_lokasi_dua_kali_memperbarui_profil(): void
    {
        [$a, $b] = WilayahKelurahan::take(2)->get();
        $this->actingAs($this->user)->post('/lokasi', ['kelurahan_id' => $a->id]);
        $this->post('/lokasi', ['kelurahan_id' => $b->id]);
        $this->assertDatabaseCount('profiles', 1);
        $this->assertSame($b->id, $this->user->fresh()->profile->kelurahan_id);
    }

    public function test_validasi(): void
    {
        $this->actingAs($this->user);
        $this->post('/lokasi', [])->assertSessionHasErrors('kelurahan_id');
        $this->post('/lokasi', ['kelurahan_id' => 99999])->assertSessionHasErrors('kelurahan_id');
        $kel = WilayahKelurahan::first();
        $this->post('/lokasi', ['kelurahan_id' => $kel->id, 'latitude' => 95, 'longitude' => 10])->assertSessionHasErrors('latitude');
        $this->post('/lokasi', ['kelurahan_id' => $kel->id, 'latitude' => 10])->assertSessionHasErrors('longitude');
    }

    public function test_gps_mencocokkan_desa(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response(['address' => ['suburb' => 'Katulampa', 'city_district' => 'Bogor Timur']])]);
        $this->actingAs($this->user)->postJson('/lokasi/gps', ['latitude' => -6.6, 'longitude' => 106.82])
            ->assertOk()->assertJsonPath('kelurahan.nama', 'Katulampa');
    }

    public function test_gps_di_luar_data_wilayah_minta_pilih_manual(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response(['address' => ['village' => 'Tidak Ada']])]);
        $this->actingAs($this->user)->postJson('/lokasi/gps', ['latitude' => 1, 'longitude' => 1])->assertNotFound();
    }

    public function test_halaman_selesai_mengarahkan_ke_atur_lokasi(): void
    {
        $this->get('/selesai')->assertSee(route('lokasi'), false);
    }

    public function test_verifikasi_benar_langsung_ke_atur_lokasi(): void
    {
        $user = User::factory()->unverified()->create();

        $this->withSession(['otp_code' => '12345', 'pending_user_id' => $user->id, 'otp_expires_at' => now()->addMinutes(5)->timestamp])
            ->post('/verifikasi', ['otp' => '12345'])
            ->assertRedirect('/lokasi');
        $this->assertAuthenticatedAs($user);
    }
}
