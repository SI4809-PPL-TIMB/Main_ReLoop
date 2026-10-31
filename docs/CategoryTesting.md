# Testing Seeder dan Pilihan Kategori (Story 1.3 / 1.3.4)

Dokumen pencatatan hasil pengujian otomatis dan manual untuk **Task 1.3.4: Testing seeder dan pilihan kategori**.

## Ringkasan Eksekusi Test

- **Perintah Eksekusi**: `php vendor/phpunit/phpunit/phpunit -c phpunit.xml --filter CategoryMasterTest --testdox`
- **Lingkungan Pengujian**: PHP 8.2 (Laravel 12 in-memory SQLite, session array, cache array)
- **Total Skenario**: 7 Skenario
- **Hasil**: **7 PASS, 0 FAIL (100% Berhasil)**
- **Status Bug Jira**: Tidak ada tiket Bug yang perlu dibuat karena seluruh skenario lulus (*Passed*).

---

## Tabel Hasil Pengujian per Skenario

| No | Skenario Pengujian | Deskripsi / Aksi | Ekspektasi | Hasil | Status |
|---|---|---|---|---|---|
| 1 | **Skema Kolom `type`** | Verifikasi keberadaan kolom `type` pada tabel `categories` hasil migrasi. | Kolom `type` ada dengan enum `('bekas', 'daur_ulang')` dan berstatus terindeks (*indexed*). | Kolom `type` terverifikasi ada pada skema tabel `categories`. | **PASS** |
| 2 | **Kategori Barang Bekas** | Menjalankan `CategorySeeder` dan memverifikasi kategori bertipe `bekas`. | Tepat 7 kategori barang bekas (Perabot, Pakaian, Buku, Elektronik, Mainan, Sepeda, Gadget) dengan icon dan sort order sesuai. | Ditemukan tepat 7 kategori dengan `type = 'bekas'`. | **PASS** |
| 3 | **Kategori Sampah Daur Ulang** | Menjalankan `CategorySeeder` dan memverifikasi kategori bertipe `daur_ulang`. | Tepat 5 kategori sampah daur ulang (Plastik, Kertas, Logam, Kaca, Elektronik bekas) dengan slug unik dan icon sesuai. | Ditemukan tepat 5 kategori dengan `type = 'daur_ulang'`. | **PASS** |
| 4 | **Keidempotensian Seeder** | Menjalankan `CategorySeeder` berulang kali (2x atau lebih) pada basis data yang sama. | Jumlah baris kategori tetap 12, tidak terjadi duplikasi data, dan tidak terjadi galat *unique constraint violation*. | `Category::count()` tetap tepat 12 setelah eksekusi kedua. | **PASS** |
| 5 | **Query Scopes & Helpers** | Memanggil `scopeBekas()`, `scopeDaurUlang()`, `scopeByType()`, `forSelect()`, dan `groupedByType()`. | Data terfilter dan terkelompok sesuai tipe secara akurat, terurut berdasarkan `sort_order` dan `name`. | Pemisahan data per tipe dan struktur array kelompok valid. | **PASS** |
| 6 | **Livewire `CategorySelect`** | Interaksi pemilihan kategori pada form pasang iklan (`App\Livewire\CategorySelect`). | Grid kategori merender pilihan sesuai tipe, klik kategori meng-update nilai dan men-dispatch event `category-selected`. | Komponen merender data, meng-update ID kategori terpilih, dan men-dispatch event. | **PASS** |
| 7 | **Livewire `CategoryFilter`** | Interaksi filter pencarian/katalog (`App\Livewire\CategoryFilter`). | Tab tipe beralih antara Semua/Bekas/Daur Ulang, pills kategori menyesuaikan, klik pill men-dispatch event `category-filtered`. | State filter berubah sesuai aksi dan event filter terpancar dengan payload akurat. | **PASS** |

---

## Log Output Pengujian Otomatis

```text
PHPUnit 11.5.57 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.28
Configuration: C:\laragon\www\TUBES_PPL\Main_ReLoop\phpunit.xml

Category Master (Tests\Feature\CategoryMaster)
 ✔ Categories table has type column
 ✔ Category model constants and scopes
 ✔ Category seeder populates all expected categories
 ✔ Category seeder is idempotent
 ✔ Category query helpers
 ✔ Livewire category select component
 ✔ Livewire category filter component

OK (7 tests, 56 assertions)
```

