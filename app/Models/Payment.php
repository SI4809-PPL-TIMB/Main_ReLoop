<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_BERHASIL = 'berhasil';
    public const STATUS_GAGAL = 'gagal';
    public const STATUS_KEDALUWARSA = 'kedaluwarsa';

    public const METHOD_QRIS = 'qris';
    public const METHOD_VA = 'va';
    public const METHOD_EWALLET = 'ewallet';

    public const KOMISI_RATE = 0.03;

    protected $fillable = [
        'order_id',
        'midtrans_id',
        'method',
        'va_number',
        'bank',
        'amount',
        'platform_fee',
        'status',
        'paid_at',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:0',
            'platform_fee' => 'decimal:0',
            'paid_at'      => 'datetime',
            'expired_at'   => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
