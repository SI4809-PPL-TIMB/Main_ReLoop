<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategorisasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_semua_endpoint_memerlukan_login(): void
    {
        foreach (['/pilih-kategori', '/pasang-barang', '/pengepul/sementara'] as $url) {
            $this->get($url)->assertRedirect('/masuk');
        }
        $this->post('/pilih-kategori', ['tipe' => 'bekas'])->assertRedirect('/masuk');
    }

    public function test_pilihan_mengikuti_tipe_dan_ketersediaan_data_master(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getJson('/pilih-kategori')->assertOk()
            ->assertJsonPath('tipe.0.value', Category::TYPE_BEKAS)
            ->assertJsonPath('tipe.0.tersedia', false);

        $this->seed(CategorySeeder::class);
        $this->getJson('/pilih-kategori')->assertOk()->assertJsonCount(2, 'tipe')
            ->assertJsonPath('tipe.0.label', 'Barang Bekas')
            ->assertJsonPath('tipe.0.tersedia', true)
            ->assertJsonPath('tipe.1.value', Category::TYPE_DAUR_ULANG)
            ->assertJsonPath('tipe.1.tersedia', true);
    }

    public function test_pilihan_valid_mengarah_ke_tujuan_yang_tepat(): void
    {
        $this->seed(CategorySeeder::class);
        $this->actingAs(User::factory()->create());
        $this->post('/pilih-kategori', ['tipe' => 'bekas'])
            ->assertRedirect(route('barang.create', ['tipe' => 'bekas']));
        $this->post('/pilih-kategori', ['tipe' => 'daur_ulang'])
            ->assertRedirect(route('pengepul.sementara', ['tipe' => 'daur_ulang']));
    }

    public function test_pilihan_tidak_valid_ditolak(): void
    {
        $this->seed(CategorySeeder::class);
        $this->actingAs(User::factory()->create());
        foreach ([[], ['tipe' => 'lainnya'], ['tipe' => ['bekas']], ['tipe' => 'https://example.com']] as $input) {
            $this->postJson('/pilih-kategori', $input)->assertUnprocessable()->assertJsonValidationErrors('tipe');
        }
    }

    public function test_tipe_tanpa_kategori_tidak_bisa_dilanjutkan(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/pilih-kategori', ['tipe' => 'bekas'])
            ->assertUnprocessable()->assertJsonValidationErrors('tipe');
    }

    public function test_tujuan_memuat_kategori_dari_database_sesuai_alur(): void
    {
        $this->seed(CategorySeeder::class);
        $this->actingAs(User::factory()->create());
        Category::where('slug', 'perabot')->update(['name' => 'Perabot diperbarui']);

        $this->getJson('/pasang-barang?tipe=daur_ulang')->assertOk()
            ->assertJsonPath('tipe', 'bekas')
            ->assertJsonPath('status', 'menunggu_form_4_2')
            ->assertJsonCount(8, 'kategori')
            ->assertJsonPath('kategori.0.name', 'Perabot diperbarui');
        $this->getJson('/pengepul/sementara?tipe=bekas')->assertOk()
            ->assertJsonPath('tipe', 'daur_ulang')
            ->assertJsonPath('status', 'pencarian_belum_tersedia')
            ->assertJsonCount(5, 'kategori')
            ->assertJsonPath('kategori.0.slug', 'plastik');
    }
}
