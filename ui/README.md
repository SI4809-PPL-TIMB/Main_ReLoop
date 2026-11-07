# 4.1.1 Desain pilihan awal

Buka beranda.html lalu klik Jual, atau buka pilih-kategori.html.

Sesuai arahan terbaru pengguna: hanya dua pilihan, Barang Bekas dan Daur Ulang. Kategori detail dipilih pada halaman masing-masing. Ini mengganti draft sebelumnya yang meminta kategori detail di awal.

Barang Bekas menuju mockup pasang-barang.html tanpa kategori detail terpilih. Daur Ulang menuju pengepul-sementara.html; pemilihan material dan pencarian penuh belum tersedia (8.3).

Gaya kartu, header, tombol, font dan warna memakai pola reloop-ecommerce-flow/pasang-barang.html, config.js dan app.css. Ilustrasi Perabot dan Plastik memakai aset asli proyek. Beranda disalin dari mockup index.html dengan perubahan tujuan tombol Jual.

Masih prototipe HTML untuk review PM, belum backend atau integrasi database. Alur sekarang membawa tipe, bukan kategori detail. Daftar kategori 1.3 tetap digunakan di tahap kategori detail.

---

# 7.1.1 Desain UI/UX checkout dan pembayaran

Buka `checkout.html` lalu lanjutkan ke `bayar.html`.

Sesuai kriteria penerimaan PPLTIMB-64 (Story 7.1):
1. **Dua Mode Harga**: Mendukung harga normal (`?harga=normal`) atau harga tawaran yang diterima (`?harga=tawar` atau bawaan `?tawar=320000`).
2. **Cara Serah Terima**:
   - Ketemuan (COD lokasi) dengan koordinasi titik temu via chat.
   - Ambil di tempat penjual (alamat dibagikan setelah pesanan dibayar).
3. **Pilihan Pembayaran Midtrans Snap**:
   - Virtual Account (BCA, BNI, BRI, Mandiri) dengan nomor VA dan instruksi transfer.
   - QRIS (scan QR via aplikasi bank/e-wallet).
   - E-Wallet (GoPay, ShopeePay).
4. **Edukasi Rekber / Escrow**:
   - Penegasan bahwa dana pembeli ditahan aman oleh Reloop sampai barang diterima.
5. **Status Pembayaran (`bayar.html`)**:
   - Menunggu bayar dengan hitung mundur (*countdown*) batas waktu 24 jam.
   - Pembatalan otomatis jika waktu kedaluwarsa (`?state=kedaluwarsa`).
   - Dialog konfirmasi lunas / berhasil (`?state=berhasil`) yang mengarahkan pembeli ke rincian pesanan.

Gaya kartu, header, tombol, font, dan warna memakai pola `config.js` dan `app.css`. Aset gambar metode serah terima dan pembayaran memakai aset asli proyek di `public/assets/illust/checkout/`.
Masih prototipe HTML di folder `ui/` untuk review PM, sebelum masuk ke spike Midtrans (7.1.2) dan integrasi backend order (7.1.3).

