<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const TYPE_BEKAS = 'bekas';
    public const TYPE_DAUR_ULANG = 'daur_ulang';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'icon',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Daftar label tipe kategori.
     */
    public static function types(): array
    {
        return [
            self::TYPE_BEKAS      => 'Barang Bekas',
            self::TYPE_DAUR_ULANG => 'Daur Ulang',
        ];
    }

    /**
     * Scope kategori barang bekas.
     */
    public function scopeBekas(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BEKAS);
    }

    /**
     * Scope kategori sampah daur ulang.
     */
    public function scopeDaurUlang(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DAUR_ULANG);
    }

    /**
     * Scope filter berdasarkan tipe kategori (opsional).
     */
    public function scopeByType(Builder $query, ?string $type = null): Builder
    {
        if ($type && in_array($type, [self::TYPE_BEKAS, self::TYPE_DAUR_ULANG], true)) {
            return $query->where('type', $type);
        }

        return $query;
    }

    /**
     * Query siap pakai untuk form / filter pilihan kategori.
     */
    public static function forSelect(?string $type = null): Collection
    {
        return static::query()
            ->byType($type)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Dapatkan kategori yang sudah dikelompokkan berdasarkan tipe.
     */
    public static function groupedByType(): array
    {
        $all = static::query()->orderBy('sort_order')->orderBy('name')->get();

        return [
            self::TYPE_BEKAS      => $all->where('type', self::TYPE_BEKAS)->values(),
            self::TYPE_DAUR_ULANG => $all->where('type', self::TYPE_DAUR_ULANG)->values(),
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}
