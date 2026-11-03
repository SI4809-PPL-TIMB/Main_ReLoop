<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Listing extends Model
{
    public const AKTIF = 'aktif';
    public const DIPESAN = 'dipesan';
    public const TERJUAL = 'terjual';
    public const DRAFT = 'draft';
    public const DIHAPUS = 'dihapus';

    public const KONDISI = [
        'seperti_baru' => ['Seperti baru', 'Hampir tanpa bekas'],
        'baik'         => ['Baik', 'Wajar dipakai'],
        'ada_minus'    => ['Ada minus', 'Ada cacat kecil'],
    ];

    public const MAKS_FOTO = 5;

    protected $fillable = [
        'user_id',
        'category_id',
        'kelurahan_id',
        'title',
        'description',
        'price',
        'is_negotiable',
        'condition',
        'condition_note',
        'status',
        'view_count',
        'material',
        'size',
        'usage_duration',
        'quantity',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price'        => 'decimal:0',
            'is_negotiable' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopeMilik($query, User $user)
    {
        return $query->where('user_id', $user->id)->where('status', '!=', self::DIHAPUS);
    }

    /** Barang yang tayang untuk pembeli. */
    public function scopeAktif($query)
    {
        return $query->where('listings.status', self::AKTIF);
    }

    /**
     * Urutkan barang dari yang terdekat dengan lokasi pembeli (titik + desa yang disimpan di 3.1).
     * Titik barang = titik profil penjual, karena barang mewarisi lokasi penjual saat dipasang.
     *
     * 1. Bila pembeli punya koordinat: urut jarak (barang tanpa koordinat penjual di belakang).
     *    Jarak pakai pendekatan equirectangular: cukup akurat untuk skala kota dan hanya aritmetika,
     *    jadi jalan di SQLite (tanpa fungsi trigonometri) maupun MySQL.
     * 2. Lalu kedekatan wilayah: desa sama > kecamatan sama > kab/kota sama > lainnya.
     * 3. Lalu yang terbaru tayang.
     *
     * Dasar untuk pencarian dan filter 5.1 berikutnya: tinggal ditambah where() di atasnya.
     */
    public function scopeTerdekat($query, Profile|User|null $lokasi = null)
    {
        if ($lokasi instanceof User) {
            $lokasi = $lokasi->profile;
        }

        $query->select('listings.*')
            ->leftJoin('profiles as penjual', 'penjual.user_id', '=', 'listings.user_id')
            ->leftJoin('wilayah_kelurahan as lok_kel', 'lok_kel.id', '=', 'listings.kelurahan_id')
            ->leftJoin('wilayah_kecamatan as lok_kec', 'lok_kec.id', '=', 'lok_kel.kecamatan_id');

        if ($lokasi?->latitude !== null && $lokasi->longitude !== null) {
            $lat = $lokasi->latitude;
            $lng = $lokasi->longitude;
            $skala = cos(deg2rad($lat)) ** 2; // 1 derajat bujur menyempit sesuai lintang

            $query->selectRaw(
                'CASE WHEN penjual.latitude IS NULL OR penjual.longitude IS NULL THEN NULL'
                .' ELSE (penjual.latitude - ?) * (penjual.latitude - ?) + (penjual.longitude - ?) * (penjual.longitude - ?) * ? END as jarak_derajat2',
                [$lat, $lat, $lng, $lng, $skala]
            )
                ->orderByRaw('CASE WHEN penjual.latitude IS NULL OR penjual.longitude IS NULL THEN 1 ELSE 0 END')
                ->orderBy('jarak_derajat2');
        }

        if ($kel = $lokasi?->kelurahan) {
            $query->orderByRaw(
                'CASE WHEN listings.kelurahan_id = ? THEN 0 WHEN lok_kel.kecamatan_id = ? THEN 1 WHEN lok_kec.kabupaten_id = ? THEN 2 ELSE 3 END',
                [$kel->id, $kel->kecamatan_id, $kel->kecamatan?->kabupaten_id]
            );
        }

        return $query->orderByDesc('listings.published_at')->orderByDesc('listings.id');
    }

    /** Jarak ke pembeli dalam km; hanya terisi bila diambil lewat scopeTerdekat() dan kedua titik diketahui. */
    protected function jarakKm(): Attribute
    {
        return Attribute::get(fn () => isset($this->attributes['jarak_derajat2'])
            ? sqrt((float) $this->attributes['jarak_derajat2']) * 111.195
            : null);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(WilayahKelurahan::class, 'kelurahan_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class)->orderBy('sort_order');
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(ListingPhoto::class)->orderBy('sort_order');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
