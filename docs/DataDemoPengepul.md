# Data simulasi pengepul (PPLTIMB-149 · 8.2)

Data contoh mitra pengepul untuk demo dan pengujian alur daur ulang (8.3 cari pengepul, 9.1–9.4 setoran,
11.3 dashboard admin). Diisi oleh `MitraPengepulSeeder`, yang dipanggil `DatabaseSeeder` setelah seeder lain.
Barang dan pengguna demo tidak dibuat ulang: tetap dari `BarangDemoSeeder`, `pembeli@reloop.test`, dan
`penjual@reloop.test`.

## Akun demo

Kata sandi semua akun: `password`.

| Akun | Peran | Keterangan |
|---|---|---|
| `pembeli@reloop.test` | pengguna | Lokasi **Beji, Kec. Beji, Kota Depok** — titik uji pencarian pengepul |
| `penjual@reloop.test` | pengguna | Lokasi Paledang, Kota Bogor |
| `admin@reloop.test` | admin | Kelola dan verifikasi mitra |
| `pengepul@reloop.test` | pengepul | Bank Sampah Demo Beji (menunggu) — untuk demo alur verifikasi |
| `hijaulestari@reloop.test` | pengepul | Bank Sampah Hijau Lestari |
| `daurjaya@reloop.test` | pengepul | CV Daur Jaya |
| `berkah@reloop.test` | pengepul | Lapak Berkah Sukmajaya |
| `kertasbogor@reloop.test` | pengepul | Pengepul Kertas Bogor Tengah |
| `cibinongbersih@reloop.test` | pengepul | Bank Sampah Cibinong Bersih (menunggu) |

## Pengepul contoh

Harga dalam rupiah per kg. Kosong = jenis sampah tidak diterima.

| Mitra | Lapak | Status | Layanan | Area layanan (kecamatan) | Plastik | Kertas | Logam | Kaca | Elektronik bekas | Jam operasional |
|---|---|---|---|---|---|---|---|---|---|---|
| Bank Sampah Hijau Lestari | Kukusan, Beji | Terverifikasi | Jemput, Antar | Beji, Pancoran Mas, Sukmajaya | 3.500 | 2.000 | 8.000 | 500 | — | Sen–Sab 08.00–16.00 |
| CV Daur Jaya | Depok Jaya, Pancoran Mas | Terverifikasi | Jemput, Antar | Pancoran Mas, Beji | 3.800 | 2.200 | 9.000 | — | 15.000 | Sen–Jum 08.00–17.00, Sab 08.00–12.00 |
| Lapak Berkah Sukmajaya | Mekarjaya, Sukmajaya | Terverifikasi | Antar | Sukmajaya | 3.000 | 1.800 | — | 600 | — | Setiap hari 07.00–15.00 |
| Pengepul Kertas Bogor Tengah | Pabaton, Bogor Tengah | Terverifikasi | Jemput | Bogor Tengah, Bogor Utara, Bogor Selatan | 3.200 | 2.500 | — | — | — | Sen–Sab 09.00–17.00 |
| Bank Sampah Demo Beji | Beji, Beji | Menunggu | Jemput, Antar | Beji | 3.400 | 1.900 | 7.500 | — | — | Sen–Jum 08.00–15.00 |
| Bank Sampah Cibinong Bersih | Nanggewer, Cibinong | Menunggu | Jemput | Cibinong | 3.300 | — | 8.500 | — | — | Sen–Sab 08.00–14.00 |

## Skenario uji yang didukung

- **Pencarian dari lokasi pembeli (Beji)**: 2 mitra terverifikasi melayani Beji (Hijau Lestari yang lapaknya
  di Beji, dan CV Daur Jaya yang lapaknya di Pancoran Mas tapi bisa Jemput ke Beji). Bank Sampah Demo Beji juga
  melayani Beji tapi **tidak boleh muncul** karena masih menunggu. Lapak Berkah **tidak muncul** karena hanya
  melayani Sukmajaya.
- **Filter jenis sampah dari Beji**: plastik / logam → Hijau Lestari, Daur Jaya; elektronik bekas → hanya
  Daur Jaya; kaca → hanya Hijau Lestari.
- **Aturan area layanan**: mitra tampil untuk pengguna di kecamatan yang ada di area layanannya, bukan
  berdasarkan lokasi lapak. Mitra yang hanya Antar Sendiri sebaiknya cukup melayani kecamatan lapaknya.
- **Layanan**: Berkah hanya Antar Sendiri, Pengepul Kertas Bogor Tengah hanya Jemput (uji validasi 9.1).
- **Daerah lain**: penjual di Kota Bogor dilayani Pengepul Kertas Bogor Tengah; Cibinong hanya punya mitra
  menunggu, jadi pencarian di sana kosong (uji empty state).
- **Verifikasi admin**: dua mitra menunggu bisa langsung diverifikasi / ditolak lewat `admin@reloop.test`.

## Aturan seeder

- Idempoten: akun dicari lewat email, mitra lewat akun. Dijalankan berulang tidak membuat data dobel.
- Perubahan yang sudah dibuat saat demo (status verifikasi, harga, area, jam) **tidak ditimpa** saat seeder
  dijalankan ulang. Pengaturan hanya diisi kalau masih kosong.
- Lokasi memakai kode wilayah BPS, bukan nama (ada dua kelurahan "Tanah Baru").
- Untuk mengembalikan semua data ke kondisi awal, pakai `php artisan demo:reset` (lihat README).
