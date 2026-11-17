# Spike Midtrans Sandbox dan ngrok (Story 7.1 / PPLTIMB-129)

Dokumen hasil riset teknis dan persiapan arsitektur untuk **Task 7.1.2: Spike Midtrans sandbox dan ngrok** pada Story **7.1 · Checkout dan Pembayaran Midtrans** (PPLTIMB-64).

---

## 1. Ringkasan & Tujuan Spike

Mengidentifikasi dan memitigasi risiko terbesar pada integrasi pembayaran Midtrans Snap sandbox, mencakup:
1. Konfigurasi kredensial Midtrans di Laravel (`config/services.php` dan `.env`).
2. Kontrak API pembuatan Snap Token (`/snap/v1/transactions`).
3. Konfigurasi tunnel publik menggunakan `ngrok` untuk menerima notifikasi HTTP Webhook di lingkungan pengembangan lokal.
4. Spesifikasi algoritma verifikasi tanda tangan (*signature key*) berbasis SHA-512.
5. Strategi idempotensi agar status pesanan tidak diproses berulang.
6. Prosedur pengujian pembayaran menggunakan Midtrans Payment Simulator.

---

## 2. Kredensial & Lingkungan Sandbox

Integrasi menggunakan mode **Sandbox** (lingkungan uji coba tanpa uang sungguhan):
- **Dashboard**: `https://dashboard.sandbox.midtrans.com`
- **Snap API Base URL**: `https://app.sandbox.midtrans.com/snap/v1`
- **Snap JS URL**: `https://app.sandbox.midtrans.com/snap/snap.js`

### Konfigurasi Laravel

Variabel lingkungan ditambahkan ke `.env.example` dan `.env`:
```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true
```

Konfigurasi dibaca terpusat di `config/services.php`:
```php
'midtrans' => [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => (bool) env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds' => (bool) env('MIDTRANS_IS_3DS', true),
],
```

- **Server Key**: Wajib dijaga kerahasiaannya di sisi server backend. Digunakan untuk request Snap Token dan verifikasi webhook signature.
- **Client Key**: Dipublikasikan ke frontend Blade untuk inisialisasi library Snap.js.

---

## 3. Alur Pembuatan Snap Token (Subtask 7.1.4)

Backend melakukan request HTTP POST ke endpoint Midtrans Snap:
- **Endpoint**: `POST https://app.sandbox.midtrans.com/snap/v1/transactions`
- **Header**:
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `Authorization: Basic base64(MIDTRANS_SERVER_KEY . ':')`
- **Payload Request**:
  ```json
  {
    "transaction_details": {
      "order_id": "RL-20261020-0142",
      "gross_amount": 320000
    },
    "item_details": [
      {
        "id": "12",
        "price": 320000,
        "quantity": 1,
        "name": "Kursi Kayu Jati Ukir"
      }
    ],
    "customer_details": {
      "first_name": "Rizwan",
      "email": "rizwan@example.com",
      "phone": "08123456789"
    }
  }
  ```
- **Respons Sukses (HTTP 201)**:
  ```json
  {
    "token": "d741cb30-8025-4c07-88d4-530268ecxxxx",
    "redirect_url": "https://app.sandbox.midtrans.com/snap/v2/vtweb/..."
  }
  ```
- Token ini disimpan di sesi/tabel dan dikirim ke browser untuk memicu `window.snap.pay(snapToken)`.

---

## 4. Alur Webhook & Setup ngrok

### Kebutuhan ngrok
Midtrans mengirim notifikasi pembayaran melalui HTTP POST dari cloud ke server aplikasi. Agar dapat diuji di mesin lokal (`localhost:8000`), digunakan `ngrok` sebagai reverse tunnel.

### Langkah Setup:
1. Jalankan server Laravel:
   ```bash
   php artisan serve --port=8000
   ```
2. Di terminal terpisah, buka tunnel ngrok:
   ```bash
   ngrok http 8000
   ```
3. Catat URL forwarding yang dihasilkan (misal `https://abcd-123.ngrok-free.app`).
4. Buka Midtrans Sandbox Dashboard $\rightarrow$ **Settings** $\rightarrow$ **Configuration**.
5. Isi **Payment Notification URL**:
   `https://abcd-123.ngrok-free.app/api/midtrans/webhook`
6. Klik **Save Changes**.

---

## 5. Algoritma Verifikasi Signature (Subtask 7.1.5)

Untuk memastikan bahwa notifikasi webhook benar-benar berasal dari Midtrans dan isi nominal belum dimanipulasi:
- Midtrans mengirim field `signature_key` di body payload.
- Backend menghitung hash SHA-512 dengan rumus:
  $$\text{computed\_signature} = \text{hash}('sha512', \text{order\_id} . \text{status\_code} . \text{gross\_amount} . \text{server\_key})$$
- Implementasi PHP:
  ```php
  $computedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . config('services.midtrans.server_key'));
  if ($computedSignature !== $incomingSignature) {
      return response()->json(['message' => 'Invalid signature'], 403);
  }
  ```

---

## 6. Pemetaan Status & Idempotensi (Subtask 7.1.6)

### Transisi Status Pesanan & Pembayaran:
| `transaction_status` | `fraud_status` | Status `orders` | Status `payments` | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| `settlement` | `accept` / null | `dibayar` | `berhasil` | Pembayaran lunas, dana ditahan di escrow. |
| `capture` | `accept` | `dibayar` | `berhasil` | Khusus kartu kredit sukses. |
| `pending` | - | `menunggu_bayar` | `menunggu` | Menunggu pembeli menyelesaikan pembayaran. |
| `expire` | - | `dibatalkan` | `kedaluwarsa` | Batas waktu 24 jam lewat, barang dilepas/aktif kembali. |
| `cancel` / `deny` | - | `dibatalkan` | `gagal` | Transaksi ditolak atau dibatalkan. |

### Prinsip Idempotensi:
Midtrans menerapkan *retry mechanism* jika server merespons lambat (timeout).
- Jika pesanan sudah berstatus `dibayar`, abaikan notifikasi susulan dan langsung berikan respons HTTP 200 `OK`.
- Jangan pernah mengembalikan status pesanan yang sudah `dibayar` kembali ke `menunggu_bayar` atau `dibatalkan`.

---

## 7. Pengujian Pembayaran dengan Midtrans Simulator

Simulator resmi Sandbox dapat diakses tanpa biaya:
- **Simulator Link**: `https://simulator.sandbox.midtrans.com/`
- Pilihan metode uji coba:
  1. **BCA / BNI / BRI / Mandiri Virtual Account**: Masukkan nomor VA yang muncul di Snap/layar bayar, lalu klik "Inquire" dan "Pay".
  2. **QRIS**: Masukkan QR string atau scan via simulator untuk memicu status pembayaran lunas.
  3. **Simulator Webhook**: Dashboard Midtrans menyediakan fitur *Notification Simulator* di menu **Settings** $\rightarrow$ **Configuration** $\rightarrow$ tombol *Check Notification*.
