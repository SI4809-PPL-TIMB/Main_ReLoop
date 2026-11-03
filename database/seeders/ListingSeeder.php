<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Illuminate\Database\Seeder;

/** Barang dummy supaya fitur belanja (4.1, 5.1, 5.2) bisa dikembangkan tanpa menunggu form pasang barang. */
class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $penjual = User::where('email', 'penjual@reloop.test')->first();
        $kelurahan = WilayahKelurahan::where('nama', 'Beji')->first() ?? WilayahKelurahan::first();
        if (! $penjual || ! $kelurahan) {
            return;
        }

        $kategori = Category::where('type', Category::TYPE_BEKAS)->pluck('id', 'slug');
        $contoh = [
            ['perabot', 'Kursi Kayu Jati', 350000, 'baik', 'Kayu jati', '3 tahun'],
            ['perabot', 'Meja Belajar Lipat', 150000, 'seperti_baru', 'Kayu MDF', '1 tahun'],
            ['perabot', 'Rak Buku Kayu 4 Susun', 210000, 'baik', 'Kayu', '2 tahun'],
            ['pakaian', 'Kaos Band Original', 85000, 'seperti_baru', 'Katun', '6 bulan'],
            ['pakaian', 'Jaket Denim Pria L', 120000, 'baik', 'Denim', '2 tahun'],
            ['buku', 'Novel Laskar Pelangi', 40000, 'baik', 'Kertas', '4 tahun'],
            ['elektronik', 'Setrika Listrik Philips', 90000, 'ada_minus', 'Plastik', '3 tahun'],
            ['mainan', 'Lego Classic 500 Keping', 175000, 'seperti_baru', 'Plastik', '1 tahun'],
            ['sepeda', 'Sepeda Lipat 20"', 900000, 'baik', 'Aluminium', '2 tahun'],
            ['gadget', 'Headset Bluetooth', 110000, 'ada_minus', 'Plastik', '1 tahun'],
        ];

        foreach ($contoh as [$slug, $judul, $harga, $kondisi, $bahan, $lama]) {
            if (! isset($kategori[$slug])) {
                continue;
            }
            Listing::updateOrCreate(
                ['user_id' => $penjual->id, 'title' => $judul],
                [
                    'category_id'    => $kategori[$slug],
                    'kelurahan_id'   => $kelurahan->id,
                    'description'    => 'Barang contoh untuk pengembangan. '.$judul.' kondisi terawat.',
                    'price'          => $harga,
                    'condition'      => $kondisi,
                    'material'       => $bahan,
                    'usage_duration' => $lama,
                    'status'         => Listing::AKTIF,
                    'published_at'   => now(),
                ]
            );
        }
    }
}
