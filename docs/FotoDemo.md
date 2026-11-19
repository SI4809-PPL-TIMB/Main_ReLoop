# Foto barang demo (PPLTIMB-136)

Foto JPEG disimpan di `public/assets/demo/barang/` dan ikut di dalam repositori.
Seeder membaca berkas lokal; tidak mengunduh gambar atau mengakses layanan luar.
Setiap foto memiliki sumber, penulis, lisensi, URL lisensi, dan catatan perubahan
di [`sources.json`](../public/assets/demo/barang/sources.json). Lisensi masing-masing
foto tetap berlaku untuk aset tersebut, termasuk ketentuan atribusi dan ShareAlike
untuk foto berlisensi CC BY-SA.

Foto berasal dari Wikimedia Commons dan merupakan foto representatif jenis barang.
Foto bukan dokumentasi unit milik penjual demo; merek, model, warna, ukuran, dan
edisi buku pada foto dapat berbeda dari judul contoh. Beberapa barang sejenis
berbagi foto, terutama buku dan perabot pada pesanan demo.

## Seeder yang dicakup

- `DatabaseSeeder`: laptop dan paket buku akun demo.
- `ListingSeeder`: 10 barang utama.
- `BarangDemoSeeder`: 50 barang tambahan untuk pencarian dan pagination.
- `OrderSeeder`: 2 barang untuk pesanan demo.

`DatabaseSeeder` juga menjalankan 50 barang tambahan, sehingga `php artisan
migrate:fresh --seed` menghasilkan seluruh 64 barang demo. Seeder tambahan tetap
dapat dijalankan sendiri melalui `php artisan db:seed --class=BarangDemoSeeder`.

`DemoListingPhotos::attach()` mengaitkan foto ke relasi `listing_photos` dengan
`updateOrCreate` berdasarkan listing dan path, sehingga pengaitan foto yang sama
tidak menambah baris baru saat diulang. Pengaitan ini tidak menghapus foto lain.
`ListingPhoto::url` menyediakan URL aset demo langsung dari folder `public`;
foto unggahan pengguna tetap menggunakan URL `storage/` seperti sebelumnya.

## Verifikasi

Jalankan `php artisan test --filter=DemoListingPhotosTest`.
Pengujian memakai SQLite sementara, menjalankan seeder utama dan seeder tambahan,
memastikan semua 64 barang mempunyai JPEG lokal, menjalankan ulang dua seeder
barang tanpa menambah foto, dan memeriksa URL foto pada halaman cari dan detail.
Jalankan `php artisan test --filter=DatabaseSeederIdempotencyTest` untuk memeriksa
seeder utama dua kali. Identitas wilayah memakai kode, akun memakai email, profil
memakai user ID, barang memakai penjual dan judul, pesanan memakai nomor pesanan,
dan pembayaran memakai order ID. Jumlah serta ID data harus tetap sama, dan
perubahan pada nama, kata sandi, profil akun, serta status barang dan pesanan yang
sudah ada dipertahankan. Kategori dan wilayah memakai `updateOrCreate` untuk
memperbarui data master; data demo lainnya memakai `firstOrCreate` agar interaksi
demo yang sudah berjalan tidak direset saat seeding diulang.
