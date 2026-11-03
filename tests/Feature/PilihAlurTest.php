<?php

namespace Tests\Feature;

use App\Livewire\PilihAlur;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PilihAlurTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_beranda_menautkan_halaman_pilihan_livewire(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/beranda')->assertOk()->assertSee('href="'.route('kategori.index').'"', false);
        $this->get('/pilih-kategori')->assertOk()->assertSeeLivewire(PilihAlur::class)
            ->assertSee('Barang Bekas')->assertSee('Daur Ulang')
            ->assertSee('name="_token"', false)->assertSee('action="'.route('kategori.pilih').'"', false);
    }

    public function test_livewire_memperbarui_pilihan_dan_label_tombol(): void
    {
        $this->seed(CategorySeeder::class);
        $this->actingAs(User::factory()->create());
        Livewire::test(PilihAlur::class)->assertSet('tipe', '')->assertViewHas('canContinue', false)
            ->set('tipe', 'bekas')->assertViewHas('canContinue', true)->assertSee('Lanjut ke Pasang Barang')
            ->set('tipe', 'daur_ulang')->assertSee('Lanjut ke Mitra Pengepul')
            ->set('tipe', 'lainnya')->assertViewHas('canContinue', false)->assertSee('Lanjutkan');
    }

    public function test_tujuan_browser_menampilkan_halaman_dan_menjaga_tipe(): void
    {
        $this->seed(CategorySeeder::class);
        $this->actingAs(User::factory()->create());
        $this->followingRedirects()->post('/pilih-kategori', ['tipe' => 'bekas'])
            ->assertOk()->assertSee('Pasang Barang')
            ->assertSee(route('barang.store'), false); // alur bekas langsung ke form 4.2
        $this->followingRedirects()->post('/pilih-kategori', ['tipe' => 'daur_ulang'])
            ->assertOk()->assertSee('Pencarian mitra segera hadir')
            ->assertSee(route('kategori.index', ['tipe' => 'daur_ulang']), false);
        $this->get('/pilih-kategori?tipe=daur_ulang')->assertOk()->assertSee('Lanjut ke Mitra Pengepul');
        $this->get('/pilih-kategori?tipe[]=bekas')->assertOk();
    }

    public function test_kesalahan_form_ditampilkan_dan_tipe_tanpa_kategori_nonaktif(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(PilihAlur::class)->set('tipe', 'bekas')
            ->assertViewHas('canContinue', false)->assertSee('Belum tersedia');
        $this->from('/pilih-kategori')->post('/pilih-kategori', ['tipe' => 'lainnya'])
            ->assertRedirect('/pilih-kategori')->assertSessionHasErrors('tipe');
        $this->get('/pilih-kategori')->assertOk()->assertSee('Jenis barang tidak valid.');
    }
}
