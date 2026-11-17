<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 6.2 — Model tawaran harga antara pembeli dan penjual.
 *
 * Status:
 *   menunggu   – tawaran baru, belum direspons penjual
 *   diterima   – penjual menerima; menjadi harga checkout (7.1)
 *   ditolak    – penjual menolak
 *   kedaluwarsa – tidak direspons dalam 24 jam
 *   dibatalkan – pembeli mengajukan tawaran baru; otomatis membatalkan tawaran lama
 */
class Offer extends Model
{
    public const MENUNGGU    = 'menunggu';
    public const DITERIMA    = 'diterima';
    public const DITOLAK     = 'ditolak';
    public const KEDALUWARSA = 'kedaluwarsa';
    public const DIBATALKAN  = 'dibatalkan';

    protected $fillable = [
        'conversation_id',
        'offerer_id',
        'amount',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:0',
            'expires_at' => 'datetime',
        ];
    }

    /** Apakah tawaran ini masih aktif (menunggu dan belum kedaluwarsa). */
    public function isAktif(): bool
    {
        return $this->status === self::MENUNGGU
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    /** Muat tawaran aktif terbaru untuk percakapan ini, atau null jika tidak ada. */
    public static function aktifUntuk(Conversation $conversation): ?self
    {
        return $conversation->offers()
            ->where('status', self::MENUNGGU)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function offerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offerer_id');
    }
}
