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
