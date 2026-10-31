<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeder data master kategori ReLoop sesuai kriteria tugas 1.3:
 * - 7 Kategori Barang Bekas: Perabot, Pakaian, Buku, Elektronik, Mainan, Sepeda, Gadget
 * - 5 Kategori Sampah Daur Ulang: Plastik, Kertas, Logam, Kaca, Elektronik bekas
 *
 * Idempoten: aman dijalankan berulang tanpa membuat data duplikat.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // --- Kategori Barang Bekas (type: bekas) ---
            [
                'name'       => 'Perabot',
                'slug'       => 'perabot',
                'type'       => Category::TYPE_BEKAS,
                'icon'       => 'sofa',
                'sort_order' => 1,
            ],
            [
                'name'       => 'Pakaian',
                'slug'       => 'pakaian',
                'type'       => Category::TYPE_BEKAS,
                'icon'       => 'shirt',
                'sort_order' => 2,
            ],
            [
                'name'       => 'Buku',
                'slug'       => 'buku',
                'type'       => Category::TYPE_BEKAS,
                'icon'       => 'book',
                'sort_order' => 3,
            ],
            [
                'name'       => 'Elektronik',
                'slug'       => 'elektronik',
                'type'       => Category::TYPE_BEKAS,
                'icon'       => 'tv',
                'sort_order' => 4,
            ],
            [
                'name'       => 'Mainan',
                'slug'       => 'mainan',
                'type'       => Category::TYPE_BEKAS,
                'icon'       => 'puzzle-piece',
                'sort_order' => 5,
            ],
            [
                'name'       => 'Sepeda',
                'slug'       => 'sepeda',
                'type'       => Category::TYPE_BEKAS,
                'icon'       => 'bicycle',
                'sort_order' => 6,
            ],
            [
                'name'       => 'Gadget',
                'slug'       => 'gadget',
                'type'       => Category::TYPE_BEKAS,
                'icon'       => 'smartphone',
                'sort_order' => 7,
            ],

            // --- Kategori Sampah Daur Ulang (type: daur_ulang) ---
            [
                'name'       => 'Plastik',
                'slug'       => 'plastik',
                'type'       => Category::TYPE_DAUR_ULANG,
                'icon'       => 'bottle',
                'sort_order' => 1,
            ],
            [
                'name'       => 'Kertas',
                'slug'       => 'kertas',
                'type'       => Category::TYPE_DAUR_ULANG,
                'icon'       => 'newspaper',
                'sort_order' => 2,
            ],
            [
                'name'       => 'Logam',
                'slug'       => 'logam',
                'type'       => Category::TYPE_DAUR_ULANG,
                'icon'       => 'cans',
                'sort_order' => 3,
            ],
            [
                'name'       => 'Kaca',
                'slug'       => 'kaca',
                'type'       => Category::TYPE_DAUR_ULANG,
                'icon'       => 'wine-glass',
                'sort_order' => 4,
            ],
            [
                'name'       => 'Elektronik bekas',
                'slug'       => 'elektronik-bekas',
                'type'       => Category::TYPE_DAUR_ULANG,
                'icon'       => 'cpu',
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
