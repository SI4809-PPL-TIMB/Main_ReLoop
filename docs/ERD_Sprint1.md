# ERD Sprint 1 — ReLoop

## Daftar Tabel & Relasi

### 1. `wilayah_provinsi`
| Kolom      | Tipe          | Keterangan              |
|------------|---------------|-------------------------|
| `id`       | BIGINT PK AI  |                         |
| `nama`     | VARCHAR(100)  | Nama provinsi           |
| `kode`     | VARCHAR(10)   | Kode BPS                |
| `created_at` / `updated_at` | TIMESTAMP |              |

### 2. `wilayah_kabupaten`
| Kolom          | Tipe          | Keterangan              |
|----------------|---------------|-------------------------|
| `id`           | BIGINT PK AI  |                         |
| `provinsi_id`  | BIGINT FK     | → wilayah_provinsi.id   |
| `nama`         | VARCHAR(100)  |                         |
| `kode`         | VARCHAR(10)   |                         |
| `created_at` / `updated_at` | TIMESTAMP |              |

### 3. `wilayah_kecamatan`
| Kolom           | Tipe          | Keterangan               |
|-----------------|---------------|--------------------------|
| `id`            | BIGINT PK AI  |                          |
| `kabupaten_id`  | BIGINT FK     | → wilayah_kabupaten.id   |
| `nama`          | VARCHAR(100)  |                          |
| `kode`          | VARCHAR(10)   |                          |
| `created_at` / `updated_at` | TIMESTAMP |               |

### 4. `wilayah_kelurahan`
| Kolom            | Tipe          | Keterangan                |
|------------------|---------------|---------------------------|
| `id`             | BIGINT PK AI  |                           |
| `kecamatan_id`   | BIGINT FK     | → wilayah_kecamatan.id    |
| `nama`           | VARCHAR(100)  |                           |
| `kode`           | VARCHAR(10)   |                           |
| `created_at` / `updated_at` | TIMESTAMP |                |

### 5. `users`
| Kolom              | Tipe          | Keterangan                          |
|--------------------|---------------|-------------------------------------|
| `id`               | BIGINT PK AI  |                                     |
| `name`             | VARCHAR(150)  |                                     |
| `email`            | VARCHAR(255)  | UNIQUE, nullable (jika pakai HP)    |
| `phone`            | VARCHAR(20)   | UNIQUE, nullable (jika pakai email) |
| `password`         | VARCHAR(255)  | bcrypt                              |
| `email_verified_at`| TIMESTAMP     | nullable                            |
| `remember_token`   | VARCHAR(100)  | nullable                            |
| `created_at` / `updated_at` | TIMESTAMP |                              |

### 6. `profiles`
| Kolom            | Tipe          | Keterangan                         |
|------------------|---------------|------------------------------------|
| `id`             | BIGINT PK AI  |                                    |
| `user_id`        | BIGINT FK     | → users.id, UNIQUE                 |
| `kelurahan_id`   | BIGINT FK     | → wilayah_kelurahan.id, nullable   |
| `avatar`         | VARCHAR(255)  | path/URL foto profil, nullable     |
| `bio`            | TEXT          | nullable                           |
| `avg_rating`     | DECIMAL(3,2)  | DEFAULT 0.00                       |
| `total_sold`     | INT           | DEFAULT 0                          |
| `streak_count`   | INT           | DEFAULT 0                          |
| `streak_last_at` | DATE          | nullable                           |
| `level`          | TINYINT       | DEFAULT 1                          |
| `total_points`   | INT           | DEFAULT 0                          |
| `created_at` / `updated_at` | TIMESTAMP |                           |

### 7. `categories`
| Kolom      | Tipe          | Keterangan               |
|------------|---------------|--------------------------|
| `id`       | BIGINT PK AI  |                          |
| `name`     | VARCHAR(100)  |                          |
| `slug`     | VARCHAR(100)  | UNIQUE                   |
| `icon`     | VARCHAR(255)  | path ikon, nullable      |
| `sort_order` | TINYINT     | DEFAULT 0                |
| `created_at` / `updated_at` | TIMESTAMP |             |

### 8. `listings`
| Kolom            | Tipe           | Keterangan                                             |
|------------------|----------------|--------------------------------------------------------|
| `id`             | BIGINT PK AI   |                                                        |
| `user_id`        | BIGINT FK      | → users.id (penjual)                                   |
| `category_id`    | BIGINT FK      | → categories.id                                        |
| `kelurahan_id`   | BIGINT FK      | → wilayah_kelurahan.id                                 |
| `title`          | VARCHAR(200)   |                                                        |
| `description`    | TEXT           | nullable                                               |
| `price`          | DECIMAL(12,0)  |                                                        |
| `is_negotiable`  | BOOLEAN        | DEFAULT true                                           |
| `condition`      | ENUM           | 'seperti_baru', 'baik', 'ada_minus'                    |
| `condition_note` | VARCHAR(255)   | catatan kekurangan, nullable                           |
| `status`         | ENUM           | 'aktif', 'dipesan', 'terjual', 'draft', 'dihapus'     |
| `view_count`     | INT            | DEFAULT 0                                              |
| `material`       | VARCHAR(100)   | nullable                                               |
| `size`           | VARCHAR(100)   | nullable                                               |
| `usage_duration` | VARCHAR(100)   | nullable                                               |
| `quantity`       | TINYINT        | DEFAULT 1                                              |
| `published_at`   | TIMESTAMP      | nullable                                               |
| `created_at` / `updated_at` | TIMESTAMP |                                                 |

### 9. `listing_photos`
| Kolom        | Tipe          | Keterangan                          |
|--------------|---------------|-------------------------------------|
| `id`         | BIGINT PK AI  |                                     |
| `listing_id` | BIGINT FK     | → listings.id                       |
| `path`       | VARCHAR(255)  | path file storage                   |
| `sort_order` | TINYINT       | DEFAULT 0 (foto 0 = foto utama)     |
| `created_at` / `updated_at` | TIMESTAMP |                          |

### 10. `conversations`
| Kolom        | Tipe          | Keterangan                          |
|--------------|---------------|-------------------------------------|
| `id`         | BIGINT PK AI  |                                     |
| `listing_id` | BIGINT FK     | → listings.id                       |
| `buyer_id`   | BIGINT FK     | → users.id (pembeli)                |
| `seller_id`  | BIGINT FK     | → users.id (penjual)                |
| `created_at` / `updated_at` | TIMESTAMP |                          |

### 11. `messages`
| Kolom              | Tipe          | Keterangan                          |
|--------------------|---------------|-------------------------------------|
| `id`               | BIGINT PK AI  |                                     |
| `conversation_id`  | BIGINT FK     | → conversations.id                  |
| `sender_id`        | BIGINT FK     | → users.id                          |
| `body`             | TEXT          |                                     |
| `read_at`          | TIMESTAMP     | nullable                            |
| `created_at` / `updated_at` | TIMESTAMP |                          |

### 12. `offers`
| Kolom              | Tipe           | Keterangan                                        |
|--------------------|----------------|---------------------------------------------------|
| `id`               | BIGINT PK AI   |                                                   |
| `conversation_id`  | BIGINT FK      | → conversations.id                                |
| `offerer_id`       | BIGINT FK      | → users.id (yang mengajukan tawaran)              |
| `amount`           | DECIMAL(12,0)  |                                                   |
| `status`           | ENUM           | 'menunggu', 'diterima', 'ditolak', 'dibatalkan'   |
| `expires_at`       | TIMESTAMP      | nullable                                          |
| `created_at` / `updated_at` | TIMESTAMP |                                            |

### 13. `orders`
| Kolom              | Tipe           | Keterangan                                                     |
|--------------------|----------------|----------------------------------------------------------------|
| `id`               | BIGINT PK AI   |                                                                |
| `order_number`     | VARCHAR(30)    | UNIQUE, misal RL-20261020-0142                                 |
| `listing_id`       | BIGINT FK      | → listings.id                                                  |
| `buyer_id`         | BIGINT FK      | → users.id                                                     |
| `seller_id`        | BIGINT FK      | → users.id                                                     |
| `agreed_price`     | DECIMAL(12,0)  | harga yang disepakati                                          |
| `delivery_method`  | ENUM           | 'cod', 'ambil'                                                 |
| `delivery_address` | TEXT           | nullable                                                       |
| `status`           | ENUM           | 'menunggu_bayar', 'dibayar', 'diserahkan', 'selesai', 'dibatalkan' |
| `seller_confirmed_at`| TIMESTAMP    | nullable (penjual tandai diserahkan)                           |
| `buyer_confirmed_at` | TIMESTAMP    | nullable (pembeli konfirmasi terima)                           |
| `auto_complete_at` | TIMESTAMP      | nullable (otomatis selesai 3 hari setelah diserahkan)          |
| `cancelled_at`     | TIMESTAMP      | nullable                                                       |
| `cancel_reason`    | TEXT           | nullable                                                       |
| `created_at` / `updated_at` | TIMESTAMP |                                                         |

### 14. `payments`
| Kolom             | Tipe           | Keterangan                                          |
|-------------------|----------------|-----------------------------------------------------|
| `id`              | BIGINT PK AI   |                                                     |
| `order_id`        | BIGINT FK      | → orders.id, UNIQUE                                 |
| `midtrans_id`     | VARCHAR(100)   | UNIQUE, nullable                                    |
| `method`          | ENUM           | 'qris', 'va', 'ewallet'                             |
| `va_number`       | VARCHAR(50)    | nullable                                            |
| `bank`            | VARCHAR(20)    | nullable (BCA, BNI, dll.)                           |
| `amount`          | DECIMAL(12,0)  |                                                     |
| `platform_fee`    | DECIMAL(12,0)  | DEFAULT 0 (3% dari agreed_price)                    |
| `status`          | ENUM           | 'menunggu', 'berhasil', 'gagal', 'kedaluwarsa'      |
| `paid_at`         | TIMESTAMP      | nullable                                            |
| `expired_at`      | TIMESTAMP      | nullable                                            |
| `created_at` / `updated_at` | TIMESTAMP |                                              |

### 15. `reviews`
| Kolom        | Tipe          | Keterangan                                     |
|--------------|---------------|------------------------------------------------|
| `id`         | BIGINT PK AI  |                                                |
| `order_id`   | BIGINT FK     | → orders.id                                    |
| `reviewer_id`| BIGINT FK     | → users.id (yang memberi ulasan)               |
| `reviewee_id`| BIGINT FK     | → users.id (yang diulas)                       |
| `listing_id` | BIGINT FK     | → listings.id, nullable                        |
| `rating`     | TINYINT       | 1-5                                            |
| `body`       | TEXT          | nullable                                       |
| `created_at` / `updated_at` | TIMESTAMP |                                     |

### 16. `notifications`
| Kolom        | Tipe          | Keterangan                                                      |
|--------------|---------------|-----------------------------------------------------------------|
| `id`         | UUID PK       | (Laravel default Notification)                                  |
| `type`       | VARCHAR(255)  |                                                                 |
| `notifiable_type` | VARCHAR(255) |                                                            |
| `notifiable_id`   | BIGINT FK  | → users.id                                                      |
| `data`       | JSON          |                                                                 |
| `read_at`    | TIMESTAMP     | nullable                                                        |
| `created_at` / `updated_at` | TIMESTAMP |                                                  |

---

## Diagram Relasi (Ringkas)

```
wilayah_provinsi
  └── wilayah_kabupaten
        └── wilayah_kecamatan
              └── wilayah_kelurahan ──────┐
                                          │
users ────────────────────────────────────┤ (profiles.kelurahan_id)
  │                                       │ (listings.kelurahan_id)
  ├── profiles ─────────────────── wilayah_kelurahan
  │
  ├── listings ──── categories
  │     └── listing_photos
  │
  ├── conversations (buyer/seller) ── listings
  │     ├── messages
  │     └── offers
  │
  ├── orders (buyer/seller) ──── listings
  │     ├── payments
  │     └── reviews (reviewer/reviewee)
  │
  └── notifications
```

---

## Catatan Bisnis
- **Komisi**: 3% dari `agreed_price`, disimpan di `payments.platform_fee`
- **Escrow**: dana ditahan sampai `orders.buyer_confirmed_at` terisi atau `auto_complete_at` terlewati
- **Streak**: dihitung di `profiles.streak_count` — bertambah 1 per hari jika ada 1 aktivitas sah
- **Poin**: disimpan di `profiles.total_points` — tidak bisa ditukar uang (BR-06)
- **Wilayah**: jenjang 4-level (provinsi → kabupaten → kecamatan → kelurahan/desa)
