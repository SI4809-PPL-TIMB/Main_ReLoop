<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MergeGadgetCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_gadget_listings_are_moved_without_affecting_recycling(): void
    {
        $electronics = Category::create(['name' => 'Elektronik', 'slug' => 'elektronik', 'type' => 'bekas']);
        $gadget = Category::create(['name' => 'Gadget', 'slug' => 'gadget', 'type' => 'bekas']);
        $recycling = Category::create(['name' => 'Elektronik bekas', 'slug' => 'elektronik-bekas', 'type' => 'daur_ulang']);
        $user = User::factory()->create();
        $listing = DB::table('listings')->insertGetId([
            'user_id' => $user->id, 'category_id' => $gadget->id, 'title' => 'Telepon',
            'price' => 100000, 'condition' => 'baik', 'status' => 'aktif',
        ]);

        $migration = require database_path('migrations/2026_11_18_000001_merge_gadget_into_elektronik_category.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseHas('listings', ['id' => $listing, 'category_id' => $electronics->id]);
        $this->assertDatabaseHas('categories', ['id' => $electronics->id, 'name' => 'Elektronik & Gadget']);
        $this->assertDatabaseMissing('categories', ['id' => $gadget->id]);
        $this->assertDatabaseHas('categories', ['id' => $recycling->id, 'name' => 'Elektronik bekas', 'type' => 'daur_ulang']);
    }

    public function test_gadget_is_renamed_if_electronics_does_not_exist(): void
    {
        $gadget = Category::create(['name' => 'Gadget', 'slug' => 'gadget', 'type' => 'bekas']);
        $migration = require database_path('migrations/2026_11_18_000001_merge_gadget_into_elektronik_category.php');
        $migration->up();

        $this->assertDatabaseHas('categories', ['id' => $gadget->id, 'slug' => 'elektronik', 'name' => 'Elektronik & Gadget']);
    }
}
