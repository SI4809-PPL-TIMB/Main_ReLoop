<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WilayahProvinsi extends Model
{
    protected $table = 'wilayah_provinsi';

    protected $fillable = ['nama', 'kode'];

    public function kabupaten(): HasMany
    {
        return $this->hasMany(WilayahKabupaten::class, 'provinsi_id');
    }
}
