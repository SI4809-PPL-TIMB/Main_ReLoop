<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BerandaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/beranda')->assertRedirect('/masuk');
    }

    public function test_pengguna_login_di_halaman_utama_dialihkan_ke_beranda(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/beranda');
    }

    public function test_beranda_menampilkan_lokasi_poin_dan_kategori(): void
    {
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);
        $user = User::factory()->create();
        $user->profile()->create(['kelurahan_id' => WilayahKelurahan::where('nama', 'Katulampa')->value('id'), 'total_points' => 1240, 'level' => 2]);

        $this->actingAs($user)->get('/beranda')->assertOk()
            ->assertSee('Katulampa')
            ->assertSee('1.240')
            ->assertSee('Lv 2')
            ->assertSee(Category::where('type', Category::TYPE_BEKAS)->value('name'))
            ->assertSee('Barang di sekitarmu')
            ->assertSee('Pengepul terdekat')
            ->assertSee('aria-current="page"', false);
    }

    public function test_beranda_tanpa_profil_mengajak_atur_lokasi(): void
    {
        $this->actingAs(User::factory()->create())->get('/beranda')->assertOk()->assertSee('Atur lokasi');
    }
}
