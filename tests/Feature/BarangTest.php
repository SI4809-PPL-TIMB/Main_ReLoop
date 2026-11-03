<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BarangTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);
        $this->user = User::factory()->create();
        $this->user->profile()->create(['kelurahan_id' => WilayahKelurahan::first()->id]);
    }

    private function payload(array $ubah = []): array
    {
        return array_merge([
            'title' => 'Kursi Kayu Jati', 'description' => 'Masih kuat', 'price' => '350.000', 'is_negotiable' => '1',
            'category_id' => Category::where('type', 'bekas')->value('id'), 'condition' => 'baik', 'quantity' => 2,
            'photos' => [UploadedFile::fake()->image('a.jpg', 3000, 2000)], 'action' => 'publish',
        ], $ubah);
    }

    private function barang(array $ubah = [], ?User $pemilik = null): Listing
    {
        return Listing::create(array_merge([
            'user_id' => ($pemilik ?? $this->user)->id, 'category_id' => Category::where('type', 'bekas')->value('id'),
            'kelurahan_id' => WilayahKelurahan::first()->id, 'title' => 'Meja Belajar', 'price' => 150000,
            'condition' => 'baik', 'status' => 'aktif',
        ], $ubah));
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/barang-saya')->assertRedirect('/masuk');
        $this->get('/jual')->assertRedirect('/masuk');
        $this->post('/jual')->assertRedirect('/masuk');
    }

    public function test_form_pasang_barang_tampil(): void
    {
        $this->actingAs($this->user)->get('/jual')->assertOk()->assertSee('Pasang Barang')->assertSee('Perabot');
    }

    public function test_pasang_barang_menyimpan_data_dan_foto_terkompres(): void
    {
        $this->actingAs($this->user)->post('/jual', $this->payload())->assertRedirect('/barang-saya');

        $l = Listing::firstOrFail();
        $this->assertSame($this->user->id, $l->user_id);
        $this->assertSame('aktif', $l->status);
        $this->assertSame(350000, (int) $l->price);
        $this->assertSame($this->user->profile->kelurahan_id, $l->kelurahan_id);
        $this->assertCount(1, $l->photos);
        Storage::disk('public')->assertExists($l->photos[0]->path);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($l->photos[0]->path));
        $this->assertSame(1280, max($w, $h));
    }

    public function test_foto_wajib_minimal_satu_dan_maksimal_lima(): void
    {
        $this->actingAs($this->user);
        $this->post('/jual', $this->payload(['photos' => []]))->assertSessionHasErrors('photos');
        $enam = array_map(fn ($i) => UploadedFile::fake()->image("f$i.jpg"), range(1, 6));
        $this->post('/jual', $this->payload(['photos' => $enam]))->assertSessionHasErrors('photos');
        $lima = array_map(fn ($i) => UploadedFile::fake()->image("f$i.jpg"), range(1, 5));
        $this->post('/jual', $this->payload(['photos' => $lima]))->assertSessionDoesntHaveErrors();
        $this->assertCount(5, Listing::first()->photos);
    }

    public function test_validasi_harga_kondisi_kategori_dan_file(): void
    {
        $this->actingAs($this->user);
        $daur = Category::where('type', 'daur_ulang')->value('id');
        $this->post('/jual', $this->payload(['price' => 'abc']))->assertSessionHasErrors('price');
        $this->post('/jual', $this->payload(['price' => '500']))->assertSessionHasErrors('price');
        $this->post('/jual', $this->payload(['condition' => 'rusak']))->assertSessionHasErrors('condition');
        $this->post('/jual', $this->payload(['category_id' => $daur]))->assertSessionHasErrors('category_id');
        $this->post('/jual', $this->payload(['title' => 'abc']))->assertSessionHasErrors('title');
        $this->post('/jual', $this->payload(['photos' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]]))->assertSessionHasErrors('photos.0');
        $this->assertSame(0, Listing::count());
    }

    public function test_tanpa_lokasi_tidak_bisa_pasang(): void
    {
        $this->user->profile->update(['kelurahan_id' => null]);
        $this->actingAs($this->user)->post('/jual', $this->payload())->assertSessionHasErrors('kelurahan_id');
    }

    public function test_simpan_draft_boleh_tanpa_foto_dan_tidak_tayang(): void
    {
        $this->actingAs($this->user)->post('/jual', $this->payload(['action' => 'draft', 'photos' => []]))->assertRedirect('/barang-saya');
        $l = Listing::first();
        $this->assertSame('draft', $l->status);
        $this->assertNull($l->published_at);

        $this->put("/barang/{$l->id}", $this->payload(['photos' => [UploadedFile::fake()->image('a.jpg')]]));
        $this->assertSame('aktif', $l->fresh()->status);
    }

    public function test_ubah_barang_tambah_dan_hapus_foto(): void
    {
        $this->actingAs($this->user)->post('/jual', $this->payload());
        $l = Listing::first();
        $lama = $l->photos[0];

        $this->put("/barang/{$l->id}", $this->payload(['title' => 'Kursi Baru', 'photos' => [UploadedFile::fake()->image('b.jpg')], 'remove_photos' => [$lama->id]]))->assertRedirect('/barang-saya');

        $l->refresh();
        $this->assertSame('Kursi Baru', $l->title);
        $this->assertCount(1, $l->photos);
        Storage::disk('public')->assertMissing($lama->path);
    }

    public function test_ubah_tidak_boleh_melebihi_lima_foto_total(): void
    {
        $this->actingAs($this->user)->post('/jual', $this->payload(['photos' => array_map(fn ($i) => UploadedFile::fake()->image("f$i.jpg"), range(1, 5))]));
        $l = Listing::first();
        $this->put("/barang/{$l->id}", $this->payload(['photos' => [UploadedFile::fake()->image('x.jpg')]]))->assertSessionHasErrors('photos');
    }

    public function test_hanya_pemilik_boleh_ubah_hapus_dan_tandai_terjual(): void
    {
        $l = $this->barang();
        $lain = User::factory()->create();
        $this->actingAs($lain);
        $this->get("/barang/{$l->id}/ubah")->assertForbidden();
        $this->put("/barang/{$l->id}", $this->payload())->assertForbidden();
        $this->delete("/barang/{$l->id}")->assertForbidden();
        $this->post("/barang/{$l->id}/terjual")->assertForbidden();
        $this->assertSame('aktif', $l->fresh()->status);
        $this->assertSame('Meja Belajar', $l->fresh()->title);
    }

    public function test_pemilik_hapus_dan_tandai_terjual(): void
    {
        $a = $this->barang(['title' => 'A barang']);
        $b = $this->barang(['title' => 'B barang']);
        $this->actingAs($this->user);

        $this->post("/barang/{$a->id}/terjual")->assertRedirect('/barang-saya?tab=terjual');
        $this->assertSame('terjual', $a->fresh()->status);
        $this->delete("/barang/{$b->id}")->assertRedirect('/barang-saya');
        $this->assertSame('dihapus', $b->fresh()->status);
        $this->get('/barang-saya')->assertDontSee('B barang');
    }

    public function test_barang_dipesan_tidak_bisa_dihapus_dan_barang_terjual_tidak_bisa_diubah(): void
    {
        $dipesan = $this->barang(['status' => 'dipesan']);
        $terjual = $this->barang(['status' => 'terjual']);
        $this->actingAs($this->user);
        $this->delete("/barang/{$dipesan->id}")->assertForbidden();
        $this->get("/barang/{$terjual->id}/ubah")->assertForbidden();
        $this->post("/barang/{$terjual->id}/terjual")->assertForbidden();
    }

    public function test_daftar_barang_saya_per_tab_dan_hanya_milik_sendiri(): void
    {
        $this->barang(['title' => 'Milikku aktif']);
        $this->barang(['title' => 'Milikku terjual', 'status' => 'terjual']);
        $this->barang(['title' => 'Milik orang lain'], User::factory()->create());

        $this->actingAs($this->user)->get('/barang-saya')->assertOk()
            ->assertSee('Milikku aktif')->assertDontSee('Milikku terjual')->assertDontSee('Milik orang lain')
            ->assertSee('Aktif (1)', false)->assertSee('Terjual (1)', false);
        $this->get('/barang-saya?tab=terjual')->assertSee('Milikku terjual');
    }
}
