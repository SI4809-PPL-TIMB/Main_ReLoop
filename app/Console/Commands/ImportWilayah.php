<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Impor seluruh wilayah Indonesia (Kepmendagri 2025, kode tanpa titik) dari CSV "kode,nama".
 * Aman dijalankan ulang: baris yang sudah ada dilewati. Baris seed lama dicocokkan lewat
 * (induk, nama) lalu kodenya diganti dengan kode resmi, jadi id dan relasi profil tetap.
 */
class ImportWilayah extends Command
{
    protected $signature = 'wilayah:impor {file? : Path CSV, default database/data/wilayah.csv}';

    protected $description = 'Impor provinsi, kab/kota, kecamatan, dan desa/kelurahan seluruh Indonesia';

    private const TABEL = ['wilayah_provinsi', 'wilayah_kabupaten', 'wilayah_kecamatan', 'wilayah_kelurahan'];

    private const FK = [1 => 'provinsi_id', 2 => 'kabupaten_id', 3 => 'kecamatan_id'];

    public function handle(): int
    {
        $path = $this->argument('file') ?? database_path('data/wilayah.csv');
        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $level = [[], [], [], []];
        $h = fopen($path, 'r');
        while (($row = fgetcsv($h)) !== false) {
            [$kode, $nama] = $row + [1 => ''];
            $n = substr_count($kode, '.');
            if ($n > 3 || $nama === '') {
                continue;
            }
            $level[$n][] = [str_replace('.', '', $kode), $nama];
        }
        fclose($h);

        $idPerKode = [];
        foreach ($level as $n => $baris) {
            $this->info(self::TABEL[$n].': '.count($baris).' baris');
            $idPerKode = $this->impor($n, $baris, $idPerKode);
        }

        return self::SUCCESS;
    }

    /** @return array<string,int> kode => id untuk level ini, dipakai level di bawahnya */
    private function impor(int $n, array $baris, array $induk): array
    {
        $tabel = self::TABEL[$n];
        $fk = self::FK[$n] ?? null;
        $ada = DB::table($tabel)->pluck('id', 'kode');
        $resmi = array_flip(array_column($baris, 0));
        $namaIdx = [];
        if ($fk) {
            // hanya baris seed lama (kode di luar CSV) yang perlu dicocokkan lewat nama
            foreach (DB::table($tabel)->select('id', 'kode', 'nama', $fk)->cursor() as $r) {
                if (! isset($resmi[$r->kode])) {
                    $namaIdx[$r->$fk.'|'.mb_strtolower($r->nama)] = $r;
                }
            }
        }

        $baru = [];
        $now = now();
        foreach ($baris as [$kode, $nama]) {
            if ($ada->has($kode)) {
                continue;
            }
            $parentId = $fk ? ($induk[$this->kodeInduk($kode, $n)] ?? null) : null;
            if ($fk && ! $parentId) {
                continue;
            }
            // seed lama berkode non-resmi: cocokkan lewat (induk, nama), ganti kodenya
            if ($fk && ($lama = $namaIdx[$parentId.'|'.mb_strtolower($nama)] ?? null)) {
                DB::table($tabel)->where('id', $lama->id)->update(['kode' => $kode]);

                continue;
            }
            $baru[] = ($fk ? [$fk => $parentId] : []) + ['nama' => $nama, 'kode' => $kode, 'created_at' => $now, 'updated_at' => $now];
            if (count($baru) === 500) {
                DB::table($tabel)->insertOrIgnore($baru);
                $baru = [];
            }
        }
        if ($baru) {
            DB::table($tabel)->insertOrIgnore($baru);
        }

        return $n < 3 ? DB::table($tabel)->pluck('id', 'kode')->all() : [];
    }

    /** Panjang kode resmi: provinsi 2, kab/kota 4, kecamatan 6, desa 10. */
    private function kodeInduk(string $kode, int $n): string
    {
        return substr($kode, 0, [1 => 2, 2 => 4, 3 => 6][$n]);
    }
}
