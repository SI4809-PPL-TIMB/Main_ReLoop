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
 * Aman dijalankan ulang (updateOrCreate per judul).
 *
 * php artisan db:seed --class=BarangDemoSeeder
 */
class BarangDemoSeeder extends Seeder
{
    public function run(): void
    {
        $penjual = User::where('email', 'penjual@reloop.test')->first();
        $kelurahan = WilayahKelurahan::whereIn('id', [12, 27, 29, 30, 31, 32])->pluck('id')->all();
        $kategori = Category::where('type', Category::TYPE_BEKAS)->pluck('id', 'slug');
        if (! $penjual || ! $kelurahan || $kategori->isEmpty()) {
            $this->command?->warn('Lewati: akun penjual demo, kelurahan, atau kategori belum ada.');

            return;
        }

        // [kategori, judul, harga, kondisi, bahan, lama pakai]
        $barang = [
            ['perabot', 'Lemari Pakaian 2 Pintu', 650000, 'baik', 'Kayu MDF', '3 tahun'],
            ['perabot', 'Sofa Minimalis 2 Dudukan', 1200000, 'baik', 'Kain', '4 tahun'],
            ['perabot', 'Meja Makan Kayu 4 Kursi', 1500000, 'ada_minus', 'Kayu jati', '5 tahun'],
            ['perabot', 'Kasur Busa 160x200', 450000, 'baik', 'Busa', '2 tahun'],
            ['perabot', 'Kursi Gaming Merah', 800000, 'seperti_baru', 'Kulit sintetis', '1 tahun'],
            ['perabot', 'Rak Sepatu Besi 5 Susun', 95000, 'baik', 'Besi', '2 tahun'],
            ['perabot', 'Meja Rias Cermin Bulat', 375000, 'seperti_baru', 'Kayu', '1 tahun'],
            ['perabot', 'Lampu Belajar LED', 65000, 'baik', 'Plastik', '1 tahun'],
            ['pakaian', 'Kemeja Flanel Kotak M', 70000, 'seperti_baru', 'Katun', '6 bulan'],
            ['pakaian', 'Hoodie Polos Hitam XL', 110000, 'baik', 'Fleece', '1 tahun'],
            ['pakaian', 'Sepatu Lari Ukuran 42', 300000, 'baik', 'Mesh', '1 tahun'],
            ['pakaian', 'Tas Ransel Laptop 15"', 180000, 'seperti_baru', 'Polyester', '8 bulan'],
            ['pakaian', 'Gaun Pesta Satin S', 250000, 'seperti_baru', 'Satin', 'Sekali pakai'],
            ['pakaian', 'Celana Chino Krem 32', 95000, 'baik', 'Katun', '1 tahun'],
            ['pakaian', 'Jaket Parasut Anak', 60000, 'ada_minus', 'Parasut', '2 tahun'],
            ['buku', 'Buku Atomic Habits', 75000, 'seperti_baru', 'Kertas', '1 tahun'],
            ['buku', 'Paket Buku SBMPTN Lengkap', 120000, 'baik', 'Kertas', '2 tahun'],
            ['buku', 'Komik One Piece Vol. 1-10', 200000, 'baik', 'Kertas', '5 tahun'],
            ['buku', 'Kamus Inggris-Indonesia', 50000, 'ada_minus', 'Kertas', '6 tahun'],
            ['buku', 'Novel Bumi Manusia', 55000, 'baik', 'Kertas', '3 tahun'],
            ['buku', 'Buku Resep Masakan Nusantara', 45000, 'seperti_baru', 'Kertas', '1 tahun'],
            ['buku', 'Ensiklopedia Anak Bergambar', 150000, 'baik', 'Kertas', '4 tahun'],
            ['elektronik', 'Rice Cooker Miyako 1.8L', 150000, 'baik', 'Plastik', '2 tahun'],
            ['elektronik', 'Kipas Angin Berdiri', 120000, 'baik', 'Plastik', '3 tahun'],
            ['elektronik', 'Blender Philips', 200000, 'seperti_baru', 'Kaca', '1 tahun'],
            ['elektronik', 'TV LED 32 Inci', 1100000, 'baik', 'Plastik', '4 tahun'],
            ['elektronik', 'Microwave Sharp 20L', 550000, 'ada_minus', 'Logam', '5 tahun'],
            ['elektronik', 'Dispenser Air Galon', 175000, 'baik', 'Plastik', '2 tahun'],
            ['elektronik', 'Vacuum Cleaner Mini', 230000, 'seperti_baru', 'Plastik', '1 tahun'],
            ['mainan', 'Boneka Beruang Besar', 90000, 'baik', 'Kain', '2 tahun'],
            ['mainan', 'Mobil Remote Control', 150000, 'ada_minus', 'Plastik', '1 tahun'],
            ['mainan', 'Puzzle 1000 Keping', 70000, 'seperti_baru', 'Karton', '6 bulan'],
            ['mainan', 'Papan Catur Kayu', 85000, 'baik', 'Kayu', '3 tahun'],
            ['mainan', 'Set Mainan Masak-masakan', 60000, 'baik', 'Plastik', '2 tahun'],
            ['mainan', 'Action Figure Gundam', 250000, 'seperti_baru', 'Plastik', '1 tahun'],
            ['mainan', 'Kereta Bayi Lipat', 500000, 'baik', 'Aluminium', '2 tahun'],
            ['sepeda', 'Sepeda Gunung 26"', 1300000, 'baik', 'Baja', '3 tahun'],
            ['sepeda', 'Sepeda Anak Roda Bantu', 350000, 'baik', 'Baja', '2 tahun'],
            ['sepeda', 'Sepeda Fixie Hitam', 1000000, 'seperti_baru', 'Aluminium', '1 tahun'],
            ['sepeda', 'Helm Sepeda Dewasa', 120000, 'baik', 'Plastik', '1 tahun'],
            ['sepeda', 'Pompa Ban Portabel', 50000, 'seperti_baru', 'Logam', '6 bulan'],
            ['sepeda', 'Sepeda BMX', 750000, 'ada_minus', 'Baja', '4 tahun'],
            ['sepeda', 'Lampu Sepeda Depan Belakang', 65000, 'baik', 'Plastik', '1 tahun'],
            ['gadget', 'HP Samsung A52 128GB', 1800000, 'baik', 'Plastik', '2 tahun'],
            ['gadget', 'iPhone 11 64GB', 3500000, 'baik', 'Aluminium', '3 tahun'],
            ['gadget', 'Tablet Lenovo Tab M10', 1500000, 'seperti_baru', 'Plastik', '1 tahun'],
            ['gadget', 'Smartwatch Xiaomi', 400000, 'baik', 'Plastik', '1 tahun'],
            ['gadget', 'Powerbank 20000mAh', 150000, 'seperti_baru', 'Plastik', '6 bulan'],
            ['gadget', 'Speaker Bluetooth JBL Go', 250000, 'ada_minus', 'Plastik', '2 tahun'],
            ['gadget', 'Mouse Wireless Logitech', 90000, 'baik', 'Plastik', '1 tahun'],
        ];

        foreach ($barang as $i => [$slug, $judul, $harga, $kondisi, $bahan, $lama]) {
            if (! isset($kategori[$slug])) {
                continue;
            }
            Listing::updateOrCreate(
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
        }
    }
}
