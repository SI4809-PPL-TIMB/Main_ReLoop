<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WilayahKelurahan extends Model
{
    protected $table = 'wilayah_kelurahan';

    protected $fillable = ['kecamatan_id', 'nama', 'kode'];

    /** "Kelurahan, Kecamatan, Kabupaten, Provinsi" — hanya nama daerah, tidak pernah alamat lengkap (BR-07). */
    public function breadcrumb(): string
    {
        $kec = $this->kecamatan;
        $kab = $kec->kabupaten;

        return implode(', ', [$this->nama, $kec->nama, $kab->nama, $kab->provinsi->nama]);
    }

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
