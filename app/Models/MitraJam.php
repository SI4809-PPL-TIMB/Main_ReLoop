<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jam operasional mitra untuk satu hari. Hari tanpa baris berarti libur. */
class MitraJam extends Model
{
    protected $table = 'mitra_jam';

    public $timestamps = false;

    protected $fillable = ['mitra_id', 'hari', 'buka', 'tutup'];

    protected function casts(): array
    {
        return ['hari' => 'integer'];
    }

    public function namaHari(): string
    {
        return MitraPengepul::HARI[$this->hari] ?? '';
    }

    /** "08.00–16.00" */
    public function rentang(): string
    {
        return str_replace(':', '.', substr($this->buka, 0, 5)).'–'.str_replace(':', '.', substr($this->tutup, 0, 5));
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(MitraPengepul::class, 'mitra_id');
    }
}
