<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WilayahSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MergedCategoryUiTest extends TestCase
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
        $this->actingAs($this->user);
    }

    public function test_listing_form_search_and_home_offer_one_merged_category(): void
    {
        foreach (['barang.create' => 'kategori', 'cari' => 'daftarKategori', 'beranda' => 'kategori'] as $route => $variable) {
            $response = $this->get(route($route))->assertOk()->assertSee('Elektronik & Gadget');
            $categories = $response->viewData($variable);
            $this->assertCount(7, $categories);
            $this->assertSame(1, $categories->where('slug', 'elektronik')->count());
            $this->assertFalse($categories->pluck('name')->contains('Gadget'));
            $this->assertFalse($categories->pluck('name')->contains('Elektronik'));
            $this->assertFalse($categories->pluck('slug')->contains('elektronik-bekas'));
        }

        $form = $this->xpath($this->get(route('barang.create'))->getContent());
        $this->assertSame(7, $form->query('//input[@name="category_id"]')->length);

        $search = $this->xpath($this->get(route('cari'))->getContent());
        $this->assertSame(1, $search->query('//*[@id="filter"]//*[@name="kategori"]')->length);
        $this->assertSame(8, $search->query('//select[@name="kategori"]/option')->length);
        $this->assertSame(1, $search->query('//select[@name="kategori"]/option[@value="elektronik"]')->length);
        $this->assertSame(0, $search->query('//select[@name="kategori"]/option[@value="gadget"]')->length);

        $home = $this->xpath($this->get(route('beranda'))->getContent());
        $this->assertSame(7, $home->query('//section[@aria-label="Kategori barang bekas"]/a')->length);
    }

    public function test_migrated_gadget_listings_can_be_edited_and_found_with_electronics(): void
    {
        $electronics = Category::where('slug', 'elektronik')->firstOrFail();
        $electronics->update(['name' => 'Elektronik']);
        $gadget = Category::create(['name' => 'Gadget', 'slug' => 'gadget', 'type' => Category::TYPE_BEKAS]);
        $phone = $this->listing('Ponsel kategori Gadget lama', $gadget);
        $this->listing('Setrika kategori Elektronik lama', $electronics);
        $this->listing('Meja kayu untuk pengujian', Category::where('slug', 'perabot')->firstOrFail());
        $this->mergeCategories();

        $edit = $this->get(route('barang.edit', $phone))->assertOk()->assertSee('Elektronik & Gadget');
        $form = $this->xpath($edit->getContent());
        $this->assertSame(1, $form->query('//input[@name="category_id"][@checked][@value="'.$electronics->id.'"]')->length);

        foreach ([['kategori' => 'elektronik'], ['category_id' => $electronics->id]] as $filter) {
            $response = $this->get(route('cari', $filter))->assertOk()
                ->assertSee('Ponsel kategori Gadget lama')
                ->assertSee('Setrika kategori Elektronik lama')
                ->assertDontSee('Meja kayu untuk pengujian');
            $search = $this->xpath($response->getContent());
            $this->assertSame(1, $search->query('//select[@name="kategori"]/option[@value="elektronik"][@selected]')->length);
        }
    }

    public function test_publishing_accepts_the_merged_category_and_rejects_the_removed_gadget_id(): void
    {
        $gadget = Category::create(['name' => 'Gadget', 'slug' => 'gadget', 'type' => Category::TYPE_BEKAS]);
        $this->mergeCategories();
        $payload = fn ($category) => [
            'title' => 'Headset kategori gabungan', 'price' => 110000,
            'category_id' => $category, 'condition' => 'baik', 'action' => 'publish',
            'photos' => [UploadedFile::fake()->image('headset.jpg', 300, 200)],
        ];

        $this->post(route('barang.store'), $payload($gadget->id))->assertSessionHasErrors('category_id');
        $this->assertDatabaseCount('listings', 0);

        $electronics = Category::where('slug', 'elektronik')->firstOrFail();
        $this->post(route('barang.store'), $payload($electronics->id))
            ->assertSessionHasNoErrors()->assertRedirect(route('barang.index'));
        $this->assertDatabaseHas('listings', ['title' => 'Headset kategori gabungan', 'category_id' => $electronics->id]);
    }

    private function listing(string $title, Category $category): Listing
    {
        return Listing::create([
            'user_id' => $this->user->id, 'category_id' => $category->id,
            'kelurahan_id' => $this->user->profile->kelurahan_id,
            'title' => $title, 'price' => 100000, 'condition' => 'baik',
            'status' => Listing::AKTIF, 'published_at' => now(),
        ]);
    }

    private function mergeCategories(): void
    {
        $migration = require database_path('migrations/2026_11_18_000001_merge_gadget_into_elektronik_category.php');
        $migration->up();
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
