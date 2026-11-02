# 4.1.2 Backend kategorisasi di awal

Mengikuti revisi desain pengguna: langkah awal hanya memilih tipe `bekas` atau `daur_ulang`. Kategori detail dipilih di tujuan masing-masing; tidak ada kategori detail yang dipilih atau dibawa dari langkah awal. Daftar kategori tetap berasal dari data master story 1.3, bukan data baru.

Semua endpoint memakai middleware `auth` dan `web` (termasuk CSRF untuk POST).

| Endpoint | Nama route | Perilaku |
| --- | --- | --- |
| GET `/pilih-kategori` | `kategori.index` | Kontrak JSON dua tipe dan ketersediaan kategori di database. |
| POST `/pilih-kategori` | `kategori.pilih` | Field `tipe` wajib string, tipe yang dikenal, dan memiliki kategori di database. Redirect ke tujuan dengan query `tipe`. |
| GET `/pasang-barang` | `barang.create` | Kontrak JSON sementara untuk integrasi 4.2.5: tipe bekas dan kategori bekas dari database. Belum form atau penyimpanan listing. |
| GET `/pengepul/sementara` | `pengepul.sementara` | Kontrak JSON halaman sementara Alur B beserta kategori daur ulang. Belum pencarian pengepul 8.3. |

Pada 4.1.3, respons data dihubungkan ke halaman Livewire sesuai mockup dan tombol Jual beranda disambungkan ke `kategori.index`. Nama route tujuan dipertahankan saat form 4.2 dan halaman sementara diimplementasikan. Query URL tidak dapat mengganti tipe yang ditentukan endpoint tujuan. Respons validasi mengikuti Laravel: redirect dengan error untuk form biasa, HTTP 422 untuk permintaan JSON.

Validasi backend: `php artisan test --filter=KategorisasiTest`. Tes menggunakan SQLite in-memory; tidak mengubah database aplikasi. Testing UI dan uji silang 4.1.4 belum dikerjakan.

## Integrasi 4.1.3

Permintaan browser sekarang menampilkan halaman Blade dengan komponen Livewire `PilihAlur`. Permintaan JSON tetap mendapat kontrak data 4.1.2. Tombol Jual beranda menuju `kategori.index`. Pilihan radio Livewire memperbarui label tombol; form POST dengan CSRF tetap menggunakan validasi dan redirect backend 4.1.2.

Nama tipe berasal dari `Category::types()` dan ketersediaannya dari database 1.3. Tipe tanpa kategori dinonaktifkan; backend memeriksa ulang ketika form dikirim. Kembali dari tujuan mempertahankan tipe melalui query yang divalidasi saat komponen dimuat.

Alur A menampilkan halaman tunggu sampai form 4.2.5 tersedia, bukan form palsu yang seolah dapat memasang barang. Alur B menampilkan halaman sementara sampai 8.3. Keduanya mempertahankan tipe, bukan kategori detail, sesuai revisi pengguna. Gaya halaman mengikuti mockup 4.1.1 dengan layout Figtree/Tailwind proyek, dua kolom dan shell maksimum 420px.

Tes integrasi: `php artisan test --filter=PilihAlurTest`. Hasil 4.1.4, batas pengujian, dan panduan uji silang dicatat di `KategorisasiTesting.md`.

## PPLTIMB-58 — Ringkasan fitur 4.1

Alur yang tersedia: Beranda → Jual → pilih Barang Bekas atau Daur Ulang → validasi backend → halaman tujuan sesuai tipe. Kembali ke pilihan mempertahankan tipe. Kategori detail dari story 1.3 dipilih pada fitur tujuan, sesuai revisi desain pengguna menjadi dua pilihan tipe.

Desain 4.1.1, backend 4.1.2, dan integrasi Livewire 4.1.3 tersedia. Pengujian otomatis 4.1.4 lulus: 14 tes khusus (98 assertions), seluruh suite 108 tes (648 assertions). Bukti dan permintaan uji silang ada di `KategorisasiTesting.md` (PPLTIMB-73).

Status story belum dinyatakan selesai: uji silang reviewer masih pending, form Pasang Barang asli menunggu 4.2.5, sedangkan pencarian pengepul penuh tetap lingkup 8.3. Alur A saat ini hanya halaman tunggu dan Alur B halaman sementara. Kriteria awal tentang kategori detail terbawa ke form diganti dengan tipe terbawa sesuai arahan pengguna; perubahan ini perlu tercermin dalam review story.
