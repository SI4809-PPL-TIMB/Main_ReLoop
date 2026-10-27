<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
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
    }

    private function seedDemoAccounts(): void
    {
        // Ambil kelurahan representatif untuk lokasi demo
        $kelBeji       = WilayahKelurahan::where('nama', 'Beji')->first();
        $kelBogorTengah = WilayahKelurahan::where('nama', 'Paledang')->first();

        // -----------------------------------------------
        // Akun Demo Pembeli
        // -----------------------------------------------
        $buyer = User::create([
            'name'     => 'Demo Pembeli',
            'email'    => 'pembeli@reloop.test',
            'phone'    => '081200000001',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Profile::create([
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
        $seller = User::create([
            'name'     => 'Demo Penjual',
            'email'    => 'penjual@reloop.test',
            'phone'    => '081200000002',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Profile::create([
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
        // Contoh listing dari penjual demo
        // -----------------------------------------------
        $catElektronik = Category::where('slug', 'elektronik')->first();
        $catBuku       = Category::where('slug', 'buku')->first();

        if ($catElektronik) {
            Listing::create([
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
        }

        if ($catBuku) {
            Listing::create([
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
        }

        $this->command->info('✅ Akun demo berhasil dibuat:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Pembeli', 'pembeli@reloop.test', 'password'],
                ['Penjual', 'penjual@reloop.test', 'password'],
            ]
        );
    }
}
