<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** 8.1 · Mitra pengepul: dikelola admin, pengaturan layanan diisi pengepul sendiri. */
class MitraPengepul extends Model
{
    public const MENUNGGU = 'menunggu';
    public const TERVERIFIKASI = 'terverifikasi';
    public const DITOLAK = 'ditolak';

    public const STATUS = [
        self::MENUNGGU      => 'Menunggu verifikasi',
        self::TERVERIFIKASI => 'Terverifikasi',
        self::DITOLAK       => 'Ditolak',
    ];

    /** ISO-8601: 1 = Senin … 7 = Minggu (sama dengan Carbon::dayOfWeekIso). */
    public const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    protected $table = 'mitra_pengepul';

    // status dan kolom verifikasi sengaja tidak fillable: hanya diubah lewat verifikasi().
    protected $fillable = [
        'user_id',
        'nama_usaha',
        'telepon',
        'alamat',
        'kelurahan_id',
        'layanan_jemput',
        'layanan_antar',
    ];

    protected $attributes = [
        'status' => self::MENUNGGU,
    ];

    protected function casts(): array
    {
        return [
            'layanan_jemput'    => 'boolean',
            'layanan_antar'     => 'boolean',
            'diverifikasi_pada' => 'datetime',
        ];
    }

    /** Hanya mitra terverifikasi yang boleh muncul di pencarian pengepul (8.3). */
    public function scopeTerverifikasi(Builder $query): Builder
    {
        return $query->where('status', self::TERVERIFIKASI);
    }

    /** 8.3 · Mitra yang melayani kecamatan ini (area layanan, bukan lokasi lapak). Null = tanpa filter. */
    public function scopeMelayani(Builder $query, ?int $kecamatanId): Builder
    {
        return $query->when($kecamatanId, fn ($q) => $q->whereHas('area', fn ($a) => $a->whereKey($kecamatanId)));
    }

    /** 8.3 · Mitra yang punya harga untuk jenis sampah (kategori daur ulang) ini. Null = tanpa filter. */
    public function scopeMenerima(Builder $query, ?int $categoryId): Builder
    {
        return $query->when($categoryId, fn ($q) => $q->whereHas('harga', fn ($h) => $h->where('category_id', $categoryId)));
    }

    /**
     * Jarak perkiraan dari lokasi pengguna. Mitra belum punya koordinat, jadi dipakai kedekatan wilayah lapak:
     * 0 = satu kelurahan, 1 = satu kecamatan, 2 = satu kota/kabupaten, 3 = lebih jauh / tidak diketahui.
     * shortcut: tanpa km sungguhan; ganti ke jarak koordinat bila mitra punya titik lapak.
     */
    public function kedekatan(?WilayahKelurahan $dari): int
    {
        $lapak = $this->kelurahan;
        if (! $dari || ! $lapak) {
            return 3;
        }

        return match (true) {
            $lapak->id === $dari->id                                          => 0,
            $lapak->kecamatan_id === $dari->kecamatan_id                      => 1,
            $lapak->kecamatan?->kabupaten_id === $dari->kecamatan?->kabupaten_id => 2,
            default                                                           => 3,
        };
    }

    public const KEDEKATAN = [0 => 'Satu kelurahan', 1 => 'Satu kecamatan', 2 => 'Satu kota/kabupaten', 3 => 'Lebih jauh'];

    /** Rating rata-rata dari profil akun mitra; null bila belum ada ulasan. */
    public function rating(): ?float
    {
        $rating = (float) ($this->user?->profile?->avg_rating ?? 0);

        return $rating > 0 ? $rating : null;
    }

    /** Ilustrasi dari public/assets/illust/collectors yang cocok dengan nama usaha, mis. "hijau-lestari". */
    public function gambar(): ?string
    {
        $slug = Str::slug($this->nama_usaha);

        return collect(glob(public_path('assets/illust/collectors/*.png')) ?: [])
            ->map(fn ($f) => pathinfo($f, PATHINFO_FILENAME))
            ->first(fn ($g) => str_contains($slug, $g));
    }

    /** Simpan keputusan admin; status + waktu + admin tersimpan untuk notifikasi (11.2). */
    public function verifikasi(string $status, User $admin, ?string $catatan = null): void
    {
        $this->forceFill([
            'status'             => $status,
            'catatan_verifikasi' => $status === self::DITOLAK ? $catatan : null,
            'diverifikasi_pada'  => $status === self::MENUNGGU ? null : now(),
            'diverifikasi_oleh'  => $status === self::MENUNGGU ? null : $admin->id,
        ])->save();
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /** Label layanan, mis. "Jemput · Antar Sendiri". */
    public function layananLabel(): string
    {
        return collect(['Jemput' => $this->layanan_jemput, 'Antar Sendiri' => $this->layanan_antar])
            ->filter()->keys()->implode(' · ') ?: 'Belum diatur';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(WilayahKelurahan::class, 'kelurahan_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function area(): BelongsToMany
    {
        return $this->belongsToMany(WilayahKecamatan::class, 'mitra_area', 'mitra_id', 'kecamatan_id');
    }

    public function harga(): HasMany
    {
        return $this->hasMany(MitraHarga::class, 'mitra_id');
    }

    public function jam(): HasMany
    {
        return $this->hasMany(MitraJam::class, 'mitra_id')->orderBy('hari');
    }

    public function orderSetors(): HasMany
    {
        return $this->hasMany(OrderSetor::class, 'mitra_id');
    }
}
