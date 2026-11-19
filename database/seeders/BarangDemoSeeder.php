<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Illuminate\Database\Seeder;

/**
 * 50 barang dummy milik Demo Penjual, tersebar di 7 kategori dan beberapa kelurahan,
 * untuk mengetes cari, filter, pagination, dan barang serupa.
 * Aman dijalankan ulang (firstOrCreate per penjual dan judul).
 *
 * php artisan db:seed --class=BarangDemoSeeder
 */
class BarangDemoSeeder extends Seeder
{
    public function run(): void
    {
        $penjual = User::where('email', 'penjual@reloop.test')->first();
        $kelurahan = WilayahKelurahan::whereIn('kode', [
            '3271010001', '3276010001', '3276010003',
            '3276010004', '3276010005', '3276010006',
        ])->orderBy('kode')->pluck('id')->all();
        $kategori = Category::where('type', Category::TYPE_BEKAS)->pluck('id', 'slug');
        if (! $penjual || ! $kelurahan || $kategori->isEmpty()) {
            $this->command?->warn('Lewati: akun penjual demo, kelurahan, atau kategori belum ada.');

            return;
        }

        // [kategori, judul, harga, kondisi, bahan, lama pakai, foto lokal]
        $barang = [
            ['perabot', 'Lemari Pakaian 2 Pintu', 650000, 'baik', 'Kayu MDF', '3 tahun', 'lemari'],
            ['perabot', 'Sofa Minimalis 2 Dudukan', 1200000, 'baik', 'Kain', '4 tahun', 'sofa'],
            ['perabot', 'Meja Makan Kayu 4 Kursi', 1500000, 'ada_minus', 'Kayu jati', '5 tahun', 'meja-makan'],
            ['perabot', 'Kasur Busa 160x200', 450000, 'baik', 'Busa', '2 tahun', 'kasur'],
            ['perabot', 'Kursi Gaming Merah', 800000, 'seperti_baru', 'Kulit sintetis', '1 tahun', 'kursi-gaming'],
            ['perabot', 'Rak Sepatu Besi 5 Susun', 95000, 'baik', 'Besi', '2 tahun', 'rak-sepatu'],
            ['perabot', 'Meja Rias Cermin Bulat', 375000, 'seperti_baru', 'Kayu', '1 tahun', 'meja-rias'],
            ['perabot', 'Lampu Belajar LED', 65000, 'baik', 'Plastik', '1 tahun', 'lampu-belajar'],
            ['pakaian', 'Kemeja Flanel Kotak M', 70000, 'seperti_baru', 'Katun', '6 bulan', 'kemeja-flanel'],
            ['pakaian', 'Hoodie Polos Hitam XL', 110000, 'baik', 'Fleece', '1 tahun', 'hoodie'],
            ['pakaian', 'Sepatu Lari Ukuran 42', 300000, 'baik', 'Mesh', '1 tahun', 'sepatu-lari'],
            ['pakaian', 'Tas Ransel Laptop 15"', 180000, 'seperti_baru', 'Polyester', '8 bulan', 'ransel'],
            ['pakaian', 'Gaun Pesta Satin S', 250000, 'seperti_baru', 'Satin', 'Sekali pakai', 'gaun'],
            ['pakaian', 'Celana Chino Krem 32', 95000, 'baik', 'Katun', '1 tahun', 'celana-chino'],
            ['pakaian', 'Jaket Parasut Anak', 60000, 'ada_minus', 'Parasut', '2 tahun', 'jaket-parasut'],
            ['buku', 'Buku Atomic Habits', 75000, 'seperti_baru', 'Kertas', '1 tahun', 'atomic-habits'],
            ['buku', 'Paket Buku SBMPTN Lengkap', 120000, 'baik', 'Kertas', '2 tahun', 'buku-pelajaran'],
            ['buku', 'Komik One Piece Vol. 1-10', 200000, 'baik', 'Kertas', '5 tahun', 'komik'],
            ['buku', 'Kamus Inggris-Indonesia', 50000, 'ada_minus', 'Kertas', '6 tahun', 'kamus'],
            ['buku', 'Novel Bumi Manusia', 55000, 'baik', 'Kertas', '3 tahun', 'buku-pelajaran'],
            ['buku', 'Buku Resep Masakan Nusantara', 45000, 'seperti_baru', 'Kertas', '1 tahun', 'buku-resep'],
            ['buku', 'Ensiklopedia Anak Bergambar', 150000, 'baik', 'Kertas', '4 tahun', 'ensiklopedia'],
            ['elektronik', 'Rice Cooker Miyako 1.8L', 150000, 'baik', 'Plastik', '2 tahun', 'rice-cooker'],
            ['elektronik', 'Kipas Angin Berdiri', 120000, 'baik', 'Plastik', '3 tahun', 'kipas-angin'],
            ['elektronik', 'Blender Philips', 200000, 'seperti_baru', 'Kaca', '1 tahun', 'blender'],
            ['elektronik', 'TV LED 32 Inci', 1100000, 'baik', 'Plastik', '4 tahun', 'televisi'],
            ['elektronik', 'Microwave Sharp 20L', 550000, 'ada_minus', 'Logam', '5 tahun', 'microwave'],
            ['elektronik', 'Dispenser Air Galon', 175000, 'baik', 'Plastik', '2 tahun', 'dispenser'],
            ['elektronik', 'Vacuum Cleaner Mini', 230000, 'seperti_baru', 'Plastik', '1 tahun', 'vacuum'],
            ['mainan', 'Boneka Beruang Besar', 90000, 'baik', 'Kain', '2 tahun', 'boneka'],
            ['mainan', 'Mobil Remote Control', 150000, 'ada_minus', 'Plastik', '1 tahun', 'mobil-rc'],
            ['mainan', 'Puzzle 1000 Keping', 70000, 'seperti_baru', 'Karton', '6 bulan', 'puzzle'],
            ['mainan', 'Papan Catur Kayu', 85000, 'baik', 'Kayu', '3 tahun', 'catur'],
            ['mainan', 'Set Mainan Masak-masakan', 60000, 'baik', 'Plastik', '2 tahun', 'mainan-masak'],
            ['mainan', 'Action Figure Gundam', 250000, 'seperti_baru', 'Plastik', '1 tahun', 'gundam'],
            ['mainan', 'Kereta Bayi Lipat', 500000, 'baik', 'Aluminium', '2 tahun', 'kereta-bayi'],
            ['sepeda', 'Sepeda Gunung 26"', 1300000, 'baik', 'Baja', '3 tahun', 'sepeda-gunung'],
            ['sepeda', 'Sepeda Anak Roda Bantu', 350000, 'baik', 'Baja', '2 tahun', 'sepeda-anak'],
            ['sepeda', 'Sepeda Fixie Hitam', 1000000, 'seperti_baru', 'Aluminium', '1 tahun', 'sepeda-fixie'],
            ['sepeda', 'Helm Sepeda Dewasa', 120000, 'baik', 'Plastik', '1 tahun', 'helm-sepeda'],
            ['sepeda', 'Pompa Ban Portabel', 50000, 'seperti_baru', 'Logam', '6 bulan', 'pompa-ban'],
            ['sepeda', 'Sepeda BMX', 750000, 'ada_minus', 'Baja', '4 tahun', 'sepeda-bmx'],
            ['sepeda', 'Lampu Sepeda Depan Belakang', 65000, 'baik', 'Plastik', '1 tahun', 'lampu-sepeda'],
            ['elektronik', 'HP Samsung A52 128GB', 1800000, 'baik', 'Plastik', '2 tahun', 'samsung-a52'],
            ['elektronik', 'iPhone 11 64GB', 3500000, 'baik', 'Aluminium', '3 tahun', 'iphone-11'],
            ['elektronik', 'Tablet Lenovo Tab M10', 1500000, 'seperti_baru', 'Plastik', '1 tahun', 'tablet-lenovo'],
            ['elektronik', 'Smartwatch Xiaomi', 400000, 'baik', 'Plastik', '1 tahun', 'smartwatch'],
            ['elektronik', 'Powerbank 20000mAh', 150000, 'seperti_baru', 'Plastik', '6 bulan', 'powerbank'],
            ['elektronik', 'Speaker Bluetooth JBL Go', 250000, 'ada_minus', 'Plastik', '2 tahun', 'speaker-jbl'],
            ['elektronik', 'Mouse Wireless Logitech', 90000, 'baik', 'Plastik', '1 tahun', 'mouse-logitech'],
        ];

        foreach ($barang as $i => [$slug, $judul, $harga, $kondisi, $bahan, $lama, $foto]) {
            if (! isset($kategori[$slug])) {
                continue;
            }
            $listing = Listing::firstOrCreate(
                ['user_id' => $penjual->id, 'title' => $judul],
                [
                    'category_id'    => $kategori[$slug],
                    'kelurahan_id'   => $kelurahan[$i % count($kelurahan)],
                    'description'    => $judul.', kondisi terawat dan siap pakai. Bisa COD di sekitar lokasi.',
                    'price'          => $harga,
                    'is_negotiable'  => $i % 3 !== 0,
                    'condition'      => $kondisi,
                    'condition_note' => $kondisi === 'ada_minus' ? 'Ada lecet atau bekas pemakaian, lihat deskripsi.' : null,
                    'material'       => $bahan,
                    'usage_duration' => $lama,
                    'quantity'       => 1,
                    'status'         => Listing::AKTIF,
                    'published_at'   => now()->subHours($i * 3), // urutan "terbaru" bervariasi
                ]
            );
            DemoListingPhotos::attach($listing, $foto);
        }
    }
}
