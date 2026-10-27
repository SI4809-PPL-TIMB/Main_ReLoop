<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WilayahKecamatan extends Model
{
    protected $table = 'wilayah_kecamatan';

    protected $fillable = ['kabupaten_id', 'nama', 'kode'];

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(WilayahKabupaten::class, 'kabupaten_id');
    }

    public function kelurahan(): HasMany
    {
        return $this->hasMany(WilayahKelurahan::class, 'kecamatan_id');
    }
}
