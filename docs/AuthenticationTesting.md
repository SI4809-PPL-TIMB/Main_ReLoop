# PPLTIMB-40 — Testing login dan logout

Jalankan `php artisan test` atau `php vendor/phpunit/phpunit/phpunit`.
Tes memakai SQLite in-memory, session array, dan cache array sesuai `phpunit.xml`, sehingga database aplikasi tidak diubah.

## Cakupan otomatis

- Login email berhasil, nomor HP ditolak, input wajib divalidasi.
- Password salah dan akun tidak dikenal menghasilkan pesan yang sama; password tidak masuk old input.
- Form mengirim POST ke backend dengan field CSRF, menampilkan error, dan mempertahankan email.
- Tamu tidak dapat mengakses profil; pengguna yang sudah masuk diarahkan dari halaman login.
- Session ID berubah setelah login; logout menghapus data session dan memperbarui token CSRF.
- Setelah logout, profil kembali terlindungi dan akun berbeda dapat masuk tanpa membawa data session akun sebelumnya.
- GET logout ditolak; POST login/logout dengan token CSRF hilang atau salah ditolak 419. Tes CSRF mengaktifkan pemeriksaan middleware yang biasanya dilewati pada environment testing.
- Lima percobaan login dihitung per IP; percobaan keenam mendapat 429 dan Retry-After, IP lain tetap bisa masuk, serta login tersedia lagi setelah batas waktu lewat.

## Pemeriksaan manual lokal

1. Buka `/masuk`, isi email demo dan password, lalu gunakan ikon mata dua kali. Pastikan sandi tampil lalu tersembunyi tanpa mengubah nilainya.
2. Masukkan password salah; periksa pesan error dan email yang tetap terisi.
3. Login dengan kredensial benar; pastikan masuk ke profil atau tujuan sebelumnya.
4. Klik Keluar, lalu buka `/profil`; pastikan diarahkan ke `/masuk`.
5. Kirim lima percobaan login, lalu coba lagi; pastikan pesan pembatasan muncul. Tunggu 60 detik sebelum mencoba ulang.

Pemeriksaan manual ikon mata di browser belum dijalankan oleh agen karena browser otomatis tidak tersedia.
