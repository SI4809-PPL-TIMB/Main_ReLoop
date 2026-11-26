<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Harga beli per kg satu jenis sampah (kategori daur ulang) di satu mitra. */
class MitraHarga extends Model
{
    protected $table = 'mitra_harga';

    protected $fillable = ['mitra_id', 'category_id', 'harga_per_kg'];

    protected function casts(): array
    {
        return ['harga_per_kg' => 'integer'];
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(MitraPengepul::class, 'mitra_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getJenisAttribute(): string
    {
        return $this->category?->name ?? 'Daur Ulang';
    }
}
