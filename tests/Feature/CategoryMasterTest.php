<?php

namespace Tests\Feature;

use App\Livewire\CategoryFilter;
use App\Livewire\CategorySelect;
use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryMasterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1.3.1: Skema tabel categories memiliki kolom type enum('bekas', 'daur_ulang').
     */
    public function test_categories_table_has_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn('categories', 'type'));
    }

    /**
     * 1.3.1: Model Category memiliki konstanta tipe dan scope query per tipe.
     */
    public function test_category_model_constants_and_scopes(): void
    {
        $this->assertSame('bekas', Category::TYPE_BEKAS);
        $this->assertSame('daur_ulang', Category::TYPE_DAUR_ULANG);

        Category::create([
            'name'       => 'Sofa Bekas',
            'slug'       => 'sofa-bekas',
            'type'       => Category::TYPE_BEKAS,
            'icon'       => 'sofa',
            'sort_order' => 1,
        ]);

        Category::create([
            'name'       => 'Botol Plastik',
            'slug'       => 'botol-plastik',
            'type'       => Category::TYPE_DAUR_ULANG,
            'icon'       => 'bottle',
            'sort_order' => 1,
        ]);

        $this->assertSame(1, Category::bekas()->count());
        $this->assertSame(1, Category::daurUlang()->count());
        $this->assertSame(1, Category::byType(Category::TYPE_BEKAS)->count());
        $this->assertSame(1, Category::byType(Category::TYPE_DAUR_ULANG)->count());
    }

    /**
     * 1.3.2: CategorySeeder mengisi 7 kategori barang bekas & 5 kategori daur ulang.
     */
    public function test_category_seeder_populates_all_expected_categories(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertSame(7, Category::bekas()->count());
        $this->assertSame(5, Category::daurUlang()->count());
        $this->assertSame(12, Category::count());

        $expectedBekas = ['perabot', 'pakaian', 'buku', 'elektronik', 'mainan', 'sepeda', 'gadget'];
        foreach ($expectedBekas as $slug) {
            $cat = Category::where('slug', $slug)->first();
            $this->assertNotNull($cat, "Kategori {$slug} tidak ditemukan.");
            $this->assertSame(Category::TYPE_BEKAS, $cat->type);
        }

        $expectedSampah = ['plastik', 'kertas', 'logam', 'kaca', 'elektronik-bekas'];
        foreach ($expectedSampah as $slug) {
            $cat = Category::where('slug', $slug)->first();
            $this->assertNotNull($cat, "Kategori sampah {$slug} tidak ditemukan.");
            $this->assertSame(Category::TYPE_DAUR_ULANG, $cat->type);
        }
    }

    /**
     * 1.3.2: Seeder idempoten (dapat dijalankan berulang tanpa duplikat).
     */
    public function test_category_seeder_is_idempotent(): void
    {
        $this->seed(CategorySeeder::class);
        $this->assertSame(12, Category::count());

        $this->seed(CategorySeeder::class);
        $this->assertSame(12, Category::count());
    }

    /**
     * 1.3.3: Category::forSelect() & Category::groupedByType() siap dipakai form & filter.
     */
    public function test_category_query_helpers(): void
    {
        $this->seed(CategorySeeder::class);

        $bekas = Category::forSelect(Category::TYPE_BEKAS);
        $this->assertCount(7, $bekas);

        $daurUlang = Category::forSelect(Category::TYPE_DAUR_ULANG);
        $this->assertCount(5, $daurUlang);

        $grouped = Category::groupedByType();
        $this->assertArrayHasKey(Category::TYPE_BEKAS, $grouped);
        $this->assertArrayHasKey(Category::TYPE_DAUR_ULANG, $grouped);
        $this->assertCount(7, $grouped[Category::TYPE_BEKAS]);
        $this->assertCount(5, $grouped[Category::TYPE_DAUR_ULANG]);
    }

    /**
     * 1.3.3: Komponen Livewire CategorySelect untuk form pasang iklan.
     */
    public function test_livewire_category_select_component(): void
    {
        $this->seed(CategorySeeder::class);

        $perabot = Category::where('slug', 'perabot')->first();

        Livewire::test(CategorySelect::class)
            ->assertSee('Perabot')
            ->assertSee('Pakaian')
            ->call('selectCategory', $perabot->id)
            ->assertSet('selectedCategoryId', $perabot->id)
            ->assertDispatched('category-selected')
            ->call('setType', Category::TYPE_DAUR_ULANG)
            ->assertSet('selectedType', Category::TYPE_DAUR_ULANG)
            ->assertSee('Plastik')
            ->assertSee('Kertas');
    }

    /**
     * 1.3.3: Komponen Livewire CategoryFilter untuk filter pencarian.
     */
    public function test_livewire_category_filter_component(): void
    {
        $this->seed(CategorySeeder::class);

        $plastik = Category::where('slug', 'plastik')->first();

        Livewire::test(CategoryFilter::class)
            ->assertSee('Semua')
            ->assertSee('Barang Bekas')
            ->assertSee('Sampah Daur Ulang')
            ->call('setType', Category::TYPE_DAUR_ULANG)
            ->assertSet('activeType', Category::TYPE_DAUR_ULANG)
            ->assertSee('Plastik')
            ->call('toggleCategory', $plastik->id)
            ->assertSet('selectedCategoryId', $plastik->id)
            ->assertDispatched('category-filtered');
    }
}

