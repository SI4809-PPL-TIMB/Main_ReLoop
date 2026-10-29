# Autentikasi ReLoop

Autentikasi memakai session Laravel untuk alur `reloop-ecommerce-flow/masuk.html` dan tombol Keluar pada profil, dengan login hanya melalui email dan kata sandi sesuai arahan. Login Google dan nomor HP tidak tersedia. Akun harus sudah ada di tabel `users` dengan password yang di-hash; registrasi, OTP, dan reset password belum termasuk implementasi ini.

| Method | URL | Middleware | Fungsi |
| --- | --- | --- | --- |
| GET | `/masuk` | guest | Form login |
| POST | `/masuk` | guest, throttle:login | Login dengan `identifier` dan `password` |
| GET | `/profil` | auth | Profil pengguna |
| POST | `/keluar` | auth | Logout |

Form POST wajib menyertakan token CSRF (`@csrf`). Field `identifier` wajib berupa email valid; nomor HP ditolak. Kesalahan kredensial memberikan pesan yang sama untuk akun tidak dikenal maupun password salah. Password tidak disimpan pada old input.

Login berhasil meregenerasi ID session dan mengarahkan pengguna ke halaman tujuan sebelumnya, atau `/profil`. Logout menghapus autentikasi, menginvalidasi session, meregenerasi token CSRF, dan mengarahkan ke `/masuk`. GET `/keluar` tidak diizinkan.

Middleware `auth` mengarahkan tamu ke `/masuk`, atau memberikan 401 untuk permintaan JSON. Middleware `guest` mengarahkan pengguna yang sudah login ke `/profil`. Tambahkan halaman privat berikutnya ke grup `auth` di `routes/web.php`.

Rate limit menghitung semua POST login yang melewati middleware guest, termasuk input tidak valid dan login berhasil: maksimal 5 permintaan per 60 detik per IP. Mengganti identifier tidak melewati batas. Saat dibatasi, server memberikan HTTP 429, header `Retry-After`, serta pesan Indonesia dalam form atau JSON sesuai header Accept. Gunakan cache persisten yang sama di semua instance deployment agar batas berlaku konsisten.

Pengujian: `php artisan test`. Feature test autentikasi menggunakan SQLite in-memory dan cache array, tanpa mengubah database aplikasi.
