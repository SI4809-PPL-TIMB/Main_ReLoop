# Pemulihan kata sandi

Klik **Lupa kata sandi?** pada `/masuk`, lalu masukkan email. Aplikasi mengirim kode 6 digit ke email terdaftar. Respons untuk email terdaftar, tidak terdaftar, dan permintaan ulang selalu sama. Nomor telepon ditolak, baik saat login maupun saat pemulihan.

Alur memakai ilustrasi asli dari `reloop-ecommerce-flow/assets/illust/auth`, disalin ke `public/assets/illust/auth` agar tetap tersedia tanpa folder mockup. Layout mengikuti `lupa-sandi.html`: email, kode verifikasi, sandi baru, lalu berhasil. Tidak ada SMS.

Kode disimpan dalam bentuk hash di cache dan berlaku 10 menit. Kirim ulang memiliki jeda 60 detik. Maksimal 5 percobaan verifikasi per email dalam 10 menit, termasuk dari IP berbeda. Setelah kode cocok, kode dihapus dan ditukar dengan token reset sekali pakai dari password broker Laravel. Password baru wajib minimal 8 karakter dengan konfirmasi sama. Setelah berhasil, pengguna kembali login dengan password baru. Endpoint kirim, verifikasi, dan simpan masing-masing dibatasi 5 per menit per IP serta memakai CSRF middleware web. Gunakan cache persisten bersama antar-instance pada deployment.

## Tes lokal

Konfigurasi proyek saat ini `MAIL_MAILER=log`. Email tidak dikirim ke inbox; isi email dan kode pemulihan ditulis ke `storage/logs/laravel.log`.

1. Buka `/lupa-sandi`, masukkan email demo, lalu klik **Kirim kode**.
2. Buka email terbaru di log, salin kode 6 digit, lalu isi pada halaman **Kode verifikasi** dan klik **Verifikasi**.
3. Isi password baru beserta konfirmasi, lalu klik **Simpan kata sandi**.
4. Masuk memakai password baru. Password demo lama tidak berlaku lagi pada akun tersebut.

Untuk pengiriman nyata, isi konfigurasi SMTP atau mailer lain di `.env`, termasuk alamat pengirim dan `APP_URL` sesuai alamat aplikasi. Jangan commit kredensial email atau log pemulihan.

Tes otomatis: `php vendor/phpunit/phpunit/phpunit --filter PasswordResetTest`. Tes menggunakan database in-memory dan notification fake sehingga tidak mengganti password akun demo lokal atau mengirim email nyata.
