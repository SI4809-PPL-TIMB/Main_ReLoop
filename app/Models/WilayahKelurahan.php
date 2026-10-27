<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WilayahKelurahan extends Model
{
    protected $table = 'wilayah_kelurahan';

    protected $fillable = ['kecamatan_id', 'nama', 'kode'];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(WilayahKecamatan::class, 'kecamatan_id');
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'kelurahan_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'kelurahan_id');
    }
}
