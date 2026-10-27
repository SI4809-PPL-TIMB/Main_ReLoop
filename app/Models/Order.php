<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
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
