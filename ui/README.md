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


---

# 8.1.1 Desain UI/UX data pengepul mitra

Buka `admin-mitra.html` (panel admin) dan `pengaturan-mitra.html` (pengaturan pengepul).

Sesuai kriteria penerimaan PPLTIMB-147 (Story 8.1):
1. **Peran akun**: `pengguna`, `pengepul`, `admin`. Admin dan pengepul masing-masing punya halaman sendiri; peran lain tidak bisa membukanya.
2. **Panel admin (`admin-mitra.html`)**:
   - `?layar=daftar`: daftar mitra, cari nama/pemilik/email, filter status (Semua, Menunggu, Terverifikasi, Ditolak). Yang menunggu verifikasi tampil paling atas.
   - `?layar=tambah`: tambah mitra sekaligus membuat akun pengepul (nama, email, kata sandi awal).
   - `?layar=detail&status=menunggu|terverifikasi|ditolak`: data usaha, tombol Verifikasi / Tolak (wajib isi alasan) / Kembalikan ke menunggu, ringkasan pengaturan dari pengepul (hanya baca), dan Hapus mitra.
3. **Pengaturan pengepul (`pengaturan-mitra.html?status=…`)**:
   - Banner status verifikasi; alasan penolakan admin ikut ditampilkan.
   - Layanan Jemput dan/atau Antar Sendiri (minimal satu).
   - Area layanan per kecamatan (data wilayah Bogor & Depok).
   - Harga per kg untuk tiap kategori daur ulang; dikosongkan = tidak menerima.
   - Jam operasional per hari; hari yang tidak dicentang = libur.
4. **Hanya mitra terverifikasi yang bisa dicari** (dipakai 8.3).

Gaya mengikuti design system Reloop: font Figtree, warna `ink` dan `loop`, viewport 420px, pola header/kartu/toggle dari `pasang-barang.html` dan `pesanan.html`.

---

# 8.3.1 Desain UI/UX pencarian pengepul (PPLTIMB-165)

Buka `cari-pengepul.html` lalu klik salah satu kartu untuk `profil-pengepul.html`.

Sesuai kriteria penerimaan PPLTIMB-150 (Story 8.3):
1. **Filter daerah**: kecamatan yang dilayani mitra (tabel `mitra_area`). Default = kecamatan di profil pengguna, bisa diubah lewat tombol Daerah.
2. **Filter jenis sampah**: chip kategori daur ulang (Plastik, Kertas, Logam, Kaca, Elektronik Bekas). "Semua" = tanpa filter.
3. **Kartu hasil**: nama usaha, daerah lapak, jarak perkiraan, rentang harga per kg (atau harga jenis yang dipilih), rating, layanan.
   - Mitra belum punya koordinat, jadi jarak perkiraan memakai kedekatan wilayah: Satu kelurahan / Satu kecamatan / Kecamatan lain.
   - Rating diambil dari profil akun mitra; mitra tanpa ulasan menampilkan "Belum ada rating".
4. **Profil mitra**: jenis sampah + harga per kg, jam operasional per hari (hari ini disorot, hari tanpa jadwal = Libur), rating, layanan, daerah layanan.
5. **Hanya mitra terverifikasi** yang muncul; profil mitra yang belum terverifikasi tidak bisa dibuka.
6. Dari Pilih Kategori › Daur Ulang langsung ke halaman ini (menggantikan `pengepul-sementara.html`).

Tombol "Setor ke pengepul ini" disambungkan ke `order-setor.html` di 9.1.

---

# 9.1.1 Desain UI/UX order setor (PPLTIMB-169)

Buka `order-setor.html` (form order setor) lalu lanjut ke `setor-status.html` (detail & status order setor).

Sesuai kriteria penerimaan PPLTIMB-151 (Story 9.1):
1. **Pemilihan Mitra & Jenis Sampah**:
   - Mitra dan lapaknya ditampilkan di bagian atas form.
   - Pilihan jenis sampah (kategori daur ulang: Plastik, Kertas, Logam, Kaca) yang diterima mitra bersangkutan lengkap dengan harga per kg.
2. **Perkiraan Berat & Kalkulasi Estimasi**:
   - Input perkiraan berat (kg) dengan quick chips (2 kg, 5 kg, 10 kg, 20 kg).
   - Kalkulasi otomatis estimasi total pendapatan (`berat × harga_per_kg`).
3. **Metode Penyerahan (Jemput vs Antar Sendiri)**:
   - Opsi **Jemput** hanya aktif jika alamat customer berada di area layanan mitra (`mitra_area`). Jika di luar area, opsi otomatis dinonaktifkan dengan pesan informatif (`?area=0`).
   - Opsi **Antar Sendiri** menampilkan alamat lapak dan jam operasional mitra.
   - Input catatan patokan lokasi penjemputan.
4. **Penguncian Harga & Tanpa Komisi**:
   - Harga per kg dikunci saat order dibuat, tidak terpengaruh jika mitra mengubah harga di kemudian hari.
   - Banner penegasan bebas komisi (pembayaran diterima penuh oleh customer sesuai hasil timbangan).
5. **Status Linimasa (`setor-status.html`)**:
   - Status awal: **Menunggu konfirmasi** dari pengepul.
   - Linimasa 4 langkah: Menunggu → Diterima mitra → Ditimbang & Dibayar → Selesai (atau Dibatalkan).
   - Aksi pembatalan order (modal konfirmasi) sebelum order diproses.
   - Tombol chat pengepul dan rincian lengkap pesanan setoran.

Gaya kartu, font Figtree, token warna `loop` & `brand`, responsif 420px selaras dengan design system `reloop-ecommerce-flow`.
