<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUS_MENUNGGU_BAYAR = 'menunggu_bayar';
    public const STATUS_DIBAYAR = 'dibayar';
    public const STATUS_DISERAHKAN = 'diserahkan';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const DELIVERY_COD = 'cod';
    public const DELIVERY_AMBIL = 'ambil';
    public const DELIVERY_KIRIM = 'kirim';

    protected $fillable = [
        'order_number',
        'listing_id',
        'buyer_id',
        'seller_id',
        'agreed_price',
        'delivery_method',
        'delivery_address',
        'status',
        'seller_confirmed_at',
        'buyer_confirmed_at',
        'auto_complete_at',
        'cancelled_at',
        'cancel_reason',
    ];

    public static function generateOrderNumber(): string
    {
        $prefix = 'RL-' . now()->format('Ymd') . '-';
        do {
            $number = $prefix . strtoupper(Str::random(4));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    protected function casts(): array
    {
        return [
            'agreed_price'        => 'decimal:0',
            'seller_confirmed_at' => 'datetime',
            'buyer_confirmed_at'  => 'datetime',
            'auto_complete_at'    => 'datetime',
            'cancelled_at'        => 'datetime',
        ];
    }

    public function deliveryLabel(): string
    {
        return match ($this->delivery_method) {
            self::DELIVERY_AMBIL => 'Ambil di tempat penjual',
            self::DELIVERY_KIRIM => 'Kirim pakai delivery',
            default => 'Ketemuan (COD lokasi)',
        };
    }

    /**
     * Cek apakah dana pesanan sedang tertahan di sistem (escrow).
     */
    public function isEscrowHeld(): bool
    {
        return in_array($this->status, [self::STATUS_DIBAYAR, self::STATUS_DISERAHKAN]);
    }

    /**
     * Hitung nilai komisi platform (3%).
     */
    public function platformFee(): float
    {
        if ($this->payment && $this->payment->platform_fee > 0) {
            return (float) $this->payment->platform_fee;
        }

        return (float) round((float) $this->agreed_price * Payment::KOMISI_RATE);
    }

    /**
     * Hitung dana bersih yang diterima penjual setelah dipotong komisi 3%.
     */
    public function netSellerAmount(): float
    {
        return (float) $this->agreed_price - $this->platformFee();
    }

    /**
     * Cek apakah pesanan dapat ditandai diserahkan oleh penjual.
     */
    public function canBeMarkedDiserahkan(): bool
    {
        return $this->status === self::STATUS_DIBAYAR;
    }

    /**
     * Cek apakah pesanan dapat dikonfirmasi telah diterima oleh pembeli.
     */
    public function canBeConfirmedReceived(): bool
    {
        return $this->status === self::STATUS_DISERAHKAN;
    }

    /**
     * Cek apakah pesanan masih dapat dibatalkan (sebelum diserahkan).
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_MENUNGGU_BAYAR, self::STATUS_DIBAYAR]);
    }

    public function isBuyer(User|int $user): bool
    {
        $id = $user instanceof User ? $user->id : $user;
        return (int) $this->buyer_id === (int) $id;
    }

    public function isSeller(User|int $user): bool
    {
        $id = $user instanceof User ? $user->id : $user;
        return (int) $this->seller_id === (int) $id;
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
