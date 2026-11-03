<?php

namespace Tests\Feature;

use App\Livewire\PilihAlur;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KategorisasiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_memerlukan_csrf_yang_valid(): void
    {
        // Laravel biasanya melewati CSRF saat testing; aktifkan pemeriksaan middleware.
        $this->app['env'] = 'local';
        $this->seed(CategorySeeder::class);
        $this->actingAs(User::factory()->create())->withSession(['_token' => 'csrf-kategorisasi']);

        $this->post('/pilih-kategori', ['tipe' => 'bekas'])->assertStatus(419);
        $this->post('/pilih-kategori', ['tipe' => 'bekas', '_token' => 'salah'])->assertStatus(419);
        $this->post('/pilih-kategori', ['tipe' => 'bekas', '_token' => 'csrf-kategorisasi'])
            ->assertRedirect(route('barang.create', ['tipe' => 'bekas']));
    }

    public function test_kategori_dihapus_setelah_halaman_dibuka_tetap_ditolak_backend(): void
    {
        $this->seed(CategorySeeder::class);
        $this->actingAs(User::factory()->create());
        Livewire::test(PilihAlur::class)->set('tipe', 'bekas')->assertViewHas('canContinue', true);

        Category::bekas()->delete();
        $this->postJson('/pilih-kategori', ['tipe' => 'bekas'])
            ->assertUnprocessable()->assertJsonValidationErrors('tipe');
        $this->post('/pilih-kategori', ['tipe' => 'daur_ulang'])
            ->assertRedirect(route('pengepul.sementara', ['tipe' => 'daur_ulang']));
    }

    public function test_parameter_tambahan_tidak_mengganti_tujuan_atau_membuat_barang(): void
    {
        $this->seed(CategorySeeder::class);
        $this->actingAs(User::factory()->create());
        $this->post('/pilih-kategori', [
            'tipe' => 'bekas',
            'redirect' => 'https://example.com',
            'category_id' => Category::daurUlang()->value('id'),
            'title' => 'Barang tidak boleh dibuat pada langkah ini',
        ])->assertRedirect(route('barang.create', ['tipe' => 'bekas']));
        $this->assertDatabaseCount('listings', 0);
        $this->assertDatabaseCount('categories', 13);
    }

    public function test_query_tidak_valid_diabaikan_dan_tipe_valid_dipulihkan(): void
    {
        $this->seed(CategorySeeder::class);
        $this->actingAs(User::factory()->create());
        Livewire::withQueryParams(['tipe' => 'daur_ulang'])->test(PilihAlur::class)
            ->assertSet('tipe', 'daur_ulang')->assertViewHas('canContinue', true);
        Livewire::withQueryParams(['tipe' => 'lainnya'])->test(PilihAlur::class)
            ->assertSet('tipe', '')->assertViewHas('canContinue', false);
        Livewire::withQueryParams(['tipe' => ['bekas']])->test(PilihAlur::class)
            ->assertSet('tipe', '')->assertViewHas('canContinue', false);
    }
}
