<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['listing_id', 'buyer_id', 'seller_id'];

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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Dapatkan pengguna lawan bicara dalam percakapan ini.
     */
    public function otherUser(User|int|null $user = null): ?User
    {
        $userId = $user instanceof User ? $user->id : ($user ?? auth()->id());

        if ($this->buyer_id === $userId) {
            return $this->seller;
        }

        if ($this->seller_id === $userId) {
            return $this->buyer;
        }

        return null;
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
