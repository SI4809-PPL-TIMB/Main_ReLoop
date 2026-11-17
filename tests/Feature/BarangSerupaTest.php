<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Aturan barang serupa di detail: sekategori dulu (maks 6); bila < 3, ditambah barang lain sampai 6. */
class BarangSerupaTest extends TestCase
{
    use RefreshDatabase;

    private User $penjual;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CategorySeeder::class);
        $this->penjual = User::factory()->create();
        $this->penjual->profile()->create();
    }

    private function barang(Category $kategori, string $judul): Listing
    {
        return Listing::create([
            'user_id' => $this->penjual->id, 'category_id' => $kategori->id, 'title' => $judul,
            'description' => 'Deskripsi', 'price' => 10000, 'condition' => 'baik',
            'status' => Listing::AKTIF, 'published_at' => now(),
        ]);
    }

    public function test_kurang_dari_tiga_sekategori_ditambah_barang_lain_sampai_enam(): void
    {
        [$a, $b] = Category::where('type', 'bekas')->take(2)->get()->all();
        $utama = $this->barang($a, 'Utama');
        $sekategori = $this->barang($a, 'Sekategori');
        foreach (range(1, 7) as $i) {
            $this->barang($b, "Lain $i");
        }

        $serupa = $this->actingAs(User::factory()->create())->get(route('barang.detail', $utama))
            ->assertOk()->viewData('serupa');

        $this->assertCount(6, $serupa);
        $this->assertSame($sekategori->id, $serupa->first()->id);
        $this->assertNotContains($utama->id, $serupa->pluck('id'));
    }

    public function test_tiga_atau_lebih_sekategori_hanya_sekategori(): void
    {
        [$a, $b] = Category::where('type', 'bekas')->take(2)->get()->all();
        $utama = $this->barang($a, 'Utama');
        foreach (range(1, 3) as $i) {
            $this->barang($a, "Sekategori $i");
        }
        $this->barang($b, 'Lain');

        $serupa = $this->actingAs(User::factory()->create())->get(route('barang.detail', $utama))
            ->assertOk()->viewData('serupa');

        $this->assertCount(3, $serupa);
        $this->assertTrue($serupa->every(fn ($s) => $s->category_id === $a->id));
    }

    public function test_detail_barang_tidak_memuat_ulang_penjual_yang_sama(): void
    {
        $kategori = Category::where('type', 'bekas')->first();
        $utama = $this->barang($kategori, 'Utama');
        foreach (range(1, 5) as $i) {
            $this->barang($kategori, "Sekategori $i");
        }
        $pembeli = User::factory()->create();

        DB::enableQueryLog();
        $this->actingAs($pembeli)->get(route('barang.detail', $utama))->assertOk();
        $userQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "users"'));

        // Hanya route binding auth + relasi penjual barang utama; barang serupa memakai ulang relasi itu.
        $this->assertLessThanOrEqual(1, $userQueries->count(), $userQueries->pluck('query')->implode("\n"));
    }
}
