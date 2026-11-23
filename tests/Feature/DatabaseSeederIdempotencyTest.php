<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_the_full_seeder_twice_keeps_counts_ids_and_relations(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expectedCounts = [
            'wilayah_provinsi' => 1,
            'wilayah_kabupaten' => 3,
            'wilayah_kecamatan' => 9,
            'wilayah_kelurahan' => 44,
            'categories' => 12,
            'users' => 4, // pembeli, penjual, admin, pengepul (8.1)
            'profiles' => 2,
            'listings' => 64,
            'listing_photos' => 64,
            'orders' => 2,
            'payments' => 2,
            'mitra_pengepul' => 1,
        ];

        $ids = [];
        foreach ($expectedCounts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
            $ids[$table] = DB::table($table)->orderBy('id')->pluck('id')->all();
        }

        $this->seed(DatabaseSeeder::class);

        foreach ($expectedCounts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
            $this->assertSame($ids[$table], DB::table($table)->orderBy('id')->pluck('id')->all(), $table);
        }

        $this->assertSame(0, Listing::doesntHave('category')->count());
        $this->assertSame(0, Listing::doesntHave('photos')->count());
        $this->assertSame(0, DB::table('orders')->leftJoin('listings', 'orders.listing_id', '=', 'listings.id')->whereNull('listings.id')->count());
        $this->assertSame(0, DB::table('payments')->leftJoin('orders', 'payments.order_id', '=', 'orders.id')->whereNull('orders.id')->count());
    }

    public function test_reseeding_preserves_existing_accounts_orders_and_other_users(): void
    {
        $this->seed(DatabaseSeeder::class);
        $buyer = User::where('email', 'pembeli@reloop.test')->firstOrFail();
        $buyer->update(['name' => 'Nama pembeli diubah', 'password' => 'password-baru']);
        $buyer->profile->update(['bio' => 'Profil sudah disesuaikan']);
        $other = User::factory()->create();
        $passwordHash = $buyer->fresh()->password;
        $order = Order::firstOrFail();
        $order->update(['status' => Order::STATUS_SELESAI]);
        $order->listing->update(['status' => Listing::TERJUAL]);
        $soldListings = Listing::whereIn('title', ['Headset Bluetooth', 'HP Samsung A52 128GB'])->get();
        $this->assertCount(2, $soldListings);
        foreach ($soldListings as $listing) {
            $listing->update(['status' => Listing::TERJUAL]);
        }

        $this->seed(DatabaseSeeder::class);

        $this->assertSame('Nama pembeli diubah', $buyer->fresh()->name);
        $this->assertSame($passwordHash, $buyer->fresh()->password);
        $this->assertSame('Profil sudah disesuaikan', $buyer->profile->fresh()->bio);
        $this->assertDatabaseHas('users', ['id' => $other->id, 'email' => $other->email]);
        $this->assertDatabaseCount('users', 5);
        $this->assertSame(Order::STATUS_SELESAI, $order->fresh()->status);
        $this->assertSame(Listing::TERJUAL, $order->listing->fresh()->status);
        foreach ($soldListings as $listing) {
            $this->assertSame(Listing::TERJUAL, $listing->fresh()->status);
        }
    }

    public function test_demo_regions_are_found_when_existing_rows_change_generated_ids(): void
    {
        $province = DB::table('wilayah_provinsi')->insertGetId(['kode' => '99', 'nama' => 'Provinsi lain']);
        $city = DB::table('wilayah_kabupaten')->insertGetId(['provinsi_id' => $province, 'kode' => '9999', 'nama' => 'Kota lain']);
        $district = DB::table('wilayah_kecamatan')->insertGetId(['kabupaten_id' => $city, 'kode' => '9999000', 'nama' => 'Kecamatan lain']);
        DB::table('wilayah_kelurahan')->insert(['id' => 1000, 'kecamatan_id' => $district, 'kode' => '9999000001', 'nama' => 'Kelurahan lain']);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('listings', 64);
        $this->assertDatabaseCount('listing_photos', 64);
        $this->assertSame(0, Listing::doesntHave('kelurahan')->count());
    }
}
