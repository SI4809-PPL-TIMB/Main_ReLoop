<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * 8.2.3 · Kembalikan seluruh data ke kondisi demo awal (PPLTIMB-149).
 *
 * Menghapus SEMUA data (migrate:fresh), mengisi ulang lewat DatabaseSeeder, dan menghapus file unggahan
 * pengguna (foto barang, avatar). Foto barang demo ada di public/assets, jadi tidak ikut terhapus.
 *
 * Lokal:          php artisan demo:reset
 * Laravel Cloud:  php artisan demo:reset --force   (tidak ada yang bisa menjawab konfirmasi di sana)
 */
class ResetDemo extends Command
{
    protected $signature = 'demo:reset {--force : Jalankan tanpa konfirmasi (wajib di server / non-interaktif)}';

    protected $description = 'Hapus semua data lalu isi ulang dengan data demo (akun, barang, pesanan, pengepul)';

    /** Folder unggahan pengguna di disk public (lihat ListingPhotos dan ProfilController). */
    public const FOLDER_UNGGAHAN = ['listings', 'avatars'];

    public function handle(): int
    {
        $this->warn('Semua data di database '.config('database.default').' akan DIHAPUS dan diganti data demo.');

        if (! $this->option('force') && ! $this->confirm('Lanjutkan reset data demo?')) {
            $this->info('Dibatalkan. Tidak ada data yang diubah. (Di server, jalankan dengan --force.)');

            return self::FAILURE;
        }

        foreach ([
            'Mengosongkan database dan menjalankan migrasi' => 'migrate:fresh',
            'Mengisi data demo'                             => 'db:seed',
        ] as $langkah => $perintah) {
            $this->line("• {$langkah}…");
            if ($this->call($perintah, ['--force' => true]) !== self::SUCCESS) {
                $this->error("Gagal saat \"{$perintah}\". Periksa pesan di atas, lalu jalankan ulang demo:reset.");

                return self::FAILURE;
            }
        }

        $this->line('• Menghapus file unggahan pengguna…');
        foreach (self::FOLDER_UNGGAHAN as $folder) {
            Storage::disk('public')->deleteDirectory($folder);
        }

        $this->newLine();
        $this->info('Data demo siap. Kata sandi semua akun: password');
        $this->table(['Peran', 'Email'], [
            ['Pembeli', 'pembeli@reloop.test'],
            ['Penjual', 'penjual@reloop.test'],
            ['Admin', 'admin@reloop.test'],
            ['Pengepul (menunggu)', 'pengepul@reloop.test'],
            ['Pengepul lain', 'lihat docs/DataDemoPengepul.md'],
        ]);

        return self::SUCCESS;
    }
}
