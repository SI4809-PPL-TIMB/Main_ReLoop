# PPLTIMB-73 — 4.1.4 Testing kategorisasi di awal

Tanggal pengujian: 2 November 2026 (Asia/Jakarta).

## Lingkup dan hasil

Mengikuti revisi pengguna: halaman awal memilih **tipe**, bukan kategori detail. Barang Bekas menuju `/pasang-barang?tipe=bekas`; Daur Ulang menuju `/pengepul/sementara?tipe=daur_ulang`. Kategori detail berasal dari data master 1.3 dan dipilih pada fitur tujuan.

Tes berjalan dengan PHP 8.3.33, PHPUnit 11.5.57, Laravel proyek, database SQLite in-memory, cache/session array. Database aplikasi tidak dimigrasi atau diubah.

- Tes khusus kategorisasi: **14 PASS, 0 FAIL; 98 assertions**.
- Seluruh suite: **108 PASS, 0 FAIL; 648 assertions**.
- Pemeriksaan whitespace `git diff --check`: **PASS**.

Perintah standar:

```powershell
php artisan test --filter="Kategorisasi|PilihAlur"
php artisan test
```

Perintah yang benar-benar dijalankan pada komputer ini (menggunakan konfigurasi PHP lokal yang tersedia):

```powershell
& 'E:/Laragon/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe' -c E:/ppl/.runtime/php.ini vendor/phpunit/phpunit/phpunit --filter 'Kategorisasi|PilihAlur' --testdox
& 'E:/Laragon/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe' -c E:/ppl/.runtime/php.ini vendor/phpunit/phpunit/phpunit
```

## Hasil per skenario otomatis

| No | Skenario | Bukti yang diuji | Hasil |
| --- | --- | --- | --- |
| 1 | Akses tanpa login | Halaman pilihan, kedua tujuan, dan POST mengarah ke login. | PASS |
| 2 | Tipe mengikuti master 1.3 | Dua tipe tersedia setelah seeder; tanpa kategori ditandai belum tersedia. | PASS |
| 3 | Barang Bekas menuju Alur A | Redirect tepat ke route `barang.create` dengan `tipe=bekas`. | PASS |
| 4 | Daur Ulang menuju Alur B | Redirect tepat ke route `pengepul.sementara` dengan `tipe=daur_ulang`. | PASS |
| 5 | Pilihan tidak valid | Kosong, tipe asing, array, dan URL ditolak dengan HTTP 422 serta error `tipe`. | PASS |
| 6 | Belum ada kategori pada tipe | POST tetap ditolak walaupun dikirim tanpa melalui UI. | PASS |
| 7 | Kategori tiap tujuan sesuai database | Alur A memuat 7 kategori bekas, Alur B 5 daur ulang; perubahan nama di DB ikut tampil pada respons data. Query tidak dapat menukar tipe tujuan. | PASS |
| 8 | Tombol Jual dan halaman | Link beranda mengarah ke pilihan; halaman merender komponen Livewire dan form dengan CSRF. | PASS |
| 9 | Interaksi Livewire | Pilihan memperbarui label tombol dan status dapat lanjut; nilai tidak dikenal tidak dapat dilanjutkan. | PASS |
| 10 | Tujuan browser dan kembali | Mengikuti redirect menghasilkan HTML halaman sementara yang sesuai; tautan kembali membawa tipe dan memulihkan pilihan. | PASS |
| 11 | Error form dan keadaan kosong | Error validasi terlihat; tipe tanpa kategori ditandai belum tersedia. | PASS |
| 12 | CSRF aktif | Token hilang/salah ditolak 419; token valid menghasilkan redirect. Pemeriksaan CSRF testing diaktifkan khusus di tes ini. | PASS |
| 13 | Kategori dihapus setelah memilih | Backend memeriksa ulang dan menolak tipe yang sudah tidak tersedia; tipe lain tetap dapat dilanjutkan. | PASS |
| 14 | Parameter tujuan palsu dan efek samping | Parameter redirect eksternal/category_id tidak mengubah tujuan. Tidak membuat listing atau mengubah master kategori. | PASS |
| 15 | Parameter query tidak valid | Query asing/array diabaikan; tipe valid dipulihkan oleh komponen. | PASS |

Beberapa metode tes mencakup lebih dari satu skenario. Implementasi tes ada di `KategorisasiTest.php`, `PilihAlurTest.php`, dan `KategorisasiSecurityTest.php`.

## Batas hasil dan pekerjaan lanjutan

PASS Alur A berarti pengarahan ke **route dan halaman tunggu** Pasang Barang berhasil. Form pasang barang yang dapat mengunggah/menyimpan listing belum tersedia; pengujian integrasi form asli menunggu 4.2.5. PASS Alur B berarti halaman sementara berhasil, bukan pencarian pengepul 8.3.

Review visual pada tahap 4.1.3 menggunakan HTML hasil render komponen pada lebar 360px dan shell 420px di browser. Ini bukan pengujian browser interaktif end-to-end atau Laravel Dusk. Interaksi Livewire/HTTP pada laporan ini diverifikasi melalui feature test. Tidak mengklaim seluruh kriteria story awal tentang kategori detail terbawa ke form telah terpenuhi; desain direvisi pengguna menjadi dua tipe.

Tidak ditemukan FAIL pada eksekusi di atas, sehingga belum dibuat tiket Bug. Bila reviewer menemukan kegagalan, catat langkah, aktual, ekspektasi, dan bukti; buat Bug di Jira yang ditautkan ke story PPLTIMB-58, kemudian jalankan ulang tes setelah perbaikan.

## Permintaan uji silang reviewer — PENDING

Review independen belum dilakukan dan tidak dinyatakan PASS. Minta reviewer lain menjalankan minimal dua skenario berikut pada aplikasi setelah login dan seeder kategori tersedia:

1. Dari Beranda klik **Jual**, pilih **Barang Bekas**, klik **Lanjut ke Pasang Barang**. Pastikan sampai ke halaman tunggu Pasang Barang; klik kembali dan pastikan Barang Bekas tetap terpilih.
2. Ganti menjadi **Daur Ulang**, klik **Lanjut ke Mitra Pengepul**. Pastikan sampai ke halaman sementara pengepul; klik kembali dan pastikan Daur Ulang tetap terpilih.

Catat nama reviewer, tanggal, browser, hasil PASS/FAIL, dan bukti di komentar PPLTIMB-73. Pada HP sekitar 360px dan laptop, periksa juga kedua kartu terbaca, tidak ada scroll horizontal, fokus keyboard terlihat, dan tombol Lanjut dapat dijangkau.

Status: pengujian otomatis selesai; **uji silang independen dan pengujian form asli 4.2.5 masih menunggu**. Belum menandai tiket Jira selesai atau mengirim komentar Jira secara otomatis.
