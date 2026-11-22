<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Models\User;
use Database\Seeders\BarangDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ListingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoListingPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_demo_listings_have_local_photos_and_reseeding_does_not_duplicate_them(): void
    {
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
        $this->seed(BarangDemoSeeder::class);

        $listings = Listing::with('photos')->get();
        $this->assertCount(64, $listings);

        foreach ($listings as $listing) {
            $this->assertCount(1, $listing->photos, $listing->title);
            $photo = $listing->photos->first();
            $this->assertFileExists(public_path($photo->path));
            $this->assertSame('image/jpeg', getimagesize(public_path($photo->path))['mime']);
            $this->assertSame(asset($photo->path), $photo->url);
        }

        $photoIds = ListingPhoto::orderBy('id')->pluck('id')->all();
        $this->seed([ListingSeeder::class, BarangDemoSeeder::class]);
        $this->assertSame(64, Listing::count());
        $this->assertSame($photoIds, ListingPhoto::orderBy('id')->pluck('id')->all());

        $buyer = User::where('email', 'pembeli@reloop.test')->firstOrFail();
        $sample = $listings->firstWhere('title', 'Headset Bluetooth');
        $photoUrl = $sample->photos->first()->url;
        $this->actingAs($buyer)->get(route('cari', ['q' => 'Headset Bluetooth']))
            ->assertOk()->assertSee($photoUrl, false);
        $this->get(route('barang.detail', $sample))
            ->assertOk()->assertSee($photoUrl, false);
    }

    public function test_uploaded_photos_keep_their_storage_urls(): void
    {
        $photo = new ListingPhoto(['path' => 'listings/123/foto.jpg']);
        $this->assertSame(asset('storage/listings/123/foto.jpg'), $photo->url);
    }
}
