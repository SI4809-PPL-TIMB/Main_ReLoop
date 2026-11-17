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

---

# 7.2.1 Desain UI/UX kelola pesanan

Buka `pesanan.html` (dapat dicoba via URL param `?peran=pembeli|penjual` dan `?step=1|2|3` atau `?state=dibatalkan`).

Sesuai kriteria penerimaan PPLTIMB-65 (Story 7.2):
1. **Dua Sisi Peran (Pembeli & Penjual)**:
   - Pembeli melihat status dana tertahan, estimasi kedatangan/serah terima, aksi konfirmasi penerimaan barang, dan tombol beri ulasan saat selesai.
   - Penjual melihat status dana yang ditahan di sistem, aksi menandai barang telah diserahkan (COD/kirim), info rincian komisi platform 3%, dan notifikasi pencairan dana bersih.
2. **Dana Tertahan (Escrow)**:
   - Banner penegasan bahwa dana pembeli aman ditahan oleh Reloop hingga pembeli mengonfirmasi penerimaan barang.
3. **Timeline Status Pesanan (Multi-step Flow)**:
   - Langkah 0: Dibayar (metode bayar & waktu lunas).
   - Langkah 1: Diserahkan penjual (koordinasi titik temu atau pengiriman).
   - Langkah 2: Diterima pembeli (konfirmasi penerimaan dengan batas waktu otomatis 3 hari / 72 jam).
   - Langkah 3: Selesai (dana diteruskan ke penjual setelah komisi 3%, streak bertambah).
4. **Pembatalan Pesanan (`?state=dibatalkan`)**:
   - Pembatalan dapat dilakukan sebelum barang diserahkan oleh penjual.
   - Dana dikembalikan penuh ke pembeli dalam 1-3 hari kerja dan barang kembali aktif.
5. **Modal Interaktif**:
   - Modal konfirmasi serah terima barang (penjual).
   - Modal konfirmasi penerimaan barang (pembeli).
   - Modal pembatalan pesanan dengan alasan pembatalan.
   - Modal rating dan ulasan penjual setelah transaksi selesai.

Gaya tampilan selaras dengan design system Reloop: font Figtree, warna `loop` (`#9be600`), mobile viewport 420px, dan arsitektur token Tailwind CSS.

