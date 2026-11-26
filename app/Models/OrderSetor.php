<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * 9.1.2 · Model Order Setor ke Mitra Pengepul (PPLTIMB-170).
 */
class OrderSetor extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_DITERIMA = 'diterima';
    public const STATUS_DITIMBANG = 'ditimbang';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';
    public const STATUS_DITOLAK = 'ditolak';

    public const METODE_JEMPUT = 'jemput';
    public const METODE_ANTAR = 'antar';

    public const STATUS = [
        self::STATUS_MENUNGGU    => 'Menunggu konfirmasi',
        self::STATUS_DITERIMA    => 'Diterima pengepul',
        self::STATUS_DITIMBANG   => 'Ditimbang & dibayar',
        self::STATUS_SELESAI     => 'Selesai',
        self::STATUS_DIBATALKAN  => 'Dibatalkan',
        self::STATUS_DITOLAK     => 'Ditolak',
    ];

    public const METODE = [
        self::METODE_JEMPUT => 'Jemput ke Rumah',
        self::METODE_ANTAR  => 'Antar Sendiri ke Lapak',
    ];

    protected $table = 'order_setor';

    protected $fillable = [
        'order_number',
        'user_id',
        'mitra_id',
        'category_id',
        'perkiraan_berat',
        'harga_per_kg',
        'total_estimasi',
        'metode',
        'alamat_jemput',
        'patokan',
        'catatan',
        'status',
        'berat_aktual',
        'total_aktual',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'perkiraan_berat' => 'float',
            'berat_aktual'    => 'float',
            'harga_per_kg'    => 'integer',
            'total_estimasi'  => 'integer',
            'total_aktual'    => 'integer',
            'confirmed_at'    => 'datetime',
            'completed_at'    => 'datetime',
            'cancelled_at'    => 'datetime',
        ];
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'ST-' . now()->format('Ymd') . '-';
        do {
            $number = $prefix . strtoupper(Str::random(4));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(MitraPengepul::class, 'mitra_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_MENUNGGU);
    }

    public function scopeBerjalan(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_MENUNGGU, self::STATUS_DITERIMA, self::STATUS_DITIMBANG]);
    }

    public function scopeSelesai(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_SELESAI, self::STATUS_DIBATALKAN, self::STATUS_DITOLAK]);
    }

    public function isCustomer(User|int $user): bool
    {
        $id = $user instanceof User ? $user->id : $user;

        return (int) $this->user_id === (int) $id;
    }

    public function isMitra(User|int $user): bool
    {
        $id = $user instanceof User ? $user->id : $user;

        return (int) $this->mitra?->user_id === (int) $id;
    }

    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_MENUNGGU;
    }

    public function isMenunggu(): bool
    {
        return $this->status === self::STATUS_MENUNGGU;
    }

    public function isSelesai(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    public function isDibatalkan(): bool
    {
        return in_array($this->status, [self::STATUS_DIBATALKAN, self::STATUS_DITOLAK]);
    }

    public function getBeratAsliAttribute(): ?float
    {
        return $this->berat_aktual;
    }

    public function getTotalFinalAttribute(): ?int
    {
        return $this->total_aktual;
    }

    public function metodeLabel(): string
    {
        return self::METODE[$this->metode] ?? $this->metode;
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}
