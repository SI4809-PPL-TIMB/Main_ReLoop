<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeder 7 kategori Barang Bekas sesuai acceptance criteria PPLTIMB-31:
 * Perabot, Pakaian, Buku, Elektronik, Mainan, Sepeda, Gadget.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Perabot',    'slug' => 'perabot',    'icon' => 'sofa',         'sort_order' => 1],
            ['name' => 'Pakaian',    'slug' => 'pakaian',    'icon' => 'shirt',         'sort_order' => 2],
            ['name' => 'Buku',       'slug' => 'buku',       'icon' => 'book',          'sort_order' => 3],
            ['name' => 'Elektronik', 'slug' => 'elektronik', 'icon' => 'tv',            'sort_order' => 4],
            ['name' => 'Mainan',     'slug' => 'mainan',     'icon' => 'puzzle-piece',  'sort_order' => 5],
            ['name' => 'Sepeda',     'slug' => 'sepeda',     'icon' => 'bicycle',       'sort_order' => 6],
            ['name' => 'Gadget',     'slug' => 'gadget',     'icon' => 'smartphone',    'sort_order' => 7],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
