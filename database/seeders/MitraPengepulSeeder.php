<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MitraPengepul;
use App\Models\User;
use App\Models\WilayahKecamatan;
use App\Models\WilayahKelurahan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 8.2.2 · Data simulasi pengepul (PPLTIMB-149). Rancangan data: docs/DataDemoPengepul.md
 *
 * Idempoten: akun dicari lewat email, mitra lewat user_id. Status, harga, area, dan jam hanya diisi
 * saat mitra baru dibuat / masih kosong, jadi perubahan selama demo tidak ditimpa.
 * Wilayah dicari lewat kode BPS (nama kelurahan bisa kembar, mis. "Tanah Baru").
 */
class MitraPengepulSeeder extends Seeder
{
    private const SEN_SAB = [1, 2, 3, 4, 5, 6];
    private const SEN_JUM = [1, 2, 3, 4, 5];
    private const SETIAP_HARI = [1, 2, 3, 4, 5, 6, 7];

    public const DATA = [
        [
            'email' => 'hijaulestari@reloop.test', 'pemilik' => 'Siti Aminah',
            'nama_usaha' => 'Bank Sampah Hijau Lestari', 'telepon' => '081311110001',
            'alamat' => 'Jl. Kukusan Raya No. 21', 'kelurahan' => '3276010004',
            'status' => MitraPengepul::TERVERIFIKASI, 'jemput' => true, 'antar' => true,
            'area' => ['3276010', '3276030', '3276020'],
            'harga' => ['plastik' => 3500, 'kertas' => 2000, 'logam' => 8000, 'kaca' => 500],
            'jam' => [[self::SEN_SAB, '08:00', '16:00']],
        ],
        [
            'email' => 'daurjaya@reloop.test', 'pemilik' => 'Andi Wijaya',
            'nama_usaha' => 'CV Daur Jaya', 'telepon' => '081311110002',
            'alamat' => 'Jl. Nusantara Raya No. 88', 'kelurahan' => '3276030002',
            'status' => MitraPengepul::TERVERIFIKASI, 'jemput' => true, 'antar' => true,
            'area' => ['3276030', '3276010'],
            'harga' => ['plastik' => 3800, 'kertas' => 2200, 'logam' => 9000, 'elektronik-bekas' => 15000],
            'jam' => [[self::SEN_JUM, '08:00', '17:00'], [[6], '08:00', '12:00']],
        ],
        [
            'email' => 'berkah@reloop.test', 'pemilik' => 'Rudi Hartono',
            'nama_usaha' => 'Lapak Berkah Sukmajaya', 'telepon' => '081311110003',
            'alamat' => 'Jl. Mekarjaya Gg. Berkah No. 5', 'kelurahan' => '3276020002',
            // Hanya Antar Sendiri: pengguna datang ke lapak, jadi area layanan = kecamatan lapaknya saja.
            'status' => MitraPengepul::TERVERIFIKASI, 'jemput' => false, 'antar' => true,
            'area' => ['3276020'],
            'harga' => ['plastik' => 3000, 'kertas' => 1800, 'kaca' => 600],
            'jam' => [[self::SETIAP_HARI, '07:00', '15:00']],
        ],
        [
            'email' => 'kertasbogor@reloop.test', 'pemilik' => 'Dewi Kartika',
            'nama_usaha' => 'Pengepul Kertas Bogor Tengah', 'telepon' => '081311110004',
            'alamat' => 'Jl. Pabaton No. 14', 'kelurahan' => '3271010002',
            'status' => MitraPengepul::TERVERIFIKASI, 'jemput' => true, 'antar' => false,
            'area' => ['3271010', '3271030', '3271020'],
            'harga' => ['plastik' => 3200, 'kertas' => 2500],
            'jam' => [[self::SEN_SAB, '09:00', '17:00']],
        ],
        [
            // Akun demo pengepul dari 8.1; sengaja menunggu supaya alur verifikasi admin bisa didemokan.
            'email' => 'pengepul@reloop.test', 'pemilik' => 'Demo Pengepul', 'phone' => '081200000003',
            'nama_usaha' => 'Bank Sampah Demo Beji', 'telepon' => '081200000003',
            'alamat' => 'Jl. Demo No. 1 (alamat contoh)', 'kelurahan' => '3276010001',
            'status' => MitraPengepul::MENUNGGU, 'jemput' => true, 'antar' => true,
            'area' => ['3276010'],
            'harga' => ['plastik' => 3400, 'kertas' => 1900, 'logam' => 7500],
            'jam' => [[self::SEN_JUM, '08:00', '15:00']],
        ],
        [
            'email' => 'cibinongbersih@reloop.test', 'pemilik' => 'Bambang Saputra',
            'nama_usaha' => 'Bank Sampah Cibinong Bersih', 'telepon' => '081311110006',
            'alamat' => 'Jl. Raya Nanggewer No. 3', 'kelurahan' => '3201010002',
            'status' => MitraPengepul::MENUNGGU, 'jemput' => true, 'antar' => false,
            'area' => ['3201010'],
            'harga' => ['plastik' => 3300, 'logam' => 8500],
            'jam' => [[self::SEN_SAB, '08:00', '14:00']],
        ],
    ];

    public function run(): void
    {
        $kategori = Category::daurUlang()->pluck('id', 'slug');
        $admin = User::where('email', 'admin@reloop.test')->first();

        foreach (self::DATA as $d) {
            $this->seedMitra($d, $kategori, $admin);
        }
    }

    private function seedMitra(array $d, $kategori, ?User $admin): void
    {
        $user = User::firstOrCreate(['email' => $d['email']], [
            'name'     => $d['pemilik'],
            'phone'    => $d['phone'] ?? null,
            'password' => Hash::make('password'),
        ]);
        // role & email_verified_at tidak fillable
        $user->forceFill(['role' => User::PENGEPUL, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        $mitra = MitraPengepul::firstOrCreate(['user_id' => $user->id], [
            'nama_usaha'     => $d['nama_usaha'],
            'telepon'        => $d['telepon'],
            'alamat'         => $d['alamat'],
            'kelurahan_id'   => WilayahKelurahan::where('kode', $d['kelurahan'])->value('id'),
            'layanan_jemput' => $d['jemput'],
            'layanan_antar'  => $d['antar'],
        ]);

        if ($mitra->wasRecentlyCreated && $d['status'] !== MitraPengepul::MENUNGGU && $admin) {
            $mitra->verifikasi($d['status'], $admin);
        }

        if ($mitra->area()->doesntExist()) {
            $mitra->area()->sync(WilayahKecamatan::whereIn('kode', $d['area'])->pluck('id'));
        }

        if ($mitra->harga()->doesntExist()) {
            foreach ($d['harga'] as $slug => $harga) {
                if (isset($kategori[$slug])) {
                    $mitra->harga()->create(['category_id' => $kategori[$slug], 'harga_per_kg' => $harga]);
                }
            }
        }

        if ($mitra->jam()->doesntExist()) {
            foreach ($d['jam'] as [$hari, $buka, $tutup]) {
                foreach ($hari as $h) {
                    $mitra->jam()->create(['hari' => $h, 'buka' => $buka, 'tutup' => $tutup]);
                }
            }
        }
    }
}
