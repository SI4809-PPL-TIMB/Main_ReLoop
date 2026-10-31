<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}

