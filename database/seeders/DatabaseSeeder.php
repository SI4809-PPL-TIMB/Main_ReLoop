<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use App\Models\MitraPengepul;
use App\Models\Profile;
use App\Models\User;
use App\Models\WilayahKelurahan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Jalankan seeder wilayah dan kategori terlebih dahulu
        $this->call([
            WilayahSeeder::class,
            CategorySeeder::class,
        ]);

        // 2. Akun demo
        $this->seedDemoAccounts();

        // 3. Barang dummy milik akun penjual
        $this->call([
            ListingSeeder::class,
            BarangDemoSeeder::class,
        ]);

        // 4. Pesanan demo untuk testing alur kelola pesanan (7.2)
        $this->call(OrderSeeder::class);
    }

    private function seedDemoAccounts(): void
    {
        // Ambil kelurahan representatif untuk lokasi demo
        $kelBeji       = WilayahKelurahan::where('nama', 'Beji')->first();
        $kelBogorTengah = WilayahKelurahan::where('nama', 'Paledang')->first();

        // -----------------------------------------------
        // Akun Demo Pembeli
        // -----------------------------------------------
        $buyer = User::firstOrCreate(['email' => 'pembeli@reloop.test'], [
            'name'     => 'Demo Pembeli',
            'email'    => 'pembeli@reloop.test',
            'phone'    => '081200000001',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Profile::firstOrCreate(['user_id' => $buyer->id], [
            'user_id'      => $buyer->id,
            'kelurahan_id' => $kelBeji?->id,
            'bio'          => 'Akun demo untuk role pembeli di ReLoop.',
            'avg_rating'   => 0.00,
            'total_sold'   => 0,
            'streak_count' => 0,
            'level'        => 1,
            'total_points' => 0,
        ]);

        // -----------------------------------------------
        // Akun Demo Penjual
        // -----------------------------------------------
        $seller = User::firstOrCreate(['email' => 'penjual@reloop.test'], [
            'name'     => 'Demo Penjual',
            'email'    => 'penjual@reloop.test',
            'phone'    => '081200000002',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Profile::firstOrCreate(['user_id' => $seller->id], [
            'user_id'      => $seller->id,
            'kelurahan_id' => $kelBogorTengah?->id,
            'bio'          => 'Akun demo untuk role penjual di ReLoop.',
            'avg_rating'   => 4.80,
            'total_sold'   => 5,
            'streak_count' => 3,
            'streak_last_at' => now()->toDateString(),
            'level'        => 2,
            'total_points' => 150,
        ]);

        // -----------------------------------------------
        // 8.1 · Akun Demo Admin dan Pengepul
        // -----------------------------------------------
        $admin = User::firstOrCreate(['email' => 'admin@reloop.test'], [
            'name'     => 'Demo Admin',
            'password' => Hash::make('password'),
        ]);
        // role & email_verified_at tidak fillable, jadi diisi terpisah
        $admin->forceFill(['role' => User::ADMIN, 'email_verified_at' => $admin->email_verified_at ?? now()])->save();

        $pengepul = User::firstOrCreate(['email' => 'pengepul@reloop.test'], [
            'name'     => 'Demo Pengepul',
            'phone'    => '081200000003',
            'password' => Hash::make('password'),
        ]);
        $pengepul->forceFill(['role' => User::PENGEPUL, 'email_verified_at' => $pengepul->email_verified_at ?? now()])->save();

        // Status awal menunggu supaya alur verifikasi admin bisa didemokan.
        // Data simulasi pengepul lengkap (harga, area, jam) dikerjakan di 8.2.
        MitraPengepul::firstOrCreate(['user_id' => $pengepul->id], [
            'nama_usaha'     => 'Bank Sampah Demo Beji',
            'telepon'        => '081200000003',
            'alamat'         => 'Jl. Demo No. 1 (alamat contoh)',
            'kelurahan_id'   => $kelBeji?->id,
            'layanan_jemput' => true,
            'layanan_antar'  => true,
        ]);

        // -----------------------------------------------
        // Contoh listing dari penjual demo
        // -----------------------------------------------
        $catElektronik = Category::where('slug', 'elektronik')->first();
        $catBuku       = Category::where('slug', 'buku')->first();

        if ($catElektronik) {
            $laptop = Listing::firstOrCreate(['user_id' => $seller->id, 'title' => 'Laptop Bekas ASUS VivoBook 14 — Mulus'], [
                'user_id'       => $seller->id,
                'category_id'   => $catElektronik->id,
                'kelurahan_id'  => $kelBogorTengah?->id,
                'title'         => 'Laptop Bekas ASUS VivoBook 14 — Mulus',
                'description'   => 'Laptop bekas kondisi baik, masih bergaransi resmi 6 bulan. Sudah diupgrade RAM 16GB + SSD 512GB. Cocok untuk kuliah/kerja.',
                'price'         => 3500000,
                'is_negotiable' => true,
                'condition'     => 'baik',
                'condition_note'=> null,
                'status'        => 'aktif',
                'published_at'  => now(),
            ]);
            DemoListingPhotos::attach($laptop, 'laptop-asus');
        }

        if ($catBuku) {
            $books = Listing::firstOrCreate(['user_id' => $seller->id, 'title' => 'Paket Buku Pemrograman Laravel & Vue.js'], [
                'user_id'       => $seller->id,
                'category_id'   => $catBuku->id,
                'kelurahan_id'  => $kelBogorTengah?->id,
                'title'         => 'Paket Buku Pemrograman Laravel & Vue.js',
                'description'   => 'Bundle 3 buku: Laravel Up & Running (edisi 3), Vue.js in Action, dan Full-Stack Vue with GraphQL. Kondisi baik, tidak ada coretan.',
                'price'         => 150000,
                'is_negotiable' => true,
                'condition'     => 'baik',
                'condition_note'=> 'Sampul buku pertama sedikit lecek.',
                'status'        => 'aktif',
                'published_at'  => now(),
            ]);
            DemoListingPhotos::attach($books, 'buku-pelajaran');
        }

        $this->command->info('✅ Akun demo berhasil dibuat:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Pembeli', 'pembeli@reloop.test', 'password'],
                ['Penjual', 'penjual@reloop.test', 'password'],
                ['Admin', 'admin@reloop.test', 'password'],
                ['Pengepul', 'pengepul@reloop.test', 'password'],
            ]
        );
    }
}
