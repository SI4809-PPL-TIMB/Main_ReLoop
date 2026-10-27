<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WilayahKabupaten extends Model
{
    protected $table = 'wilayah_kabupaten';

    protected $fillable = ['provinsi_id', 'nama', 'kode'];

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(WilayahProvinsi::class, 'provinsi_id');
    }

    public function kecamatan(): HasMany
    {
        return $this->hasMany(WilayahKecamatan::class, 'kabupaten_id');
    }
}
